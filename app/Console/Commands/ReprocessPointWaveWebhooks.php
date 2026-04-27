<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessPointWaveWebhook;

class ReprocessPointWaveWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pointwave:reprocess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess unprocessed PointWave webhook events';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $events = DB::table('webhook_events')
            ->where('processed', false)
            ->where('event_type', 'payment.success')
            ->get();

        if ($events->isEmpty()) {
            $this->info('No unprocessed PointWave events found.');
            return 0;
        }

        $this->info('Found ' . $events->count() . ' unprocessed events. Starting processing...');

        foreach ($events as $event) {
            $this->info('Processing event ID: ' . $event->event_id);
            
            try {
                $data = json_decode($event->payload, true);
                
                if (!$data) {
                    $this->error('Failed to decode payload for event ID: ' . $event->event_id);
                    continue;
                }

                // Dispatch the job
                // Since QUEUE_CONNECTION is sync, this will run immediately
                dispatch(new ProcessPointWaveWebhook($data, $event->event_id));
                
                $this->info('Successfully processed event ID: ' . $event->event_id);
            } catch (\Exception $e) {
                $this->error('Error processing event ID ' . $event->event_id . ': ' . $e->getMessage());
                $this->error('PAYLOAD DUMP: ' . $event->payload);
            }
        }

        $this->info('Reprocessing complete.');
        return 0;
    }
}

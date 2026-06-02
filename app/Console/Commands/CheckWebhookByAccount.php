<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckWebhookByAccount extends Command
{
    protected $signature = 'check:webhook {account}';
    protected $description = 'Check webhook_events for a specific account number';

    public function handle()
    {
        $account = $this->argument('account');
        $events = DB::table('webhook_events')
            ->where('payload', 'LIKE', '%' . $account . '%')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        if ($events->isEmpty()) {
            $this->info("No webhooks found containing: " . $account);
        } else {
            foreach ($events as $event) {
                $this->info("Event ID: " . $event->event_id);
                $this->info("Created: " . $event->created_at);
                $this->info("Processed: " . $event->processed);
                $this->info("Payload: " . $event->payload);
                $this->line("--------------------------------------------------");
            }
        }
    }
}

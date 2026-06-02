<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckWebhookByUser extends Command
{
    protected $signature = 'check:webhook-user {username}';
    protected $description = 'Check webhook_events for a specific username';

    public function handle()
    {
        $username = $this->argument('username');
        $user = DB::table('user')->where('username', $username)->first();

        if (!$user) {
            $this->error("User not found: " . $username);
            return;
        }

        $accounts = array_filter([
            $user->palmpay ?? null,
            $user->pointwave_account_number ?? null,
            $user->monnify_account ?? null,
        ]);

        if (empty($accounts)) {
            $this->error("No virtual accounts found for user: " . $username);
            return;
        }

        $this->info("Found virtual accounts for $username: " . implode(', ', $accounts));

        foreach ($accounts as $account) {
            $this->info("Checking webhooks for account: " . $account);
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
}

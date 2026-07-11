<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$username = 'oshafu';

echo "==================================================\n";
echo "🔍 DUMPING WEBHOOK EVENTS FOR USERNAME: " . strtoupper($username) . "\n";
echo "==================================================\n\n";

if (Illuminate\Support\Facades\Schema::hasTable('webhook_events')) {
    $events = DB::table('webhook_events')
        ->where('payload', 'like', "%$username%")
        ->orderBy('id', 'desc')
        ->limit(30)
        ->get();

    echo "Found " . $events->count() . " recent webhook events (out of 86 total):\n\n";
    foreach ($events as $ev) {
        echo "ID:         {$ev->id}\n";
        echo "Created At: {$ev->created_at}\n";
        echo "Payload:    " . json_encode(json_decode($ev->payload), JSON_PRETTY_PRINT) . "\n";
        echo "--------------------------------------------------\n";
    }
} else {
    echo "webhook_events table not found.\n";
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Show current state
echo "=== BEFORE UPDATE ===\n";
$plans = DB::table('cable_plan')->select('plan_id', 'plan_name', 'cable_name', 'habukhan1')->orderBy('plan_id')->get();
foreach ($plans as $p) {
    $status = empty($p->habukhan1) ? '❌ EMPTY' : '✅ ' . $p->habukhan1;
    echo "plan_id=$p->plan_id | $p->cable_name | $p->plan_name | habukhan1=$status\n";
}

echo "\n=== UPDATING: Setting habukhan1 = plan_id for all empty plans ===\n";

$updated = DB::table('cable_plan')
    ->where(function($q) {
        $q->whereNull('habukhan1')->orWhere('habukhan1', '');
    })
    ->update(['habukhan1' => DB::raw('plan_id')]);

echo "Updated $updated plans\n\n";

// Show after state
echo "=== AFTER UPDATE ===\n";
$plans = DB::table('cable_plan')->select('plan_id', 'plan_name', 'cable_name', 'habukhan1')->orderBy('plan_id')->get();
foreach ($plans as $p) {
    echo "plan_id=$p->plan_id | $p->cable_name | $p->plan_name | habukhan1=$p->habukhan1\n";
}

echo "\nDone! Cable purchases via Habukhan should now work.\n";

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Production;
use Carbon\Carbon;

$from = now()->startOfMonth()->toDateString();
$to = now()->toDateString();

$count = Production::where('status', 'done')
    ->whereBetween('production_date', [$from, $to])
    ->count();

$totalHpp = Production::where('status', 'done')
    ->whereBetween('production_date', [$from, $to])
    ->sum('total_cost_snapshot');

echo "Productions in range ($from - $to): $count\n";
echo "Total HPP in range: $totalHpp\n";

$allCount = Production::count();
echo "Total Productions in DB: $allCount\n";

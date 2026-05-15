<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Production;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $thisMonth = $now->month;
        $thisYear = $now->year;
        
        $lastMonthDate = $now->copy()->subMonth();
        $lastMonth = $lastMonthDate->month;
        $lastMonthYear = $lastMonthDate->year;

        // 1. Sales Statistics
        $salesThisMonth = Sale::whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->sum('total');
            
        $salesLastMonth = Sale::whereMonth('created_at', $lastMonth)
            ->whereYear('created_at', $lastMonthYear)
            ->sum('total');
            
        $salesGrowth = $salesLastMonth > 0 
            ? (($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100 
            : ($salesThisMonth > 0 ? 100 : 0);

        // 2. Production Statistics
        $prodThisMonth = Production::whereMonth('production_date', $thisMonth)
            ->whereYear('production_date', $thisYear)
            ->sum('quantity');
            
        $prodLastMonth = Production::whereMonth('production_date', $lastMonth)
            ->whereYear('production_date', $lastMonthYear)
            ->sum('quantity');
            
        $prodGrowth = $prodLastMonth > 0 
            ? (($prodThisMonth - $prodLastMonth) / $prodLastMonth) * 100 
            : ($prodThisMonth > 0 ? 100 : 0);

        // 3. Stock Statistics
        $lowStock = Material::whereColumn('stock', '<=', 'minimum_stock')->count();
        $totalMaterials = Material::count();
        $stockSafePercent = $totalMaterials > 0
            ? round((($totalMaterials - $lowStock) / $totalMaterials) * 100)
            : 0;

        // 4. Chart Data (Last 30 Days)
        $chartData = Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $recentProductions = Production::with('product')
            ->latest('production_date')
            ->limit(4)
            ->get();

        return view('DashboardUtama', [
            'totalSales' => $salesThisMonth,
            'salesGrowth' => $salesGrowth,
            'totalProduction' => $prodThisMonth,
            'prodGrowth' => $prodGrowth,
            'lowStock' => $lowStock,
            'stockSafePercent' => $stockSafePercent,
            'recentProductions' => $recentProductions,
            'chartData' => $chartData,
            'overallSales' => Sale::sum('total'), // Tetap kirim total keseluruhan jika ingin ditampilkan
        ]);
    }
}

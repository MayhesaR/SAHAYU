<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $targetDateString = $request->query('date');
        $isTimeTravel = !empty($targetDateString);

        if ($isTimeTravel) {
            $now = Carbon::parse($targetDateString);
            $filter = '1'; // Force daily view
        } else {
            $now = Carbon::now();
            $filter = $request->query('range');
            if (!$filter) {
                $filter = auth()->check() && auth()->user()->isStaff() ? '1' : '30';
            }
        }

        $companyId = auth()->user()->company_id;
        
        // Define date range
        $days = (int) $filter;
        $startDate = $now->copy()->subDays($days)->startOfDay();
        if ($filter === '1') {
            $startDate = $now->copy()->startOfDay();
        }
        $endDate = $now->copy()->endOfDay();
        
        $prevStartDate = $now->copy()->subDays($days * 2)->startOfDay();
        if ($filter === '1') {
            $prevStartDate = $now->copy()->subDays(1)->startOfDay();
        }
        $prevEndDate = $now->copy()->subDays($days)->endOfDay();

        // 1. Sales Statistics (Strict Cash Basis: Cash Sales + Debt/Installment Payments)
        $cashSalesThisPeriod = Sale::where('company_id', $companyId)
            ->whereIn('payment_method', ['cash', 'transfer', 'qris'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $debtPaymentsThisPeriod = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount_paid');

        $salesThisPeriod = $cashSalesThisPeriod + $debtPaymentsThisPeriod;

        $cashSalesPrevPeriod = Sale::where('company_id', $companyId)
            ->whereIn('payment_method', ['cash', 'transfer', 'qris'])
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('total');

        $debtPaymentsPrevPeriod = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->whereBetween('payment_date', [$prevStartDate->toDateString(), $prevEndDate->toDateString()])
            ->sum('amount_paid');

        $salesPrevPeriod = $cashSalesPrevPeriod + $debtPaymentsPrevPeriod;

        $salesGrowth = $salesPrevPeriod > 0 ? (($salesThisPeriod - $salesPrevPeriod) / $salesPrevPeriod) * 100 : ($salesThisPeriod > 0 ? 100 : 0);

        // 2. Expense Statistics
        $prodCostsThis = Production::where('company_id', $companyId)->whereBetween('production_date', [$startDate, $endDate])->sum('total_cost_snapshot');
        $overheadThis = OverheadCost::where('company_id', $companyId)->whereBetween('transaction_date', [$startDate, $endDate])->sum('cost');
        $expensesThisPeriod = $prodCostsThis + $overheadThis;

        $prodCostsPrev = Production::where('company_id', $companyId)->whereBetween('production_date', [$prevStartDate, $prevEndDate])->sum('total_cost_snapshot');
        $overheadPrev = OverheadCost::where('company_id', $companyId)->whereBetween('transaction_date', [$prevStartDate, $prevEndDate])->sum('cost');
        $expensesPrevPeriod = $prodCostsPrev + $overheadPrev;
        $expenseGrowth = $expensesPrevPeriod > 0 ? (($expensesThisPeriod - $expensesPrevPeriod) / $expensesPrevPeriod) * 100 : ($expensesThisPeriod > 0 ? 100 : 0);

        // 3. Production & Stock Alerts
        $prodThisPeriod = Production::where('company_id', $companyId)->whereBetween('production_date', [$startDate, $endDate])->sum('quantity');
        $lowStockMaterials = Material::where('company_id', $companyId)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->get();
        $lowStock = $lowStockMaterials->count();
        $totalMaterials = Material::where('company_id', $companyId)->count();
        $stockSafePercent = $totalMaterials > 0 ? round((($totalMaterials - $lowStock) / $totalMaterials) * 100) : 0;

        // 4. Recent Activities (Merged Sales, Production & Installment Payments)
        $latestSales = Sale::where('company_id', $companyId)->latest()->limit(5)->get()->map(function($sale) {
            $isDebt = $sale->payment_method === 'debt';
            return [
                'type' => 'sale',
                'title' => $isDebt ? "Penjualan Tempo #{$sale->id}" : "Penjualan #{$sale->id}",
                'amount' => 'Rp ' . number_format($sale->total, 0, ',', '.'),
                'time' => $sale->created_at,
                'icon' => $isDebt ? 'menu_book' : 'payments',
                'color' => $isDebt ? 'amber' : 'teal'
            ];
        });

        $latestProds = Production::with('product')->where('company_id', $companyId)->latest()->limit(5)->get()->map(function($prod) {
            return [
                'type' => 'production',
                'title' => "Produksi " . ($prod->product->name ?? 'Produk'),
                'amount' => "{$prod->quantity} Unit",
                'time' => $prod->created_at,
                'icon' => 'precision_manufacturing',
                'color' => 'indigo'
            ];
        });

        $latestPayments = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($pay) {
                return [
                    'type' => 'payment',
                    'title' => "Cicilan " . ($pay->debt->customer->name ?? 'Pelanggan'),
                    'amount' => 'Rp ' . number_format($pay->amount_paid, 0, ',', '.'),
                    'time' => $pay->created_at,
                    'icon' => 'price_check',
                    'color' => 'emerald'
                ];
            });

        $recentActivities = $latestSales->concat($latestProds)->concat($latestPayments)->sortByDesc('time')->take(5);

        // 5. Chart Data (Dynamic Grouping under Strict Cash Basis)
        $chartLabels = [];
        $chartSales = [];
        $chartExpenses = [];
        $chartType = $filter == '1' ? 'bar' : 'line';

        if ($filter == '1') {
            // Hourly grouping for Today (Cash Inflow = Cash Sales + Installment Payments)
            $salesTrend = Sale::where('company_id', $companyId)
                ->whereIn('payment_method', ['cash', 'transfer', 'qris'])
                ->whereDate('created_at', $now->toDateString())
                ->selectRaw('HOUR(created_at) as hour, SUM(total) as total')
                ->groupBy('hour')->get()->keyBy('hour');

            $paymentTrend = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->where('payment_date', $now->toDateString())
                ->selectRaw('HOUR(created_at) as hour, SUM(amount_paid) as total')
                ->groupBy('hour')->get()->keyBy('hour');
                
            $prodTrend = Production::where('company_id', $companyId)
                ->whereDate('production_date', $now->toDateString())
                ->selectRaw('HOUR(created_at) as hour, SUM(total_cost_snapshot) as total')
                ->groupBy('hour')->get()->keyBy('hour');
                
            $ohTrend = OverheadCost::where('company_id', $companyId)
                ->whereDate('transaction_date', $now->toDateString())
                ->selectRaw('HOUR(created_at) as hour, SUM(cost) as total')
                ->groupBy('hour')->get()->keyBy('hour');

            for ($i = 0; $i < 24; $i++) {
                $chartLabels[] = sprintf('%02d:00', $i);
                $chartSales[] = (float) ($salesTrend[$i]->total ?? 0) + (float) ($paymentTrend[$i]->total ?? 0);
                $chartExpenses[] = (float) ($prodTrend[$i]->total ?? 0) + (float) ($ohTrend[$i]->total ?? 0);
            }
        } else {
            // Daily grouping for Weekly/Monthly (Cash Inflow = Cash Sales + Installment Payments)
            $salesTrend = Sale::where('company_id', $companyId)
                ->whereIn('payment_method', ['cash', 'transfer', 'qris'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(total) as total')
                ->groupBy('date')->get()->keyBy('date');

            $paymentTrend = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->selectRaw('payment_date as date, SUM(amount_paid) as total')
                ->groupBy('date')->get()->keyBy('date');
                
            $prodTrend = Production::where('company_id', $companyId)
                ->whereBetween('production_date', [$startDate, $endDate])
                ->selectRaw('DATE(production_date) as date, SUM(total_cost_snapshot) as total')
                ->groupBy('date')->get()->keyBy('date');
                
            $ohTrend = OverheadCost::where('company_id', $companyId)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('DATE(transaction_date) as date, SUM(cost) as total')
                ->groupBy('date')->get()->keyBy('date');

            for ($i = $days; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->toDateString();
                $chartLabels[] = Carbon::parse($date)->format('d/m');
                $chartSales[] = (float) ($salesTrend[$date]->total ?? 0) + (float) ($paymentTrend[$date]->total ?? 0);
                $chartExpenses[] = (float) ($prodTrend[$date]->total ?? 0) + (float) ($ohTrend[$date]->total ?? 0);
            }
        }

        // 6. Cost Distribution
        $costDistribution = Production::where('company_id', $companyId)->whereBetween('production_date', [$startDate, $endDate])
            ->selectRaw('SUM(material_cost_snapshot) as material, SUM(labor_cost) as labor, SUM(overhead_cost_snapshot) as overhead')
            ->first();

        return view('DashboardUtama', [
            'companyName' => auth()->user()->company->name ?? 'UMKM Anda',
            'totalSales' => $salesThisPeriod,
            'salesGrowth' => $salesGrowth,
            'totalExpenses' => $expensesThisPeriod,
            'expenseGrowth' => $expenseGrowth,
            'totalProduction' => $prodThisPeriod,
            'lowStock' => $lowStock,
            'lowStockMaterials' => $lowStockMaterials,
            'stockSafePercent' => $stockSafePercent,
            'chartLabels' => $chartLabels,
            'chartSales' => $chartSales,
            'chartExpenses' => $chartExpenses,
            'chartType' => $chartType,
            'costDist' => [
                'material' => (float) ($costDistribution->material ?? 0),
                'labor' => (float) ($costDistribution->labor ?? 0),
                'overhead' => (float) ($costDistribution->overhead ?? 0),
            ],
            'aiInsight' => $this->generateAiInsight($salesThisPeriod, $expensesThisPeriod, $lowStock, $salesGrowth),
            'currentFilter' => $filter,
            'recentActivities' => $recentActivities,
            'targetDate' => $now->toDateString(),
            'isTimeTravel' => $isTimeTravel,
        ]);
    }

    private function generateAiInsight($sales, $expenses, $lowStock, $growth)
    {
        $margin = $sales > 0 ? (($sales - $expenses) / $sales) * 100 : 0;
        
        $insights = [];

        // Critical Alerts (Prioritized)
        if ($lowStock > 0) {
            $insights[] = "Krisis stok terdeteksi! Ada {$lowStock} bahan baku di bawah batas minimum. Segera belanja agar produksi tidak macet.";
        }
        
        if ($expenses > $sales && $sales > 0) {
            $insights[] = "Waspada kebocoran biaya: Pengeluaran Anda melampaui pendapatan. Cek kembali biaya overhead atau efisiensi penggunaan bahan baku.";
        }

        // Performance Insights
        if ($growth > 20) {
            $insights[] = "Luar biasa! Penjualan melonjak " . round($growth, 1) . "%. Pertimbangkan untuk menambah kapasitas produksi atau stok cadangan.";
        } elseif ($growth > 5) {
            $insights[] = "Tren positif terlihat. Penjualan tumbuh stabil. Pertahankan ritme pemasaran Anda saat ini.";
        } elseif ($growth < -10) {
            $insights[] = "Penjualan menurun dibanding periode lalu. Mungkin ini saatnya mencoba promo baru atau mengevaluasi harga jual produk.";
        }

        // Margin Analysis
        if ($margin > 30) {
            $insights[] = "Efisiensi bisnis Anda sangat baik dengan margin laba di atas 30%. Anda memiliki arus kas yang sehat untuk investasi alat baru.";
        } elseif ($margin < 10 && $sales > 0) {
            $insights[] = "Margin laba Anda tipis (di bawah 10%). Coba negosiasi ulang harga bahan baku atau tingkatkan harga jual produk unggulan.";
        }

        // Fallback/Generic (if no strong insights)
        if (empty($insights)) {
            $templates = [
                "Performa operasional Anda cukup stabil. Fokus pada konsistensi kualitas produk untuk menjaga loyalitas pelanggan.",
                "Stok aman dan keuangan terkendali. Gunakan waktu luang ini untuk merencanakan strategi pemasaran bulan depan.",
                "Data menunjukkan alur bisnis yang sehat. Pastikan pencatatan biaya operasional tetap disiplin setiap hari."
            ];
            return $templates[array_rand($templates)];
        }

        // Pick 1 random from qualified insights
        return $insights[array_rand($insights)];
    }
}

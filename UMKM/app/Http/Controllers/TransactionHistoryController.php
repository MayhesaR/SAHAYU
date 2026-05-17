<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Production;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        // General stats for cards
        $totalSalesAmount = Sale::where('company_id', $companyId)->sum('total');
        $totalSalesCount = Sale::where('company_id', $companyId)->count();
        $totalProductionCount = Production::where('company_id', $companyId)->count();

        // 1. Capture parameters
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $type = $request->query('type', 'all');
        $sortBy = $request->query('sort_by', 'transaction_date');

        // 2. Prepare Queries
        $salesQuery = Sale::where('company_id', $companyId);
        $prodsQuery = Production::with('product')->where('company_id', $companyId);
        $paymentsQuery = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->with(['debt.customer']);

        // 3. Apply Date Filters at DB level for efficiency
        if ($startDate) {
            $salesQuery->whereDate('created_at', '>=', $startDate);
            $prodsQuery->whereDate('production_date', '>=', $startDate);
            $paymentsQuery->whereDate('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('created_at', '<=', $endDate);
            $prodsQuery->whereDate('production_date', '<=', $endDate);
            $paymentsQuery->whereDate('payment_date', '<=', $endDate);
        }

        // 4. Fetch logs (up to 500 of each type to optimize performance)
        $sales = $salesQuery->latest()->limit(500)->get();
        $prods = $prodsQuery->latest()->limit(500)->get();
        $payments = $paymentsQuery->latest()->limit(500)->get();

        // 5. Map records into structured array formats
        $salesItems = $sales->map(function ($sale) {
            $isDebt = $sale->payment_method === 'debt';
            return [
                'type' => 'sale',
                'subtype' => $isDebt ? 'sale_debt' : 'sale_cash',
                'id' => $sale->id,
                'title' => $isDebt ? "Penjualan Tempo #{$sale->id}" : "Penjualan #{$sale->id}",
                'raw_amount' => (float) $sale->total,
                'amount' => 'Rp ' . number_format($sale->total, 0, ',', '.'),
                'time' => $sale->created_at,
                'transaction_date' => $sale->created_at,
                'created_at' => $sale->created_at,
                'icon' => $isDebt ? 'menu_book' : 'payments',
                'color' => $isDebt ? 'amber' : 'teal',
                'status' => $sale->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                'status_color' => $sale->status === 'paid' ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700',
                'details' => $sale->customer ? "Pelanggan: {$sale->customer}" : "Umum",
                'customer_name' => $sale->customer ?? '',
            ];
        });

        $prodsItems = $prods->map(function ($prod) {
            return [
                'type' => 'production',
                'subtype' => 'production',
                'id' => $prod->id,
                'title' => "Produksi " . ($prod->product->name ?? 'Produk'),
                'raw_amount' => (float) $prod->total_cost_snapshot,
                'amount' => "{$prod->quantity} Unit",
                'time' => $prod->created_at,
                'transaction_date' => \Carbon\Carbon::parse($prod->production_date)->startOfDay(),
                'created_at' => $prod->created_at,
                'icon' => 'precision_manufacturing',
                'color' => 'indigo',
                'status' => ucfirst($prod->status),
                'status_color' => $prod->status === 'completed' 
                    ? 'bg-teal-100 text-teal-700' 
                    : ($prod->status === 'running' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700'),
                'details' => "HPP: Rp " . number_format($prod->total_cost_snapshot, 0, ',', '.'),
                'customer_name' => '',
            ];
        });

        $paymentsItems = $payments->map(function ($pay) {
            $customerName = $pay->debt->customer->name ?? 'Pelanggan';
            return [
                'type' => 'payment',
                'subtype' => 'payment',
                'id' => $pay->id,
                'title' => "Cicilan Piutang",
                'raw_amount' => (float) $pay->amount_paid,
                'amount' => 'Rp ' . number_format($pay->amount_paid, 0, ',', '.'),
                'time' => \Carbon\Carbon::parse($pay->payment_date),
                'transaction_date' => \Carbon\Carbon::parse($pay->payment_date)->startOfDay(),
                'created_at' => $pay->created_at,
                'icon' => 'price_check',
                'color' => 'blue',
                'status' => 'Diterima',
                'status_color' => 'bg-blue-100 text-blue-700',
                'details' => "Pembayaran dari {$customerName}",
                'customer_name' => $customerName,
            ];
        });

        // 6. Concatenate collections
        $merged = $salesItems->concat($prodsItems)->concat($paymentsItems);

        // 7. Apply Search query
        if ($search) {
            $merged = $merged->filter(function ($item) use ($search) {
                return stripos((string)$item['id'], $search) !== false
                    || stripos($item['customer_name'], $search) !== false
                    || stripos($item['details'], $search) !== false
                    || stripos($item['title'], $search) !== false;
            });
        }

        // 8. Apply Subtype filtering
        if ($type !== 'all') {
            if ($type === 'sale_cash') {
                $merged = $merged->where('subtype', 'sale_cash');
            } elseif ($type === 'sale_debt') {
                $merged = $merged->where('subtype', 'sale_debt');
            } elseif ($type === 'payment') {
                $merged = $merged->where('type', 'payment');
            } elseif ($type === 'production') {
                $merged = $merged->where('type', 'production');
            }
        }

        // 9. Apply UX Advanced Sorting
        if ($sortBy === 'input_time') {
            // Strictly by database input created_at timestamp
            $merged = $merged->sortByDesc(function ($item) {
                return $item['created_at']->timestamp;
            });
        } elseif ($sortBy === 'amount') {
            // Sort by size of transaction
            $merged = $merged->sortByDesc('raw_amount');
        } else {
            // Default: Actual transaction date (transaction_date), tie-broken by input time
            $merged = $merged->sort(function ($a, $b) {
                $timeA = $a['transaction_date'];
                $timeB = $b['transaction_date'];
                if ($timeA->equalTo($timeB)) {
                    return $b['created_at']->timestamp <=> $a['created_at']->timestamp;
                }
                return $timeB->timestamp <=> $timeA->timestamp;
            });
        }

        // 10. Paginate Collection
        $perPage = 15;
        $page = $request->query('page', 1);
        $sliced = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        $logs = new LengthAwarePaginator(
            $sliced,
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('RiwayatTransaksi', [
            'logs' => $logs,
            'currentType' => $type,
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sortBy' => $sortBy,
            'totalSalesAmount' => $totalSalesAmount,
            'totalSalesCount' => $totalSalesCount,
            'totalProductionCount' => $totalProductionCount,
        ]);
    }
}

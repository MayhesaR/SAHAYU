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
        $expensesQuery = \App\Models\Expense::where('company_id', $companyId);
        $purchasesQuery = \App\Models\Purchase::where('company_id', $companyId);

        // 3. Apply Date Filters at DB level for efficiency
        if ($startDate) {
            $salesQuery->whereDate('created_at', '>=', $startDate);
            $prodsQuery->whereDate('production_date', '>=', $startDate);
            $paymentsQuery->whereDate('payment_date', '>=', $startDate);
            $expensesQuery->whereDate('expense_date', '>=', $startDate);
            $purchasesQuery->whereDate('purchase_date', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('created_at', '<=', $endDate);
            $prodsQuery->whereDate('production_date', '<=', $endDate);
            $paymentsQuery->whereDate('payment_date', '<=', $endDate);
            $expensesQuery->whereDate('expense_date', '<=', $endDate);
            $purchasesQuery->whereDate('purchase_date', '<=', $endDate);
        }

        // 4. Fetch logs (up to 500 of each type to optimize performance)
        $sales = $salesQuery->latest()->limit(500)->get();
        $prods = $prodsQuery->latest()->limit(500)->get();
        $payments = $paymentsQuery->latest()->limit(500)->get();
        $expenses = $expensesQuery->latest()->limit(500)->get();
        $purchases = $purchasesQuery->latest()->limit(500)->get();

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

        $expensesItems = $expenses->map(function ($exp) {
            $parsedDate = \Carbon\Carbon::parse($exp->expense_date);
            if ($exp->created_at) {
                $parsedDate->setTimeFrom($exp->created_at);
            }
            return [
                'type' => 'expense',
                'subtype' => 'expense',
                'id' => $exp->id,
                'title' => "Pengeluaran Operasional (" . $exp->category . ")",
                'raw_amount' => (float) $exp->amount,
                'amount' => '- Rp ' . number_format($exp->amount, 0, ',', '.'),
                'time' => $parsedDate,
                'transaction_date' => \Carbon\Carbon::parse($exp->expense_date)->startOfDay(),
                'created_at' => $exp->created_at,
                'icon' => 'shopping_cart',
                'color' => 'rose',
                'status' => 'Kas Keluar',
                'status_color' => 'bg-rose-100 text-rose-700',
                'details' => $exp->description ?: 'Tanpa keterangan',
                'customer_name' => '',
            ];
        });

        $purchasesItems = $purchases->map(function ($pur) {
            $parsedDate = \Carbon\Carbon::parse($pur->purchase_date);
            if ($pur->created_at) {
                $parsedDate->setTimeFrom($pur->created_at);
            }
            return [
                'type' => 'purchase',
                'subtype' => 'purchase',
                'id' => $pur->id,
                'title' => "Belanja Bahan Baku",
                'raw_amount' => (float) $pur->total_amount,
                'amount' => '- Rp ' . number_format($pur->total_amount, 0, ',', '.'),
                'time' => $parsedDate,
                'transaction_date' => \Carbon\Carbon::parse($pur->purchase_date)->startOfDay(),
                'created_at' => $pur->created_at,
                'icon' => 'shopping_bag',
                'color' => 'rose',
                'status' => 'Kas Keluar',
                'status_color' => 'bg-rose-100 text-rose-700',
                'details' => $pur->description ?: 'Pembelian bahan baku restok',
                'customer_name' => '',
            ];
        });

        // 6. Concatenate collections
        $merged = $salesItems->concat($prodsItems)->concat($paymentsItems)->concat($expensesItems)->concat($purchasesItems);

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
            } elseif ($type === 'expense') {
                $merged = $merged->where('type', 'expense');
            } elseif ($type === 'purchase') {
                $merged = $merged->where('type', 'purchase');
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

    public function export(Request $request)
    {
        $companyId = auth()->user()->company_id;

        // 1. Capture parameters
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $type = $request->query('type', 'all');
        $sortBy = $request->query('sort_by', 'transaction_date');

        // 2. Prepare Queries
        $salesQuery = Sale::with(['items.product'])->where('company_id', $companyId);
        $prodsQuery = Production::with('product')->where('company_id', $companyId);
        $paymentsQuery = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->with(['debt.customer']);
        $expensesQuery = \App\Models\Expense::where('company_id', $companyId);
        $purchasesQuery = \App\Models\Purchase::where('company_id', $companyId);

        // 3. Apply Date Filters
        if ($startDate) {
            $salesQuery->whereDate('created_at', '>=', $startDate);
            $prodsQuery->whereDate('production_date', '>=', $startDate);
            $paymentsQuery->whereDate('payment_date', '>=', $startDate);
            $expensesQuery->whereDate('expense_date', '>=', $startDate);
            $purchasesQuery->whereDate('purchase_date', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('created_at', '<=', $endDate);
            $prodsQuery->whereDate('production_date', '<=', $endDate);
            $paymentsQuery->whereDate('payment_date', '<=', $endDate);
            $expensesQuery->whereDate('expense_date', '<=', $endDate);
            $purchasesQuery->whereDate('purchase_date', '<=', $endDate);
        }

        // 4. Fetch all (no pagination for complete download)
        $sales = $salesQuery->latest()->get();
        $prods = $prodsQuery->latest()->get();
        $payments = $paymentsQuery->latest()->get();
        $expenses = $expensesQuery->latest()->get();
        $purchases = $purchasesQuery->latest()->get();

        // 5. Map records into structured export format
        $salesItems = $sales->map(function ($sale) {
            $isDebt = $sale->payment_method === 'debt';
            
            // Context items list
            $productNames = [];
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $productNames[] = $item->product->name . ' (' . $item->quantity . 'x)';
                }
            }
            $rincianKonten = !empty($productNames) ? implode(', ', $productNames) : 'Transaksi Kasir POS';

            return [
                'tanggal' => $sale->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB',
                'nota_id' => '#' . str_pad($sale->id, 5, '0', STR_PAD_LEFT),
                'kategori' => $isDebt ? 'Piutang' : 'Penjualan Tunai',
                'identitas' => $sale->customer ?? 'Umum',
                'rincian' => $rincianKonten,
                'uang_masuk' => (int) $sale->total,
                'uang_keluar' => 0,
                'status' => $sale->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                'type' => 'sale',
                'subtype' => $isDebt ? 'sale_debt' : 'sale_cash',
                'raw_amount' => (float) $sale->total,
                'created_at' => $sale->created_at,
                'transaction_date' => $sale->created_at,
                'customer_name' => $sale->customer ?? '',
                'details' => $rincianKonten,
            ];
        });

        $prodsItems = $prods->map(function ($prod) {
            return [
                'tanggal' => \Carbon\Carbon::parse($prod->production_date)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB',
                'nota_id' => '#' . str_pad($prod->id, 5, '0', STR_PAD_LEFT),
                'kategori' => 'Produksi',
                'identitas' => 'Produksi Internal',
                'rincian' => "Produksi " . ($prod->product->name ?? 'Produk') . " (" . $prod->quantity . " Unit)",
                'uang_masuk' => 0,
                'uang_keluar' => (int) $prod->total_cost_snapshot,
                'status' => ucfirst($prod->status),
                'type' => 'production',
                'subtype' => 'production',
                'raw_amount' => (float) $prod->total_cost_snapshot,
                'created_at' => $prod->created_at,
                'transaction_date' => \Carbon\Carbon::parse($prod->production_date)->startOfDay(),
                'customer_name' => '',
                'details' => "Produksi " . ($prod->product->name ?? 'Produk'),
            ];
        });

        $paymentsItems = $payments->map(function ($pay) {
            $customerName = $pay->debt->customer->name ?? 'Pelanggan';
            return [
                'tanggal' => \Carbon\Carbon::parse($pay->payment_date)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB',
                'nota_id' => '#' . str_pad($pay->id, 5, '0', STR_PAD_LEFT),
                'kategori' => 'Cicilan',
                'identitas' => $customerName,
                'rincian' => "Pembayaran cicilan angsuran",
                'uang_masuk' => (int) $pay->amount_paid,
                'uang_keluar' => 0,
                'status' => 'Lunas',
                'type' => 'payment',
                'subtype' => 'payment',
                'raw_amount' => (float) $pay->amount_paid,
                'created_at' => $pay->created_at,
                'transaction_date' => \Carbon\Carbon::parse($pay->payment_date)->startOfDay(),
                'customer_name' => $customerName,
                'details' => "Pembayaran cicilan angsuran",
            ];
        });

        $expensesItems = $expenses->map(function ($exp) {
            $parsedDate = \Carbon\Carbon::parse($exp->expense_date);
            if ($exp->created_at) {
                $parsedDate->setTimeFrom($exp->created_at);
            }
            return [
                'tanggal' => $parsedDate->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB',
                'nota_id' => '#' . str_pad($exp->id, 5, '0', STR_PAD_LEFT),
                'kategori' => 'Pengeluaran',
                'identitas' => $exp->category,
                'rincian' => $exp->description ?: 'Pengeluaran Operasional',
                'uang_masuk' => 0,
                'uang_keluar' => (int) $exp->amount,
                'status' => 'Paid',
                'type' => 'expense',
                'subtype' => 'expense',
                'raw_amount' => (float) $exp->amount,
                'created_at' => $exp->created_at,
                'transaction_date' => \Carbon\Carbon::parse($exp->expense_date)->startOfDay(),
                'customer_name' => '',
                'details' => $exp->description ?: 'Pengeluaran Operasional',
            ];
        });

        $purchasesItems = $purchases->map(function ($pur) {
            $parsedDate = \Carbon\Carbon::parse($pur->purchase_date);
            if ($pur->created_at) {
                $parsedDate->setTimeFrom($pur->created_at);
            }
            return [
                'tanggal' => $parsedDate->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB',
                'nota_id' => '#' . str_pad($pur->id, 5, '0', STR_PAD_LEFT),
                'kategori' => 'Belanja Bahan',
                'identitas' => 'Restok Bahan Baku',
                'rincian' => $pur->description ?: 'Belanja restok bahan baku',
                'uang_masuk' => 0,
                'uang_keluar' => (int) $pur->total_amount,
                'status' => 'Paid',
                'type' => 'purchase',
                'subtype' => 'purchase',
                'raw_amount' => (float) $pur->total_amount,
                'created_at' => $pur->created_at,
                'transaction_date' => \Carbon\Carbon::parse($pur->purchase_date)->startOfDay(),
                'customer_name' => '',
                'details' => $pur->description ?: 'Belanja restok bahan baku',
            ];
        });

        // 6. Concatenate collections
        $merged = $salesItems->concat($prodsItems)->concat($paymentsItems)->concat($expensesItems)->concat($purchasesItems);

        // 7. Apply Search query
        if ($search) {
            $merged = $merged->filter(function ($item) use ($search) {
                return stripos((string)$item['nota_id'], $search) !== false
                    || stripos($item['customer_name'], $search) !== false
                    || stripos($item['details'], $search) !== false
                    || stripos($item['identitas'], $search) !== false
                    || stripos($item['rincian'], $search) !== false
                    || stripos($item['kategori'], $search) !== false;
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
            } elseif ($type === 'expense') {
                $merged = $merged->where('type', 'expense');
            } elseif ($type === 'purchase') {
                $merged = $merged->where('type', 'purchase');
            }
        }

        // 9. Apply UX Advanced Sorting
        if ($sortBy === 'input_time') {
            $merged = $merged->sortByDesc(function ($item) {
                return $item['created_at']->timestamp;
            });
        } elseif ($sortBy === 'amount') {
            $merged = $merged->sortByDesc('raw_amount');
        } else {
            $merged = $merged->sort(function ($a, $b) {
                $timeA = $a['transaction_date'];
                $timeB = $b['transaction_date'];
                if ($timeA->equalTo($timeB)) {
                    return $b['created_at']->timestamp <=> $a['created_at']->timestamp;
                }
                return $timeB->timestamp <=> $timeA->timestamp;
            });
        }

        // 10. Generate CSV Stream Download
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=riwayat-transaksi-" . now()->format('Y-m-d') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($merged) {
            $file = fopen('php://output', 'w');

            // Header CSV (BOM untuk dukungan UTF-8 di Excel)
            fputs($file, "\xEF\xBB\xBF");
            
            // Header columns
            fputcsv($file, [
                'Tanggal & Waktu',
                'Nomor Nota / ID',
                'Kategori Transaksi',
                'Identitas (Nama Pelanggan / Deskripsi Objek)',
                'Rincian Konten',
                'Uang Masuk (+ Rp)',
                'Uang Keluar (- Rp)',
                'Status Pembayaran'
            ], ';');

            foreach ($merged as $row) {
                fputcsv($file, [
                    $row['tanggal'],
                    $row['nota_id'],
                    $row['kategori'],
                    $row['identitas'],
                    $row['rincian'],
                    $row['uang_masuk'],
                    $row['uang_keluar'],
                    $row['status']
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

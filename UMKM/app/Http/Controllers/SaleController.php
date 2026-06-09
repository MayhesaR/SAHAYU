<?php

namespace App\Http\Controllers;

use App\Events\ProductSold;
use App\Events\StockLowAlert;
use App\Events\SalesAnalyticsUpdated;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Debt;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $todaySales = Sale::with(['items.product'])
            ->whereDate('created_at', $today)
            ->latest()
            ->get();

        $todayUnits = (int) SaleItem::whereIn('sale_id', $todaySales->pluck('id'))->sum('quantity');

        $salesHistory = Sale::with(['items.product'])
            ->filterSortPaginate(
                $request,
                searchableColumns: ['customer', 'payment_method', 'items.product.name'],
                filterableColumns: ['payment_method', 'status'],
                defaultSort: 'created_at',
                defaultOrder: 'desc',
                perPage: 15,
                dateColumn: 'created_at',
            );

        $companyId = auth()->user()->company_id;
        $topProductsRaw = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', $today)
            ->where('sales.company_id', $companyId)
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total'))
            ->groupBy('sale_items.product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topProductNames = Product::whereIn('id', $topProductsRaw->pluck('product_id')->all())
            ->pluck('name', 'id');

        $topProducts = $topProductsRaw->map(fn ($item) => [
            'id' => $item->product_id,
            'name' => $topProductNames[$item->product_id] ?? '-',
            'qty' => (int) $item->total,
        ])->toArray();

        return view('PencatatanPenjualan', [
            'products' => Product::with('category')->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'todayRevenue' => $todaySales->sum('total'),
            'todayTransactions' => $todaySales->count(),
            'todayUnits' => $todayUnits,
            'todaySales' => $todaySales->take(5),
            'salesHistory' => $salesHistory,
            'topProducts' => $topProducts,
            'businessTip' => $this->generateDynamicTip(),
        ]);
    }

    private function generateDynamicTip()
    {
        $lowStockProduct = Product::whereColumn('stock', '<=', 'minimum_stock')->first();
        if ($lowStockProduct) {
            return [
                'title' => 'Stok Kritis!',
                'content' => "Stok {$lowStockProduct->name} sisa {$lowStockProduct->stock} unit. Segera restock agar tidak kehilangan potensi penjualan.",
                'icon' => 'warning'
            ];
        }

        $popular = SaleItem::select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->first();

        if ($popular) {
            $product = Product::find($popular->product_id);
            if ($product) {
                return [
                    'title' => 'Produk Terlaris',
                    'content' => "{$product->name} adalah bintang Anda! Pertimbangkan untuk membuat paket bundling dengan produk lain.",
                    'icon' => 'star'
                ];
            }
        }

        $qrisUsage = Sale::where('payment_method', 'qris')->count();
        if ($qrisUsage < 5) {
            return [
                'title' => 'Tips Pembayaran',
                'content' => "Promosikan pembayaran QRIS untuk mempercepat proses transaksi di kasir.",
                'icon' => 'qr_code_2'
            ];
        }

        return [
            'title' => 'Tips Harian',
            'content' => "Selalu catat setiap transaksi sekecil apa pun untuk akurasi laporan laba rugi di akhir bulan.",
            'icon' => 'lightbulb'
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        // Backward compatibility for single product submissions
        if (!$request->has('items') && $request->has('product_id')) {
            $request->merge([
                'items' => [
                    [
                        'product_id' => $request->input('product_id'),
                        'quantity' => $request->input('quantity', 1)
                    ]
                ]
            ]);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => ['required', 'in:cash,transfer,qris,debt'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        if ($validated['payment_method'] === 'debt') {
            if (empty($validated['customer_id'])) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Pelanggan wajib dipilih jika metode pembayaran adalah Piutang / Kasbon.',
                ]);
            }
            if (empty($validated['due_date'])) {
                throw ValidationException::withMessages([
                    'due_date' => 'Tanggal jatuh tempo wajib diisi jika metode pembayaran adalah Piutang / Kasbon.',
                ]);
            }
        }

        $customerName = 'Pelanggan Umum';
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            if ($customer) {
                $customerName = $customer->name;
            }
        }

        $createdSaleId = null;

        DB::transaction(function () use ($validated, $customerName, &$createdSaleId) {
            $total = 0;
            $itemsData = [];

            foreach ($validated['items'] as $itemInput) {
                $freshProduct = Product::query()->lockForUpdate()->findOrFail($itemInput['product_id']);
                $quantity = (int) $itemInput['quantity'];

                if ((int) $freshProduct->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Stok produk "'.$freshProduct->name.'" tidak mencukupi.',
                    ]);
                }

                $unitPrice = (float) ($freshProduct->selling_price ?? 0);
                $subtotal = $unitPrice * $quantity;
                $total += $subtotal;

                $itemsData[] = [
                    'product' => $freshProduct,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                ];
            }

            $sale = Sale::create([
                'company_id' => auth()->user()->company_id,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer' => $customerName,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'status' => $validated['payment_method'] === 'debt' ? 'unpaid' : 'paid',
            ]);

            $createdSaleId = $sale->id;

            if ($validated['payment_method'] === 'debt') {
                Debt::create([
                    'company_id' => auth()->user()->company_id,
                    'customer_id' => $validated['customer_id'],
                    'sale_id' => $sale->id,
                    'total_amount' => $total,
                    'remaining_amount' => $total,
                    'due_date' => $validated['due_date'],
                    'status' => 'unpaid',
                ]);
            }

            foreach ($itemsData as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];

                $before = (int) $product->stock;
                $after = $before - $quantity;

                $product->update(['stock' => $after]);

                $product->stockMovements()->create([
                    'type' => 'out',
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $product->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Penjualan',
                    'note' => 'Pengurangan stok barang jadi karena penjualan.',
                ]);

                SaleItem::create([
                    'company_id' => auth()->user()->company_id,
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                ]);

                $this->dispatchEvent(new ProductSold(
                    productId: (int) $product->id,
                    qtyDeducted: $quantity,
                ));

                if ($after <= (int) ($product->minimum_stock ?? 0)) {
                    $this->dispatchEvent(new StockLowAlert(
                        productId: (int) $product->id,
                        currentStock: (float) $after,
                        minimumThreshold: (float) ($product->minimum_stock ?? 0),
                        itemType: 'product'
                    ));
                }
            }
        });

        $todayRevenue = Sale::whereDate('created_at', Carbon::today())->sum('total');
        $todayTransactions = Sale::whereDate('created_at', Carbon::today())->count();
        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', Carbon::today())
            ->where('sales.company_id', auth()->user()->company_id)
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total'))
            ->groupBy('sale_items.product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $productNames = Product::whereIn('id', $topProducts->pluck('product_id')->all())
            ->pluck('name', 'id');

        $topProducts = $topProducts->map(fn ($item) => [
            'id' => $item->product_id,
            'name' => $productNames[$item->product_id] ?? '-',
            'qty' => $item->total,
        ])->toArray();

        $latestSale = null;
        if ($createdSaleId) {
            $sale = Sale::with(['items.product'])->find($createdSaleId);
            if ($sale) {
                $latestSale = [
                    'id' => (int) $sale->id,
                    'timestamp' => $sale->created_at->timestamp,
                    'time' => $sale->created_at->format('H:i'),
                    'full_time' => $sale->created_at->format('d M Y H:i'),
                    'product' => $sale->items->count() > 1 
                        ? $sale->items->first()?->product?->name . ' +' . ($sale->items->count() - 1) . ' item lainnya'
                        : ($sale->items->first()?->product?->name ?? '-'),
                    'qty' => (int) $sale->items->sum('quantity'),
                    'customer' => $sale->customer ?: 'Walk-in',
                    'payment_method' => strtoupper((string) $sale->payment_method),
                    'payment_dot_class' => $sale->payment_method === 'cash'
                        ? 'bg-amber-500'
                        : ($sale->payment_method === 'qris' ? 'bg-emerald-500' : 'bg-blue-500'),
                    'total' => (float) $sale->total,
                    'status' => (string) $sale->status,
                    'status_label' => $sale->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                    'status_class' => $sale->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700',
                    'can_delete' => auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isStaff()),
                    'destroy_url' => route('sales.destroy', $sale),
                ];
            }
        }

        $this->dispatchEvent(new SalesAnalyticsUpdated(
            totalSales: (float) SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereDate('sales.created_at', Carbon::today())
                ->sum('sale_items.quantity'),
            totalTransactions: $todayTransactions,
            totalRevenue: $todayRevenue,
            topProducts: $topProducts,
            latestSale: $latestSale
        ));

        return redirect()->route('sales.index')
            ->with('success', 'Transaksi berhasil dicatat.')
            ->with('print_sale_id', $createdSaleId);
    }

    public function showReceipt(Sale $sale)
    {
        if ($sale->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized.');
        }

        $sale->load(['items.product', 'customer']);

        return view('sales.receipt', [
            'sale' => $sale,
            'company' => auth()->user()->company,
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        Customer::create([
            'company_id' => auth()->user()->company_id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Pelanggan baru berhasil disimpan.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        DB::transaction(function () use ($sale): void {
            $sale->loadMissing('items.product');

            foreach ($sale->items as $item) {
                if (! $item->product) {
                    continue;
                }

                $freshProduct = Product::query()->lockForUpdate()->findOrFail($item->product->id);
                $before = (int) $freshProduct->stock;
                $after = $before + (int) $item->quantity;

                $freshProduct->update(['stock' => $after]);

                $freshProduct->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => (int) $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshProduct->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Rollback penjualan #'.$sale->id,
                    'note' => 'Pengembalian stok barang jadi karena transaksi dihapus.',
                ]);
            }

            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function exportPdf()
    {
        $sales = Sale::with('items.product')->orderByDesc('created_at')->limit(100)->get();

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.export_pdf', [
                'sales' => $sales,
            ]);

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOption('dpi', 96);
            $pdf->setOption('enable_font_subsetting', true);

            return $pdf->download('laporan-penjualan-'.now()->format('Y-m-d').'.pdf');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['pdf' => 'Gagal generate PDF: ' . $e->getMessage()]);
        }
    }

    public function exportGoogleSheets(\App\Services\SpreadsheetExportService $exportService)
    {
        try {
            $sales = Sale::with('items.product')->orderByDesc('created_at')->get();

            $headers = ['No Transaksi', 'Tanggal', 'Pelanggan', 'Metode Bayar', 'Produk', 'Jumlah (Pcs)', 'Total (Rp)'];
            $data = [];

            foreach ($sales as $s) {
                $products = $s->items->map(function ($item) {
                    return ($item->product ? $item->product->name : '-') . ' ('.$item->quantity.')';
                })->join(', ');

                $totalQty = $s->items->sum('quantity');

                $data[] = [
                    $s->id,
                    $s->created_at->format('Y-m-d H:i'),
                    $s->customer ?: 'Umum',
                    strtoupper($s->payment_method),
                    $products,
                    $totalQty,
                    number_format($s->total, 0, ',', '')
                ];
            }

            $options = [
                'statistics' => [
                    6 => 'Total Omzet (Rp)',
                    5 => 'Total Item Terjual'
                ]
            ];

            return $exportService->exportAsXlsx('Riwayat-Penjualan-UMKM-Pancasila', $headers, $data, $options);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export Spreadsheet Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export spreadsheet: ' . $e->getMessage()]);
        }
    }
}

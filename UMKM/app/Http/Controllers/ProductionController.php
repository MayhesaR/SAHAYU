<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Product;
use App\Models\Production;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Events\ProductSold;
use App\Events\ProductionStatusUpdated;
use App\Events\MaterialUsed;
use App\Events\StockLowAlert;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->get();
        $materials = Material::orderBy('name')->get();

        $runningProductions = Production::with('product')
            ->filterSortPaginate(
                $request,
                searchableColumns: ['batch_code', 'supervisor_name', 'product.name'],
                filterableColumns: ['status'],
                defaultSort: 'production_date',
                defaultOrder: 'desc',
                perPage: 10,
                dateColumn: 'production_date',
            );

        $companyId = auth()->user()->company_id;
        $materialCost = DB::table('production_materials')
            ->join('materials', 'materials.id', '=', 'production_materials.material_id')
            ->where('production_materials.company_id', $companyId)
            ->selectRaw('SUM(production_materials.quantity * materials.price) as total')
            ->value('total') ?? 0;

        $overheadCost = OverheadCost::sum('cost');

        $today = now()->toDateString();
        $batchesToday = Production::whereDate('production_date', $today)->count();
        $doneBatchesToday = Production::whereDate('production_date', $today)->where('status', 'done')->count();
        $avgYieldToday = Production::whereDate('production_date', $today)
            ->selectRaw('AVG(CASE WHEN quantity > 0 THEN (good_quantity * 100.0 / quantity) ELSE 0 END) as yield_avg')
            ->value('yield_avg') ?? 0;
        $avgHpp = Production::where('unit_hpp_snapshot', '>', 0)->avg('unit_hpp_snapshot') ?? 0;

        return view('InputProduksi', [
            'products' => $products,
            'materials' => $materials,
            'runningProductions' => $runningProductions,
            'stockAlertMaterial' => Material::orderBy('stock')->first(),
            'materialCostEstimate' => $materialCost,
            'overheadCostEstimate' => $overheadCost,
            'totalProductionEstimate' => $materialCost + $overheadCost,
            'batchesToday' => $batchesToday,
            'doneBatchesToday' => $doneBatchesToday,
            'avgYieldToday' => (float) $avgYieldToday,
            'avgHpp' => (float) $avgHpp,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'production_date' => ['required', 'date'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reject_quantity' => ['nullable', 'integer', 'min:0'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'overhead_cost_snapshot' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:process,done,cancelled'],
        ]);

        $materialsInput = collect($request->input('materials', []))
            ->filter(function (array $material) {
                return filled($material['material_id'] ?? null) && filled($material['quantity'] ?? null);
            })
            ->values()
            ->all();

        if ($materialsInput === []) {
            return back()
                ->withErrors(['materials' => 'Minimal satu bahan baku harus diisi.'])
                ->withInput();
        }

        $production = null;

        $duplicateMaterialIds = collect($materialsInput)
            ->pluck('material_id')
            ->filter()
            ->countBy()
            ->filter(fn ($count) => $count > 1);

        if ($duplicateMaterialIds->isNotEmpty()) {
            return back()
                ->withErrors(['materials' => 'Satu bahan baku tidak boleh dipakai dua kali dalam batch yang sama.'])
                ->withInput();
        }

        foreach ($materialsInput as $index => $materialInput) {
            $materialValidator = Validator::make($materialInput, [
                'material_id' => ['required', 'exists:materials,id'],
                'quantity' => ['required', 'numeric', 'min:0.0001'],
            ]);

            if ($materialValidator->fails()) {
                return back()
                    ->withErrors($materialValidator, 'materials')
                    ->withInput();
            }
        }

        DB::transaction(function () use ($validated, $materialsInput, &$production) {
            $materialIds = collect($materialsInput)
                ->pluck('material_id')
                ->unique()
                ->values();

            $lockedMaterials = Material::query()
                ->whereIn('id', $materialIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($materialsInput as $material) {
                $materialModel = $lockedMaterials->get((int) $material['material_id']);

                if (! $materialModel) {
                    throw ValidationException::withMessages([
                        'materials' => 'Ada bahan baku yang tidak ditemukan.',
                    ]);
                }

                if ((float) $materialModel->stock < (float) $material['quantity']) {
                    throw ValidationException::withMessages([
                        'materials' => 'Stok bahan "'.$materialModel->name.'" tidak mencukupi untuk produksi.',
                    ]);
                }
            }

            $materialCostSnapshot = collect($materialsInput)->sum(function (array $material) use ($lockedMaterials): float {
                $materialModel = $lockedMaterials->get((int) $material['material_id']);

                return (float) ((float) $material['quantity'] * (float) ($materialModel?->price ?? 0));
            });

            $rejectQuantity = min((int) ($validated['reject_quantity'] ?? 0), (int) $validated['quantity']);
            $goodQuantity = max(0, (int) $validated['quantity'] - $rejectQuantity);

            $laborCost = (float) ($validated['labor_cost'] ?? 0);
            $overheadCost = (float) ($validated['overhead_cost_snapshot'] ?? 0);
            $totalCost = $materialCostSnapshot + $laborCost + $overheadCost;
            $unitHpp = $goodQuantity > 0 ? $totalCost / $goodQuantity : 0;

            $status = $validated['status'] ?? 'process';
            $batchCode = 'PRD-'.now()->format('YmdHis').'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $production = Production::create([
                'batch_code' => $batchCode,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'good_quantity' => $goodQuantity,
                'reject_quantity' => $rejectQuantity,
                'supervisor_name' => $validated['supervisor_name'] ?? null,
                'production_date' => $validated['production_date'],
                'status' => $status,
                'material_cost_snapshot' => $materialCostSnapshot,
                'labor_cost' => $laborCost,
                'overhead_cost_snapshot' => $overheadCost,
                'total_cost_snapshot' => $totalCost,
                'unit_hpp_snapshot' => $unitHpp,
                'completed_at' => $status === 'done' ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $syncData = [];
            $companyId = auth()->user()->company_id;

            foreach ($materialsInput as $material) {
                $syncData[$material['material_id']] = [
                    'quantity' => $material['quantity'],
                    'company_id' => $companyId
                ];
            }

            $production->materials()->sync($syncData);

            foreach ($materialsInput as $material) {
                $materialId = (int) $material['material_id'];
                $qtyUsed = (float) $material['quantity'];

                /** @var Material $materialModel */
                $materialModel = $lockedMaterials->get($materialId);

                $before = (float) $materialModel->stock;
                $after = $before - $qtyUsed;

                $materialModel->update([
                    'stock' => $after,
                ]);

                $materialModel->stockMovements()->create([
                    'type' => 'out',
                    'quantity' => $qtyUsed,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $materialModel->price,
                    'transaction_date' => $validated['production_date'],
                    'reference' => 'Produksi #'.$production->id,
                    'note' => 'Pemakaian bahan untuk batch produksi '.$production->id,
                ]);

                $materialModel->stock = $after;
            }

            if ($status === 'done' && $goodQuantity > 0) {
                $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
                $before = (int) $product->stock;
                $after = $before + $goodQuantity;

                $product->update(['stock' => $after]);

                $product->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => $goodQuantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $product->selling_price,
                    'transaction_date' => $validated['production_date'],
                    'reference' => 'Produksi '.$production->batch_code,
                    'note' => 'Barang jadi masuk dari batch produksi.',
                ]);
            }
        });

        $status = $validated['status'] ?? 'process';
        $goodQuantity = (int) ($production?->good_quantity ?? 0);

        foreach ($materialsInput as $materialInput) {
            $this->dispatchEvent(new MaterialUsed(
                materialId: (int) $materialInput['material_id'],
                quantityUsed: (float) $materialInput['quantity'],
                productionId: (int) ($production?->id ?? 0)
            ));

            $updatedMaterial = Material::find((int) $materialInput['material_id']);
            if ($updatedMaterial && (float) $updatedMaterial->stock <= (float) ($updatedMaterial->minimum_stock ?? 0)) {
                $this->dispatchEvent(new StockLowAlert(
                    productId: (int) $updatedMaterial->id,
                    currentStock: (float) $updatedMaterial->stock,
                    minimumThreshold: (float) ($updatedMaterial->minimum_stock ?? 0),
                    itemType: 'material'
                ));
            }
        }

        if ($status === 'done' && $goodQuantity > 0) {
            $this->dispatchEvent(new ProductSold(
                productId: (int) $validated['product_id'],
                qtyDeducted: (int) (0 - $goodQuantity),
            ));

            $updatedProduct = Product::findOrFail($validated['product_id']);
            if ((int) $updatedProduct->stock <= (int) ($updatedProduct->minimum_stock ?? 0)) {
                $this->dispatchEvent(new StockLowAlert(
                    productId: (int) $updatedProduct->id,
                    currentStock: (float) $updatedProduct->stock,
                    minimumThreshold: (float) ($updatedProduct->minimum_stock ?? 0),
                    itemType: 'product'
                ));
            }
        }

        return redirect()->route('productions.index')->with('success', 'Batch produksi berhasil disimpan.');
    }

    public function destroy(Production $production): RedirectResponse
    {
        $materialBroadcasts = [];
        $productBroadcast = null;

        DB::transaction(function () use ($production, &$materialBroadcasts, &$productBroadcast): void {
            $production->loadMissing(['materials', 'product']);

            foreach ($production->materials as $material) {
                $qtyUsed = (float) $material->pivot->quantity;
                $freshMaterial = Material::query()->lockForUpdate()->findOrFail($material->id);
                $before = (float) $freshMaterial->stock;
                $after = $before + $qtyUsed;

                $freshMaterial->update(['stock' => $after]);

                $freshMaterial->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => $qtyUsed,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshMaterial->price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Rollback '.$production->batch_code,
                    'note' => 'Pengembalian stok karena batch produksi dihapus.',
                ]);

                $materialBroadcasts[] = [
                    'id' => (int) $freshMaterial->id,
                    'quantity' => $qtyUsed,
                    'stock' => $after,
                    'minimum_stock' => (float) ($freshMaterial->minimum_stock ?? 0),
                ];
            }

            if ($production->status === 'done' && $production->good_quantity > 0 && $production->product) {
                $freshProduct = Product::query()->lockForUpdate()->findOrFail($production->product->id);
                $before = (int) $freshProduct->stock;
                $after = max(0, $before - (int) $production->good_quantity);

                $freshProduct->update(['stock' => $after]);

                $freshProduct->stockMovements()->create([
                    'type' => 'out',
                    'quantity' => (int) $production->good_quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshProduct->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Rollback '.$production->batch_code,
                    'note' => 'Pengurangan stok barang jadi karena batch produksi dihapus.',
                ]);

                $productBroadcast = [
                    'id' => (int) $freshProduct->id,
                    'quantity' => (int) $production->good_quantity,
                    'stock' => $after,
                    'minimum_stock' => (int) ($freshProduct->minimum_stock ?? 0),
                ];
            }

            $production->delete();
        });

        foreach ($materialBroadcasts as $materialBroadcast) {
            $this->dispatchEvent(new MaterialUsed(
                materialId: $materialBroadcast['id'],
                quantityUsed: (float) (0 - $materialBroadcast['quantity']),
                productionId: (int) $production->id
            ));

            if ((float) $materialBroadcast['stock'] <= (float) $materialBroadcast['minimum_stock']) {
                $this->dispatchEvent(new StockLowAlert(
                    productId: $materialBroadcast['id'],
                    currentStock: (float) $materialBroadcast['stock'],
                    minimumThreshold: (float) $materialBroadcast['minimum_stock'],
                    itemType: 'material'
                ));
            }
        }

        if ($productBroadcast) {
            $this->dispatchEvent(new ProductSold(
                productId: $productBroadcast['id'],
                qtyDeducted: $productBroadcast['quantity'],
            ));

            if ((int) $productBroadcast['stock'] <= (int) $productBroadcast['minimum_stock']) {
                $this->dispatchEvent(new StockLowAlert(
                    productId: $productBroadcast['id'],
                    currentStock: (float) $productBroadcast['stock'],
                    minimumThreshold: (float) $productBroadcast['minimum_stock'],
                    itemType: 'product'
                ));
            }
        }

        return redirect()->route('productions.index')->with('success', 'Batch produksi berhasil dihapus.');
    }

    public function updateStatus(Request $request, Production $production): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:process,done,cancelled'],
        ]);

        $originalStatus = $production->status;

        DB::transaction(function () use ($validated, $production): void {
            $production->loadMissing('product');
            $originalStatus = $production->status;
            $newStatus = $validated['status'];

            if ($originalStatus === $newStatus) {
                return;
            }

            if ($originalStatus === 'done' && $production->good_quantity > 0 && $production->product) {
                $freshProduct = Product::query()->lockForUpdate()->findOrFail($production->product->id);
                $before = (int) $freshProduct->stock;
                $after = max(0, $before - (int) $production->good_quantity);

                $freshProduct->update(['stock' => $after]);

                $freshProduct->stockMovements()->create([
                    'type' => 'out',
                    'quantity' => (int) $production->good_quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshProduct->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Status '.$production->batch_code,
                    'note' => 'Penyesuaian stok barang jadi karena status batch berubah dari selesai.',
                ]);
            }

            if ($originalStatus !== 'done' && $newStatus === 'done' && $production->good_quantity > 0 && $production->product) {
                $freshProduct = Product::query()->lockForUpdate()->findOrFail($production->product->id);
                $before = (int) $freshProduct->stock;
                $after = $before + (int) $production->good_quantity;

                $freshProduct->update(['stock' => $after]);

                $freshProduct->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => (int) $production->good_quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshProduct->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Status '.$production->batch_code,
                    'note' => 'Barang jadi masuk karena batch diubah menjadi selesai.',
                ]);
            }

            $production->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'done' ? now() : null,
            ]);
        });

        $this->dispatchEvent(new ProductionStatusUpdated(
            productionId: (int) $production->id,
            status: $validated['status'],
            productId: (int) ($production->product_id ?? 0),
            quantityProduced: (int) ($production->good_quantity ?? 0)
        ));

        if ($production->good_quantity > 0 && $production->product_id) {
            $quantityDelta = 0;

            if ($validated['status'] === 'done' && $originalStatus !== 'done') {
                $quantityDelta = -1 * (int) $production->good_quantity;
            } elseif ($originalStatus === 'done' && $validated['status'] !== 'done') {
                $quantityDelta = (int) $production->good_quantity;
            }

            if ($quantityDelta !== 0) {
                $this->dispatchEvent(new ProductSold(
                    productId: (int) $production->product_id,
                    qtyDeducted: (int) $quantityDelta,
                ));
            }
        }

        return redirect()->route('productions.index')->with('success', 'Status batch produksi berhasil diperbarui.');
    }

    public function updateStatusFromIndex(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'production_id' => ['required', 'exists:productions,id'],
            'status' => ['required', 'in:process,done,cancelled'],
        ]);

        $production = Production::findOrFail($validated['production_id']);
        $originalStatus = $production->status;

        DB::transaction(function () use ($validated, $production): void {
            $production->loadMissing('product');
            $originalStatus = $production->status;
            $newStatus = $validated['status'];

            if ($originalStatus === $newStatus) {
                return;
            }

            if ($originalStatus === 'done' && $production->good_quantity > 0 && $production->product) {
                $freshProduct = Product::query()->lockForUpdate()->findOrFail($production->product->id);
                $before = (int) $freshProduct->stock;
                $after = max(0, $before - (int) $production->good_quantity);

                $freshProduct->update(['stock' => $after]);

                $freshProduct->stockMovements()->create([
                    'type' => 'out',
                    'quantity' => (int) $production->good_quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshProduct->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Status '.$production->batch_code,
                    'note' => 'Penyesuaian stok barang jadi karena status batch berubah dari selesai.',
                ]);
            }

            if ($originalStatus !== 'done' && $newStatus === 'done' && $production->good_quantity > 0 && $production->product) {
                $freshProduct = Product::query()->lockForUpdate()->findOrFail($production->product->id);
                $before = (int) $freshProduct->stock;
                $after = $before + (int) $production->good_quantity;

                $freshProduct->update(['stock' => $after]);

                $freshProduct->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => (int) $production->good_quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshProduct->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Status '.$production->batch_code,
                    'note' => 'Barang jadi masuk karena batch diubah menjadi selesai.',
                ]);
            }

            $production->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'done' ? now() : null,
            ]);
        });

        $this->dispatchEvent(new ProductionStatusUpdated(
            productionId: (int) $production->id,
            status: $validated['status'],
            productId: (int) ($production->product_id ?? 0),
            quantityProduced: (int) ($production->good_quantity ?? 0)
        ));

        if ($production->good_quantity > 0 && $production->product_id) {
            $quantityDelta = 0;

            if ($validated['status'] === 'done' && $originalStatus !== 'done') {
                $quantityDelta = -1 * (int) $production->good_quantity;
            } elseif ($originalStatus === 'done' && $validated['status'] !== 'done') {
                $quantityDelta = (int) $production->good_quantity;
            }

            if ($quantityDelta !== 0) {
                $this->dispatchEvent(new ProductSold(
                    productId: (int) $production->product_id,
                    qtyDeducted: (int) $quantityDelta,
                ));
            }
        }

        return redirect()->route('productions.index')->with('success', 'Status batch produksi berhasil diperbarui.');
    }

    public function exportPdf()
    {
        // Set very high memory limit for PDF generation
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        // Limit data SIGNIFICANTLY to prevent memory exhaustion - export only last 100 production records
        $productions = Production::with(['product', 'materials'])->orderByDesc('created_at')->limit(100)->get();

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('productions.export_pdf', [
                'productions' => $productions,
            ]);

            $pdf->setPaper('A4', 'landscape');
            $pdf->setOption('dpi', 96);
            $pdf->setOption('enable_font_subsetting', true);

            return $pdf->download('laporan-produksi-'.now()->format('Y-m-d').'.pdf');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['pdf' => 'Gagal generate PDF: ' . $e->getMessage()]);
        }
    }

    public function exportGoogleSheets(\App\Services\SpreadsheetExportService $exportService)
    {
        try {
            $productions = Production::with('product')->orderByDesc('created_at')->get();

            $headers = ['Kode Batch', 'Produk', 'Tanggal Produksi', 'Kuantitas', 'Good Qty', 'Reject', 'Status', 'Total Biaya (Rp)', 'HPP/Unit (Rp)'];
            $data = [];

            foreach ($productions as $p) {
                $data[] = [
                    $p->batch_code,
                    $p->product ? $p->product->name : '-',
                    $p->production_date,
                    $p->quantity,
                    $p->good_quantity,
                    $p->reject_quantity,
                    strtoupper($p->status),
                    number_format($p->total_cost_snapshot, 0, ',', ''),
                    number_format($p->unit_hpp_snapshot, 0, ',', '')
                ];
            }

            $options = [
                'statistics' => [
                    7 => 'Total Biaya Produksi',
                    8 => 'Rata-rata HPP/Unit'
                ]
            ];

            return $exportService->exportAsXlsx('Riwayat-Produksi-UMKM-Pancasila', $headers, $data, $options);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export Spreadsheet Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export spreadsheet: ' . $e->getMessage()]);
        }
    }

    /**
     * Export production history to native styled Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        try {
            $export = new \App\Exports\ProduksiExport($request->all());
            return $export->download();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Production XLSX Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export XLSX: ' . $e->getMessage()]);
        }
    }

    public function getIngredients(Product $product)
    {
        $companyId = auth()->user()->company_id;

        if ($product->company_id !== $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ingredients = $product->ingredients()->get()->map(function ($material) {
            return [
                'id' => $material->id,
                'name' => $material->name,
                'unit' => $material->unit,
                'price' => (float) $material->price,
                'default_quantity' => (float) $material->pivot->quantity,
                'stock' => (float) $material->stock,
            ];
        });

        return response()->json($ingredients);
    }
}

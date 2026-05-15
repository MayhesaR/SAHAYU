<?php

namespace App\Http\Controllers;

use App\Models\OverheadCost;
use App\Models\Product;
use App\Models\Production;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HppController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'selling_price']);

        $selectedProductId = $request->query('product_id');
        $volumeBatch = max(1, (int) $request->query('volume_batch', 100));
        $wasteFactor = max(0, (int) $request->query('waste_factor', 5));
        $durationHours = max(1, (int) $request->query('duration_hours', 48));
        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        try {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();
        } catch (\Throwable $th) {
            $from = now()->startOfMonth()->startOfDay();
            $to = now()->endOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $productionBaseQuery = Production::query()
            ->where('status', 'done')
            ->whereBetween('production_date', [$from->toDateString(), $to->toDateString()])
            ->when($selectedProductId, fn ($query, $productId) => $query->where('product_id', $productId));

        $productionIds = (clone $productionBaseQuery)->pluck('id');

        $materialBreakdown = DB::table('production_materials')
            ->join('productions', 'productions.id', '=', 'production_materials.production_id')
            ->join('materials', 'materials.id', '=', 'production_materials.material_id')
            ->select(
                'materials.name',
                'materials.unit',
                'materials.price',
                DB::raw('SUM(production_materials.quantity) as qty_used'),
                DB::raw('SUM(production_materials.quantity * materials.price) as subtotal')
            )
            ->where('productions.status', 'done')
            ->whereBetween('productions.production_date', [$from->toDateString(), $to->toDateString()])
            ->when($selectedProductId, fn ($query, $productId) => $query->where('productions.product_id', $productId))
            ->groupBy('materials.id', 'materials.name', 'materials.unit', 'materials.price')
            ->orderByDesc('subtotal')
            ->limit(6)
            ->get();

        $materialCostFromSnapshot = (clone $productionBaseQuery)->sum('material_cost_snapshot');
        $materialCostFromBreakdown = $materialBreakdown->sum('subtotal');
        $materialCost = $materialCostFromSnapshot > 0 ? (float) $materialCostFromSnapshot : (float) $materialCostFromBreakdown;

        $overheadItems = OverheadCost::query()
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('cost')
            ->limit(6)
            ->get();

        $overheadFromSnapshot = (clone $productionBaseQuery)->sum('overhead_cost_snapshot');
        $overheadFromMaster = $overheadItems->sum('cost');
        $overheadCost = $overheadFromSnapshot > 0 ? (float) $overheadFromSnapshot : (float) $overheadFromMaster;

        $laborFromSnapshot = (clone $productionBaseQuery)->sum('labor_cost');
        $laborCost = $laborFromSnapshot > 0 ? (float) $laborFromSnapshot : (float) ($overheadCost * 0.2);
        $totalHpp = $materialCost + $overheadCost + $laborCost;

        $producedUnits = (int) (clone $productionBaseQuery)->sum(DB::raw('CASE WHEN good_quantity > 0 THEN good_quantity ELSE quantity END'));
        $producedUnits = max(1, $producedUnits);

        $totalInputUnits = (int) (clone $productionBaseQuery)->sum('quantity');
        $totalRejectUnits = (int) (clone $productionBaseQuery)->sum('reject_quantity');
        $doneBatches = (clone $productionBaseQuery)->count();

        $hppPerUnit = $totalHpp / $producedUnits;
        $simulatedHppPerUnit = ($totalHpp / $volumeBatch) * (1 + ($wasteFactor / 100)) * (1 + ($durationHours / 1000));

        $materialPercent = $totalHpp > 0 ? round(($materialCost / $totalHpp) * 100) : 0;
        $laborPercent = $totalHpp > 0 ? round(($laborCost / $totalHpp) * 100) : 0;
        $overheadPercent = max(0, 100 - $materialPercent - $laborPercent);

        $selectedProductName = optional($products->firstWhere('id', (int) $selectedProductId))->name ?? 'Semua Produk';

        return view('PenghitunganHPPOtomatis', [
            'products' => $products,
            'materialBreakdown' => $materialBreakdown,
            'overheadItems' => $overheadItems,
            'materialCost' => $materialCost,
            'laborCost' => $laborCost,
            'overheadCost' => $overheadCost,
            'totalHpp' => $totalHpp,
            'hppPerUnit' => $hppPerUnit,
            'simulatedHppPerUnit' => $simulatedHppPerUnit,
            'materialPercent' => $materialPercent,
            'laborPercent' => $laborPercent,
            'overheadPercent' => $overheadPercent,
            'volumeBatch' => $volumeBatch,
            'wasteFactor' => $wasteFactor,
            'durationHours' => $durationHours,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
            'selectedProductId' => $selectedProductId,
            'selectedProductName' => $selectedProductName,
            'selectedProduct' => $products->firstWhere('id', (int) $selectedProductId),
            'doneBatches' => $doneBatches,
            'producedUnits' => $producedUnits,
            'rejectRate' => $totalInputUnits > 0 ? round(($totalRejectUnits / $totalInputUnits) * 100, 2) : 0,
            'materialStopPercent' => $materialPercent,
            'laborStopPercent' => $materialPercent + $laborPercent,
            'periodLabel' => $from->translatedFormat('d M Y').' - '.$to->translatedFormat('d M Y'),
            'hasProductionData' => $productionIds->isNotEmpty(),
        ]);
    }
}

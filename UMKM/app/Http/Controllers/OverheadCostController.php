<?php

namespace App\Http\Controllers;

use App\Models\OverheadCost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OverheadCostController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->query('month', now()->month);
        $selectedYear = $request->query('year', now()->year);

        $query = OverheadCost::query()
            ->whereMonth('transaction_date', $selectedMonth)
            ->whereYear('transaction_date', $selectedYear);

        $overheadCosts = (clone $query)->latest('transaction_date')->get();
        $totalOverhead = (clone $query)->sum('cost');

        $categoryBreakdown = (clone $query)
            ->select('category', DB::raw('SUM(cost) as total'))
            ->groupBy('category')
            ->get();

        return view('ManajemenOverhead', [
            'overheadCosts' => $overheadCosts,
            'totalOverhead' => $totalOverhead,
            'categoryBreakdown' => $categoryBreakdown,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ],
            'years' => OverheadCost::selectRaw('YEAR(transaction_date) as year')
                ->distinct()
                ->pluck('year')
                ->push(now()->year)
                ->unique()
                ->sortDesc(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'cost' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
        ]);

        OverheadCost::create($validated);

        return redirect()->route('overhead.index', [
            'month' => Carbon::parse($validated['transaction_date'])->month,
            'year' => Carbon::parse($validated['transaction_date'])->year
        ])->with('success', 'Biaya operasional berhasil ditambahkan.');
    }

    public function destroy(OverheadCost $overheadCost): RedirectResponse
    {
        $overheadCost->delete();

        return redirect()->back()->with('success', 'Biaya operasional berhasil dihapus.');
    }
}

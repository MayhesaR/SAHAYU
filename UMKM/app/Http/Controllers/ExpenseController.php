<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ExpenseController extends Controller
{
    /**
     * Display a listing of petty cash operational expenses.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        // Fetch paginated expenses scoped to company
        $expenses = Expense::latest('expense_date')
            ->latest('id')
            ->paginate(15);

        // Fetch statistics for cards
        $todayExpensesSum = Expense::whereDate('expense_date', now()->toDateString())->sum('amount');
        $monthExpensesSum = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
        $totalExpensesCount = Expense::count();

        return view('ManajemenPengeluaran', [
            'expenses' => $expenses,
            'todayExpensesSum' => $todayExpensesSum,
            'monthExpensesSum' => $monthExpensesSum,
            'totalExpensesCount' => $totalExpensesCount,
        ]);
    }

    /**
     * Store a newly created operational expense.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'in:Listrik/Air,Transportasi,Perlengkapan,Gaji/Honor,Lain-lain'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran operasional berhasil dicatat.');
    }

    /**
     * Remove the specified operational expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        // Enforce boundary checks
        if ($expense->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }
}

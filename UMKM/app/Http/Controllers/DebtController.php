<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Customer;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebtController extends Controller
{
    /**
     * Display live debt ledger with filters and statistics.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Debt::with(['customer', 'payments', 'sale'])
            ->where('company_id', $companyId);

        // Filter by Customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter by Due Date
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->query('due_date'));
        }

        // Sorting options
        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');
        
        if (in_array($sort, ['created_at', 'total_amount', 'remaining_amount', 'due_date'])) {
            $query->orderBy($sort, $order);
        } else {
            $query->latest();
        }

        $debts = $query->paginate(15);
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();

        // Aggregate statistics for dashboard summary cards
        $totalOutstanding = Debt::where('company_id', $companyId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('remaining_amount');

        $overdueCount = Debt::where('company_id', $companyId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return view('CatatanUtang', [
            'debts' => $debts,
            'customers' => $customers,
            'totalOutstanding' => $totalOutstanding,
            'overdueCount' => $overdueCount,
        ]);
    }

    /**
     * Record a new installment payment and update the remaining balance and status.
     */
    public function payInstallment(Request $request, Debt $debt): RedirectResponse
    {
        // Enforce multi-tenancy access check
        if ((int)$debt->company_id !== (int)auth()->user()->company_id) {
            abort(403, 'Aksi tidak diizinkan untuk perusahaan Anda.');
        }

        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'payment_date' => ['required', 'date'],
        ]);

        $amountPaid = (float) $validated['amount_paid'];
        $remaining = (float) $debt->remaining_amount;

        if ($amountPaid > $remaining) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Jumlah cicilan melebihi sisa tagihan saat ini (Sisa Tagihan: Rp ' . number_format($remaining, 0, ',', '.') . ').',
            ]);
        }

        DB::transaction(function () use ($debt, $amountPaid, $validated) {
            // Create payment log first
            $debt->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount_paid' => $amountPaid,
                'payment_method' => $validated['payment_method'],
            ]);

            // Recalculate remaining amount strictly based on the SUM of all payments
            $totalPaid = $debt->payments()->sum('amount_paid');
            $newRemaining = max(0.00, (float)$debt->total_amount - (float)$totalPaid);

            // Transition status dynamically
            $newStatus = 'partial';
            if ($newRemaining <= 0) {
                $newStatus = 'paid';
            } elseif ($newRemaining >= (float)$debt->total_amount) {
                $newStatus = 'unpaid';
            }

            $debt->update([
                'remaining_amount' => $newRemaining,
                'status' => $newStatus,
            ]);

            // Sync with parent sale status if fully settled
            if ($newStatus === 'paid' && $debt->sale) {
                $debt->sale->update(['status' => 'paid']);
            }
        });

        return redirect()->route('debts.index')->with('success', 'Cicilan pembayaran sebesar Rp ' . number_format($amountPaid, 0, ',', '.') . ' berhasil dicatat.');
    }
}

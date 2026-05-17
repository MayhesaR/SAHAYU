<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with(['sales', 'debts'])
            ->filterSortPaginate(
                $request,
                searchableColumns: ['name', 'phone', 'address'],
                filterableColumns: [],
                defaultSort: 'name',
                defaultOrder: 'asc',
                perPage: 15,
            );

        return view('ManajemenCustomer', [
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        return redirect()->route('customers.index')->with('success', 'Pelanggan baru berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        // Scope Check (Multi-tenancy audit defense)
        if ($customer->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang diizinkan untuk menghapus data pelanggan.');
        }

        // Scope Check
        if ($customer->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil dihapus.');
    }
}

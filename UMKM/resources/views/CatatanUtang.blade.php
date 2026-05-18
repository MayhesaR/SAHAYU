@extends('layouts.app')
@section('title', 'Piutang & Kasbon Pelanggan')
@section('page_title', 'Piutang / Kasbon Pelanggan')

@section('content')
<div class="px-4 py-6 md:py-8 sm:px-8 max-w-7xl mx-auto space-y-4 md:space-y-8" 
     x-data="{
        isPaymentModalOpen: false,
        activeDebt: { id: '', customer_name: '', total_amount: 0, remaining_amount: 0, route: '' },
        amountToPay: 0,
        paymentMethod: 'cash',
        paymentDate: '{{ now()->toDateString() }}',
        
        openPaymentModal(debtId, name, total, remaining, route) {
            this.activeDebt = { id: debtId, customer_name: name, total_amount: total, remaining_amount: remaining, route: route };
            this.amountToPay = remaining;
            this.isPaymentModalOpen = true;
        },
        formatRupiah(val) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        }
     }">

    <!-- Alerts and Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl space-y-1 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-600">error</span>
                <span class="font-bold text-sm">Kesalahan Input Pembayaran:</span>
            </div>
            <ul class="list-disc list-inside text-xs font-semibold pl-8">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Header & Action section -->
    <div class="flex flex-col gap-3 md:gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight font-manrope">
                Piutang & Kasbon Pelanggan
            </h1>
            <p class="text-slate-500 font-medium text-xs md:text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-xs md:text-sm">menu_book</span>
                Manajemen CRM tagihan, piutang tempo, dan angsuran cicilan pelanggan.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.index') }}" 
               class="w-full sm:w-auto px-5 py-2.5 md:py-3 bg-emerald-600 text-white font-black text-xs rounded-xl shadow-md hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                <span>Kasir POS Baru</span>
            </a>
        </div>
    </div>

    <!-- Unified Ultra-Compact Stats Ribbon (Micro-Cards) -->
    <div class="grid grid-cols-3 gap-2 md:gap-4">
        <!-- Stat 1: Total Outstanding -->
        <div class="bg-white p-2 md:p-3.5 rounded-2xl border border-slate-100 shadow-sm md:shadow-md flex items-center gap-2 overflow-hidden group">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-amber-50 text-amber-700 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-base md:text-lg">account_balance_wallet</span>
            </div>
            <div class="min-w-0">
                <p class="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-wider truncate">Total Piutang</p>
                <h4 class="text-xs md:text-base lg:text-lg font-black text-slate-800 mt-0.5 tracking-tight truncate">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</h4>
            </div>
        </div>

        <!-- Stat 2: Overdue Count -->
        <div class="bg-white p-2 md:p-3.5 rounded-2xl border border-slate-100 shadow-sm md:shadow-md flex items-center gap-2 overflow-hidden group {{ $overdueCount > 0 ? 'border-rose-100 bg-rose-50/10' : '' }}">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-base md:text-lg font-bold">event_busy</span>
            </div>
            <div class="min-w-0">
                <p class="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-wider truncate {{ $overdueCount > 0 ? 'text-rose-600' : '' }}">Jatuh Tempo</p>
                <h4 class="text-xs md:text-base lg:text-lg font-black {{ $overdueCount > 0 ? 'text-rose-600' : 'text-slate-800' }} mt-0.5 tracking-tight truncate">{{ $overdueCount }} Debitur</h4>
            </div>
        </div>

        <!-- Stat 3: Maximum Plafon -->
        <div class="bg-white p-2 md:p-3.5 rounded-2xl border border-slate-100 shadow-sm md:shadow-md flex items-center gap-2 overflow-hidden group">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-teal-50 text-teal-700 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-base md:text-lg">contacts</span>
            </div>
            <div class="min-w-0">
                <p class="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-wider truncate">Limit Kredit</p>
                <h4 class="text-xs md:text-base lg:text-lg font-black text-slate-800 mt-0.5 tracking-tight truncate">Rp 5.000.000</h4>
            </div>
        </div>
    </div>

    <!-- Live CRM Search & Filters Panel (Easy-to-tap UI) -->
    <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-100 shadow-md md:shadow-xl space-y-4">
        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Penyaringan & Penelusuran Cepat</h3>
        <form action="{{ route('debts.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            
            <!-- Customer Dropdown -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase text-slate-400">Cari Pelanggan</label>
                <select name="customer_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Semua Debitur --</option>
                    @foreach ($customers as $cust)
                        <option value="{{ $cust->id }}" {{ request()->query('customer_id') == $cust->id ? 'selected' : '' }}>
                            {{ $cust->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Selector -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase text-slate-400">Pilih Status</label>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Semua Status --</option>
                    <option value="unpaid" {{ request()->query('status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar (Unpaid)</option>
                    <option value="partial" {{ request()->query('status') === 'partial' ? 'selected' : '' }}>Cicilan Aktif (Partial)</option>
                    <option value="paid" {{ request()->query('status') === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                </select>
            </div>

            <!-- Start Due Date -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase text-slate-400">Jatuh Tempo Dari</label>
                <input type="date" name="start_due_date" value="{{ request()->query('start_due_date') }}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none focus:border-emerald-600" />
            </div>

            <!-- End Due Date -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase text-slate-400">Jatuh Tempo Hingga</label>
                <input type="date" name="end_due_date" value="{{ request()->query('end_due_date') }}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none focus:border-emerald-600" />
            </div>

            <!-- Form Actions -->
            <div class="flex items-end gap-2 sm:col-span-2 md:col-span-1">
                <button type="submit" class="flex-1 py-2 px-3 bg-slate-800 text-white font-black text-xs rounded-xl hover:bg-slate-900 transition-all flex items-center justify-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[14px]">filter_list</span> Saring
                </button>
                <a href="{{ route('debts.index') }}" class="flex-1 py-2 px-3 bg-slate-100 text-slate-600 font-black text-xs rounded-xl hover:bg-slate-200 border border-slate-200 transition-all flex items-center justify-center gap-1.5 shadow-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Active Debts Ledger Table -->
    <div class="bg-white rounded-[1.5rem] md:rounded-[2rem] border border-slate-100 shadow-md md:shadow-xl overflow-hidden">
        <div class="px-4 py-4 md:px-8 md:py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h4 class="text-sm md:text-base font-black text-slate-800 font-manrope">Buku Besar Ledger Piutang</h4>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">Daftar terperinci rincian angsuran kasbon pelanggan.</p>
            </div>
            <span class="text-[9px] md:text-[10px] font-black text-slate-400 bg-white px-2.5 py-1 md:px-3 md:py-1.5 rounded-full border border-slate-100">
                {{ $debts->total() }} pelanggan terdaftar
            </span>
        </div>

        <!-- Desktop Debtor Table (Hidden on Mobile) -->
        <div class="w-full overflow-x-auto hidden md:block">
            <table class="w-full text-xs text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Pelanggan</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Jumlah Nota</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Total Kasbon</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Sisa Piutang</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Progres Repayment</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm" x-data="{ openGroup: null }">
                    @forelse ($debts as $customerDebts)
                        @php
                            $firstDebt = $customerDebts->first();
                            $customer = $firstDebt->customer;
                            $customerId = $firstDebt->customer_id ?? 0;
                            $totalRemaining = $customerDebts->sum('remaining_amount');
                            $totalAmount = $customerDebts->sum('total_amount');
                            $totalPaid = $totalAmount - $totalRemaining;
                            $percent = $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100) : 0;
                            
                            $overdueCount = $customerDebts->filter(function($d) {
                                return \Carbon\Carbon::parse($d->due_date)->isPast() && $d->status !== 'paid';
                            })->count();

                            $customerName = $customer->name ?? 'Umum';
                            $customerPhone = $customer->phone ?? 'Tidak ada kontak';
                            $customerAddress = $customer->address ?? 'Alamat belum diinput';
                            $customerInitials = strtoupper(substr($customerName, 0, 2));
                        @endphp
                        
                        <!-- Primary Customer Row -->
                        <tr class="hover:bg-slate-50/40 transition-colors group">
                            <!-- Debtor Name -->
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-100 text-teal-700 font-black text-sm flex items-center justify-center shadow-sm">
                                        {{ $customerInitials }}
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800">{{ $customerName }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ $customerPhone }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Total invoices count -->
                            <td class="px-6 py-6 font-bold text-slate-600">
                                {{ $customerDebts->count() }} Nota
                            </td>

                            <!-- Total Initial Debt Plafon -->
                            <td class="px-6 py-6 font-bold text-slate-500">
                                Rp {{ number_format($totalAmount, 0, ',', '.') }}
                            </td>

                            <!-- Total remaining sisa piutang -->
                            <td class="px-6 py-6">
                                <span class="font-extrabold text-sm {{ $overdueCount > 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                    Rp {{ number_format($totalRemaining, 0, ',', '.') }}
                                </span>
                                @if($overdueCount > 0)
                                    <span class="block text-[9px] text-rose-500 font-bold mt-0.5">({{ $overdueCount }} Overdue)</span>
                                @endif
                            </td>

                            <!-- Repayment Progress -->
                            <td class="px-6 py-6">
                                <div class="flex flex-col gap-1 w-28">
                                    <div class="flex justify-between items-center text-[9px] font-bold text-slate-500">
                                        <span>{{ $percent }}%</span>
                                        <span>Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden border border-slate-200/20">
                                        <div class="h-full rounded-full transition-all duration-500 bg-emerald-600" 
                                             style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-6">
                                @if($totalRemaining <= 0)
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-100 font-extrabold text-[10px] uppercase tracking-wider">Lunas</span>
                                @else
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-800 rounded-lg border border-amber-100 font-extrabold text-[10px] uppercase tracking-wider">Aktif</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="px-8 py-6 text-right">
                                <button @click="openGroup = (openGroup === {{ $customerId }} ? null : {{ $customerId }})" 
                                        type="button" 
                                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-black text-xs rounded-xl shadow-sm transition-all inline-flex items-center gap-1 active:scale-95">
                                    <span class="material-symbols-outlined text-[16px]" x-text="openGroup === {{ $customerId }} ? 'visibility_off' : 'visibility'">visibility</span>
                                    <span x-text="openGroup === {{ $customerId }} ? 'Tutup Nota' : 'Lihat Rincian'">Lihat Rincian</span>
                                </button>
                            </td>
                        </tr>

                        <!-- Sub-list (Detailed Invoices Breakdown) - Expandable Row -->
                        <tr x-show="openGroup === {{ $customerId }}" x-cloak class="bg-slate-50/50">
                            <td colspan="7" class="px-8 py-5 border-t border-b border-slate-100">
                                <div class="bg-white p-6 rounded-2xl border border-slate-100 space-y-4 shadow-sm max-w-5xl">
                                    <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                                        <h5 class="text-xs font-black uppercase tracking-widest text-slate-500">Daftar Nota Penjualan Unpaid/Partial</h5>
                                        <span class="text-[10px] font-bold text-slate-400">Total Tagihan Akumulatif: Rp {{ number_format($totalRemaining, 0, ',', '.') }}</span>
                                    </div>

                                    <!-- Sub-table for individual invoices -->
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs border-collapse">
                                            <thead>
                                                <tr class="text-[9px] uppercase tracking-wider text-slate-400 border-b border-slate-100 font-black">
                                                    <th class="py-2.5">Tanggal Transaksi</th>
                                                    <th class="py-2.5">Nomor Nota</th>
                                                    <th class="py-2.5">Daftar Produk</th>
                                                    <th class="py-2.5">Utang Awal</th>
                                                    <th class="py-2.5">Sisa Utang</th>
                                                    <th class="py-2.5">Jatuh Tempo</th>
                                                    <th class="py-2.5 text-right">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($customerDebts as $d)
                                                    @php
                                                        $dueDate = \Carbon\Carbon::parse($d->due_date)->startOfDay();
                                                        $today = \Carbon\Carbon::now()->startOfDay();
                                                        $diffInDays = $today->diffInDays($dueDate, false);
                                                        $diffInDays = (int) round($diffInDays);
                                                        $absDiff = abs($diffInDays);
                                                        $isOverdueInvoice = $dueDate->isPast() && $d->status !== 'paid';

                                                        // Extract product names
                                                        $productNames = [];
                                                        if ($d->sale && $d->sale->items) {
                                                            foreach ($d->sale->items as $item) {
                                                                if ($item->product) {
                                                                    $productNames[] = $item->product->name . ' (' . $item->quantity . 'x)';
                                                                }
                                                            }
                                                        }
                                                        $productDescription = !empty($productNames) ? implode(', ', $productNames) : 'Transaksi Kasir POS';
                                                    @endphp
                                                    <tr class="hover:bg-slate-50/30 transition-colors">
                                                        <td class="py-3 font-semibold text-slate-600">
                                                            {{ \Carbon\Carbon::parse($d->created_at)->translatedFormat('d M Y H:i') }}
                                                        </td>
                                                        <td class="py-3 font-bold text-slate-700 font-mono">
                                                            #{{ str_pad($d->sale_id ?? $d->id, 5, '0', STR_PAD_LEFT) }}
                                                        </td>
                                                        <td class="py-3 text-slate-400 font-medium max-w-xs truncate" title="{{ $productDescription }}">
                                                            {{ $productDescription }}
                                                        </td>
                                                        <td class="py-3 font-bold text-slate-600">
                                                            Rp {{ number_format($d->total_amount, 0, ',', '.') }}
                                                        </td>
                                                        <td class="py-3">
                                                            <span class="font-extrabold {{ $isOverdueInvoice ? 'text-rose-600' : 'text-slate-800' }}">
                                                                Rp {{ number_format($d->remaining_amount, 0, ',', '.') }}
                                                            </span>
                                                        </td>
                                                        <td class="py-3">
                                                            <div class="flex flex-col">
                                                                <span class="font-bold text-[10px] {{ $isOverdueInvoice ? 'text-rose-500' : 'text-slate-500' }}">
                                                                    {{ $dueDate->translatedFormat('d M Y') }}
                                                                </span>
                                                                <span class="text-[9px] font-bold mt-0.5 {{ $isOverdueInvoice ? 'text-rose-500' : 'text-slate-400' }}">
                                                                    @if($isOverdueInvoice)
                                                                        Terlewat {{ $absDiff }} hari
                                                                    @elseif($diffInDays === 0)
                                                                        Hari ini
                                                                    @else
                                                                        {{ $absDiff }} hari lagi
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td class="py-3 text-right">
                                                            @if($d->status !== 'paid')
                                                                <button @click="openPaymentModal(
                                                                            '{{ $d->id }}', 
                                                                            '{{ addslashes($d->customer->name ?? '') }}', 
                                                                            '{{ $d->total_amount }}', 
                                                                            '{{ $d->remaining_amount }}', 
                                                                            '{{ route('debts.pay', $d) }}'
                                                                        )"
                                                                        type="button"
                                                                        class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-black text-[10px] rounded-lg shadow-sm transition-all inline-flex items-center gap-1 active:scale-95">
                                                                    <span class="material-symbols-outlined text-xs">price_check</span>
                                                                    <span>Bayar</span>
                                                                </button>
                                                            @else
                                                                <span class="text-[9px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded">Lunas</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-8 py-6 text-sm text-slate-400 font-semibold text-center whitespace-normal">
                                Belum ada data catatan piutang / kasbon yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Debtor List (Hidden on Desktop) - Clean scannable Contact List style -->
        <div class="block md:hidden divide-y divide-slate-100 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden" x-data="{ openGroup: null }">
            @forelse ($debts as $customerDebts)
                @php
                    $firstDebt = $customerDebts->first();
                    $customer = $firstDebt->customer;
                    $customerId = $firstDebt->customer_id ?? 0;
                    $totalRemaining = $customerDebts->sum('remaining_amount');
                    $totalAmount = $customerDebts->sum('total_amount');
                    $totalPaid = $totalAmount - $totalRemaining;
                    $percent = $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100) : 0;
                    
                    $overdueCount = $customerDebts->filter(function($d) {
                        return \Carbon\Carbon::parse($d->due_date)->isPast() && $d->status !== 'paid';
                    })->count();

                    $customerName = $customer->name ?? 'Umum';
                    $customerPhone = $customer->phone ?? 'Tidak ada kontak';
                    $customerInitials = strtoupper(substr($customerName, 0, 1));
                @endphp
                
                <div class="p-3.5 hover:bg-slate-50/40 transition-colors space-y-2.5">
                    <div class="flex items-center justify-between gap-3">
                        <!-- Left: Contact Info (Avatar + Name & Phone) -->
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-black text-sm flex items-center justify-center flex-shrink-0">
                                {{ $customerInitials }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-black text-slate-800 text-sm truncate">{{ $customerName }}</h4>
                                <p class="text-[10px] font-bold text-slate-400 truncate mt-0.5">{{ $customerPhone }}</p>
                            </div>
                        </div>

                        <!-- Right: Debt Amount & Overdue Badge -->
                        <div class="text-right flex-shrink-0">
                            <h4 class="text-sm font-black text-slate-900">Rp {{ number_format($totalRemaining, 0, ',', '.') }}</h4>
                            <p class="text-[9px] font-bold mt-0.5 {{ $overdueCount > 0 ? 'text-rose-500 font-black' : 'text-slate-400' }}">
                                @if($overdueCount > 0)
                                    {{ $overdueCount }} Overdue
                                @else
                                    {{ $customerDebts->count() }} Nota Aktif
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Repayment Progress Line & Status Badges -->
                    <div class="flex items-center justify-between gap-4 text-[9px] font-bold text-slate-500 pt-1 border-t border-slate-100/60">
                        <div class="flex items-center gap-2">
                            <span>Status:</span>
                            @if($totalRemaining <= 0)
                                <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded uppercase tracking-wider text-[8px] font-black">Lunas</span>
                            @else
                                <span class="text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded uppercase tracking-wider text-[8px] font-black">Aktif</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span>Terbayar: <strong class="text-slate-700">{{ $percent }}%</strong></span>
                            <div class="w-12 bg-slate-100 rounded-full h-1 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-600" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Panel: Toggle "Lihat Rincian Nota" -->
                    <div class="flex items-center justify-between pt-1">
                        <button @click="openGroup = (openGroup === {{ $customerId }} ? null : {{ $customerId }})" 
                                type="button" 
                                class="p-1.5 -ml-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-all flex items-center gap-1 text-[10px] font-black">
                            <span class="material-symbols-outlined text-base" x-text="openGroup === {{ $customerId }} ? 'visibility_off' : 'visibility'">visibility</span>
                            <span x-text="openGroup === {{ $customerId }} ? 'Tutup Nota' : '👁️ Lihat Rincian Nota'">👁️ Lihat Rincian Nota</span>
                        </button>
                    </div>

                    <!-- Expandable Sub-list for Invoices (Mobile) -->
                    <div x-show="openGroup === {{ $customerId }}" x-cloak class="pt-1.5 space-y-2">
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 space-y-3">
                            <div class="text-[8px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-200/50 pb-1 flex justify-between">
                                <span>Rincian Nota Penjualan</span>
                                <span>{{ $customerDebts->count() }} Transaksi</span>
                            </div>

                            <div class="space-y-3 divide-y divide-slate-200/50">
                                @foreach($customerDebts as $d)
                                    @php
                                        $dueDate = \Carbon\Carbon::parse($d->due_date)->startOfDay();
                                        $today = \Carbon\Carbon::now()->startOfDay();
                                        $diffInDays = $today->diffInDays($dueDate, false);
                                        $diffInDays = (int) round($diffInDays);
                                        $absDiff = abs($diffInDays);
                                        $isOverdueInvoice = $dueDate->isPast() && $d->status !== 'paid';

                                        // Extract product names
                                        $productNames = [];
                                        if ($d->sale && $d->sale->items) {
                                            foreach ($d->sale->items as $item) {
                                                if ($item->product) {
                                                    $productNames[] = $item->product->name . ' (' . $item->quantity . 'x)';
                                                }
                                            }
                                        }
                                        $productDescription = !empty($productNames) ? implode(', ', $productNames) : 'Transaksi Kasir POS';
                                    @endphp
                                    <div class="pt-2.5 first:pt-0 space-y-1 text-[10px]">
                                        <div class="flex justify-between items-start font-bold">
                                            <span class="text-slate-800">Nota #{{ str_pad($d->sale_id ?? $d->id, 5, '0', STR_PAD_LEFT) }}</span>
                                            <span class="{{ $isOverdueInvoice ? 'text-rose-600' : 'text-slate-800' }}">Sisa: Rp {{ number_format($d->remaining_amount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="text-slate-400 font-semibold text-[9px]">
                                            {{ \Carbon\Carbon::parse($d->created_at)->translatedFormat('d M Y H:i') }}
                                        </div>
                                        <div class="text-slate-500 font-medium leading-relaxed font-manrope">
                                            <span class="text-slate-400 font-bold">Keterangan:</span> {{ $productDescription }}
                                        </div>
                                        <div class="flex justify-between items-center pt-1 text-[9px] font-bold">
                                            <div class="flex flex-col">
                                                <span class="text-slate-400">Jatuh Tempo:</span>
                                                <span class="{{ $isOverdueInvoice ? 'text-rose-500 font-black' : 'text-slate-500' }}">
                                                    {{ $dueDate->translatedFormat('d M Y') }} ({{ $isOverdueInvoice ? 'Overdue ' . $absDiff . ' hari' : $absDiff . ' hari lagi' }})
                                                </span>
                                            </div>
                                            <div>
                                                @if($d->status !== 'paid')
                                                    <button @click="openPaymentModal(
                                                                '{{ $d->id }}', 
                                                                '{{ addslashes($d->customer->name ?? '') }}', 
                                                                '{{ $d->total_amount }}', 
                                                                '{{ $d->remaining_amount }}', 
                                                                '{{ route('debts.pay', $d) }}'
                                                            )"
                                                            type="button"
                                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white font-black rounded-lg shadow-sm transition-all flex items-center gap-1 active:scale-95">
                                                        <span class="material-symbols-outlined text-xs">price_check</span>
                                                        <span>Bayar</span>
                                                    </button>
                                                @else
                                                    <span class="text-[8px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-0.5 rounded">Lunas</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-sm text-slate-400 font-semibold text-center">
                    Belum ada data catatan piutang / kasbon yang terdaftar.
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 md:px-8 md:py-4 bg-slate-50 border-t border-slate-100">
            {{ $debts->appends(request()->query())->links() }}
        </div>
    </div>


    <!-- PAYMENT INTERACTIVE MODAL (Bayar Cicilan via AlpineJS) -->
    <div x-show="isPaymentModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak>
         
        <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl w-full max-w-md overflow-hidden relative" 
             @click.away="isPaymentModalOpen = false">
             
            <!-- Close Button -->
            <button @click="isPaymentModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>

            <!-- Header -->
            <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-50 text-amber-700 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined">price_check</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800">Form Bayar Angsuran</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">SAHAYU Mini-ERP CRM</p>
                </div>
            </div>
            
            <!-- Form Body -->
            <form x-bind:action="activeDebt.route" method="POST">
                @csrf
                <div class="p-6 space-y-5">
                    
                    <!-- Debtor Info Info -->
                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Debitur / Pelanggan</p>
                        <p class="text-base font-black text-slate-800 mt-0.5" x-text="activeDebt.customer_name"></p>
                        <div class="flex justify-between items-center border-t border-slate-200/50 mt-2 pt-2 text-xs font-bold text-slate-500">
                            <span>Sisa Tagihan Tempo:</span>
                            <span class="font-black text-slate-900" x-text="formatRupiah(activeDebt.remaining_amount)"></span>
                        </div>
                    </div>

                    <!-- Input Nominal Cicilan -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Nominal Pembayaran (Rupiah)</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-4 text-slate-400 font-black text-sm">Rp</span>
                            <input type="number" 
                                   name="amount_paid" 
                                   x-model.number="amountToPay"
                                   x-bind:max="activeDebt.remaining_amount"
                                   min="1"
                                   required 
                                   class="w-full pl-10 pr-4 py-3 bg-slate-50 border-2 border-slate-100 focus:bg-white focus:border-emerald-600 rounded-xl text-lg font-black text-slate-800 outline-none transition-all font-mono" />
                        </div>
                    </div>

                    <!-- Payment Method Dropdown -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Pilih Saluran Pembayaran</label>
                        <select name="payment_method" 
                                x-model="paymentMethod"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-700 focus:outline-none focus:border-emerald-600">
                            <option value="cash">Tunai / Cash</option>
                            <option value="transfer">Transfer Bank (Mandiri/BCA)</option>
                            <option value="qris">QRIS Digital</option>
                        </select>
                    </div>

                    <!-- Payment Date -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Tanggal Pembayaran</label>
                        <input type="date" 
                               name="payment_date" 
                               x-model="paymentDate"
                               required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-700 focus:outline-none focus:border-emerald-600" />
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" 
                            @click="isPaymentModalOpen = false" 
                            class="px-5 py-2.5 bg-white text-slate-600 font-bold text-xs rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-all">
                        Batal
                    </button>
                    <button type="submit" 
                            style="background-color: #005050;" 
                            class="px-5 py-2.5 text-white font-black text-xs rounded-xl shadow-md hover:opacity-95 transition-all">
                        Simpan Angsuran
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@extends('layouts.app')
@section('title', 'Piutang & Kasbon Pelanggan')
@section('page_title', 'Piutang / Kasbon Pelanggan')

@section('content')
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 9999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
</style>

<script>
    window.debtData = {
        customers: [
            @foreach ($debts as $customerDebts)
                @php
                    $firstDebt = $customerDebts->first();
                    $customer = $firstDebt->customer;
                    $customerId = $firstDebt->customer_id ?? 0;
                    $totalRemaining = $customerDebts->sum('remaining_amount');
                    $totalAmount = $customerDebts->sum('total_amount');
                    $lastTransactionDate = \Carbon\Carbon::parse($customerDebts->max('created_at'))->translatedFormat('d M Y');
                    
                    $invoices = [];
                    $paymentsArr = [];
                    foreach ($customerDebts as $d) {
                        if ($d->status !== 'paid') {
                            $productNames = [];
                            if ($d->sale && $d->sale->items) {
                                foreach ($d->sale->items as $item) {
                                    if ($item->product) {
                                        $productNames[] = $item->product->name . ' (' . $item->quantity . 'x)';
                                    }
                                }
                            }
                            $productDesc = !empty($productNames) ? implode(', ', $productNames) : 'Transaksi Kasir POS';

                            $due = \Carbon\Carbon::parse($d->due_date)->startOfDay();
                            $today = \Carbon\Carbon::today();
                            $daysDiff = (int)$today->diffInDays($due, false);
                            
                            if ($daysDiff < 0) {
                                $dueText = 'Jatuh Tempo: ' . abs($daysDiff) . ' Hari yang Lalu';
                                $dueClass = 'text-rose-600 dark:text-rose-400 font-semibold';
                            } elseif ($daysDiff <= 3) {
                                $dueText = $daysDiff === 0 ? 'Jatuh Tempo: Hari Ini' : 'Jatuh Tempo: ' . $daysDiff . ' Hari Lagi';
                                $dueClass = 'text-amber-600 dark:text-amber-400 font-semibold';
                            } else {
                                $dueText = 'Jatuh Tempo: ' . $due->translatedFormat('d/m/Y');
                                $dueClass = 'text-stone-500 dark:text-white font-medium';
                            }

                            $invoices[] = [
                                'id' => $d->id,
                                'sale_id' => $d->sale_id,
                                'invoice_number' => 'Nota #' . str_pad($d->id, 5, '0', STR_PAD_LEFT),
                                'total_amount' => (float)$d->total_amount,
                                'remaining_amount' => (float)$d->remaining_amount,
                                'due_date' => $due->translatedFormat('d M Y'),
                                'due_text' => $dueText,
                                'due_class' => $dueClass,
                                'is_overdue' => $daysDiff < 0,
                                'days_overdue' => $daysDiff < 0 ? abs($daysDiff) : 0,
                                'due_label' => $daysDiff < 0 ? 'Jatuh Tempo' : ($daysDiff === 0 ? 'Hari Ini' : $daysDiff . ' Hari'),
                                'date' => \Carbon\Carbon::parse($d->created_at)->translatedFormat('d M Y'),
                                'raw_date' => \Carbon\Carbon::parse($d->created_at)->toDateString(),
                                'description' => $productDesc,
                                'pay_route' => route('debts.pay', $d),
                                'status' => $d->status
                            ];
                        }

                        // Collect payment history for this specific customer
                        foreach ($d->payments as $p) {
                            $paymentsArr[] = [
                                'id' => $p->id,
                                'amount_paid' => (float)$p->amount_paid,
                                'payment_method' => strtoupper($p->payment_method),
                                'payment_date' => \Carbon\Carbon::parse($p->payment_date)->translatedFormat('d M Y'),
                                'created_at' => $p->created_at ? $p->created_at->toIso8601String() : '',
                                'invoice_number' => 'Nota #' . str_pad($d->id, 5, '0', STR_PAD_LEFT),
                            ];
                        }
                    }

                    // Sort payments history by created_at descending (newest first)
                    usort($paymentsArr, function ($a, $b) {
                        return strcmp($b['created_at'], $a['created_at']);
                    });
                @endphp
                {
                    id: {{ $customerId }},
                    name: '{{ addslashes($customer->name ?? 'Umum') }}',
                    phone: '{{ $customer->phone ?? 'Tidak ada kontak' }}',
                    initials: '{{ strtoupper(substr($customer->name ?? 'Umum', 0, 2)) }}',
                    total_remaining: {{ $totalRemaining }},
                    total_amount: {{ $totalAmount }},
                    last_transaction: '{{ $lastTransactionDate }}',
                    invoices: {!! json_encode($invoices) !!},
                    payments: {!! json_encode($paymentsArr) !!}
                },
            @endforeach
        ]
    };
</script>

<div class="px-4 py-6 md:py-8 sm:px-8 max-w-7xl mx-auto space-y-6" 
     x-data="{
        searchQuery: '',
        selectedCustomerId: null,
        amountToPay: 0,
        displayAmount: '',
        paymentMethod: 'cash',
        paymentDate: '{{ now()->toDateString() }}',
        showAdvancedFilters: false,
        customers: window.debtData.customers,
        showModal: false,
        isPaymentSuccessModalOpen: {{ session('payment_success') ? 'true' : 'false' }},
        
        // Inner Modal Invoices Search, Filter, and Selection States
        invoiceSearchQuery: '',
        invoiceStatusFilter: 'all',
        invoiceStartDate: '',
        invoiceEndDate: '',
        selectedInvoiceIds: [],
        
        init() {
            this.$watch('amountToPay', value => {
                this.displayAmount = value ? new Intl.NumberFormat('id-ID').format(value) : '';
            });
        },
        
        openPaymentModal(customerId) {
            this.selectedCustomerId = customerId;
            let cust = this.getActiveCustomer();
            if (cust) {
                this.invoiceSearchQuery = '';
                this.invoiceStatusFilter = 'all';
                this.invoiceStartDate = '';
                this.invoiceEndDate = '';
                this.selectedInvoiceIds = [];
                this.recalculateAmount();
                this.showModal = true;
            }
        },
        
        selectInvoice(id) {
            let index = this.selectedInvoiceIds.indexOf(id);
            if (index > -1) {
                this.selectedInvoiceIds.splice(index, 1);
            } else {
                this.selectedInvoiceIds.push(id);
            }
            this.recalculateAmount();
        },
        
        toggleSelectAllFiltered() {
            let filtered = this.getFilteredInvoices();
            let allSelected = filtered.every(inv => this.selectedInvoiceIds.includes(inv.id));
            if (allSelected) {
                // Deselect all filtered
                this.selectedInvoiceIds = this.selectedInvoiceIds.filter(id => !filtered.some(f => f.id === id));
            } else {
                // Select all filtered (avoid duplicates)
                filtered.forEach(inv => {
                    if (!this.selectedInvoiceIds.includes(inv.id)) {
                        this.selectedInvoiceIds.push(inv.id);
                    }
                });
            }
            this.recalculateAmount();
        },
        
        recalculateAmount() {
            let cust = this.getActiveCustomer();
            if (!cust) return;
            this.amountToPay = cust.invoices
                .filter(inv => this.selectedInvoiceIds.includes(inv.id))
                .reduce((sum, inv) => sum + inv.remaining_amount, 0);
            this.displayAmount = new Intl.NumberFormat('id-ID').format(this.amountToPay);
        },
        
        getFilteredInvoices() {
            let cust = this.getActiveCustomer();
            if (!cust) return [];
            return cust.invoices.filter(inv => {
                let matchesSearch = inv.invoice_number.toLowerCase().includes(this.invoiceSearchQuery.toLowerCase())
                    || inv.description.toLowerCase().includes(this.invoiceSearchQuery.toLowerCase());
                
                let matchesStatus = true;
                if (this.invoiceStatusFilter === 'overdue') {
                    matchesStatus = inv.is_overdue;
                } else if (this.invoiceStatusFilter === 'unpaid') {
                    matchesStatus = inv.status === 'unpaid';
                } else if (this.invoiceStatusFilter === 'partial') {
                    matchesStatus = inv.status === 'partial';
                }

                let matchesDate = true;
                if (this.invoiceStartDate) {
                    matchesDate = matchesDate && (inv.raw_date >= this.invoiceStartDate);
                }
                if (this.invoiceEndDate) {
                    matchesDate = matchesDate && (inv.raw_date <= this.invoiceEndDate);
                }
                
                return matchesSearch && matchesStatus && matchesDate;
            });
        },
        
        getActiveCustomer() {
            return this.customers.find(c => c.id === this.selectedCustomerId);
        },
        
        setQuickAmount(amount) {
            let cust = this.getActiveCustomer();
            if (!cust) return;
            
            let maxAmt = cust.invoices
                .filter(inv => this.selectedInvoiceIds.includes(inv.id))
                .reduce((sum, inv) => sum + inv.remaining_amount, 0);
                
            if (amount === 'lunas') {
                this.amountToPay = maxAmt;
            } else {
                this.amountToPay = Math.min(amount, maxAmt);
            }
            this.displayAmount = new Intl.NumberFormat('id-ID').format(this.amountToPay);
        },
        
        formatRupiah(val) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        }
     }">

    <!-- Alerts and Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 text-emerald-800 dark:text-emerald-400 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 text-rose-800 dark:text-rose-300 rounded-2xl space-y-1 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-600 dark:text-rose-400">error</span>
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
            <h1 class="text-2xl md:text-3xl font-black text-stone-800 dark:text-white tracking-tight font-manrope">
                Piutang & Kasbon Pelanggan
            </h1>
            <p class="text-stone-500 dark:text-white font-medium text-xs md:text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-xs md:text-sm">receipt_long</span>
                Terima angsuran kasbon pelanggan secara instan dan bayar banyak nota sekaligus.
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if (!$debts->isEmpty())
                <!-- Guided Tour Button -->
                <button type="button" id="btn-start-tour"
                        class="bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-2 text-sm font-semibold transition-all flex items-center gap-2 border border-emerald-100/30 dark:border-emerald-900/30 shadow-sm hover:scale-[1.02] active:scale-[0.98]">
                    <span class="material-symbols-outlined text-sm font-bold">help</span>
                    <span>💡 Panduan Kasir</span>
                </button>
            @endif
            <a href="{{ route('sales.index') }}" 
               class="w-full sm:w-auto px-5 py-2.5 md:py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-900/10 transition-all duration-200 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                <span>POS Penjualan Baru</span>
            </a>
        </div>
    </div>

    <!-- Stats Ribbon -->
    <div class="grid grid-cols-2 gap-3">
        <!-- Stat 1: Total Outstanding -->
        <div class="bg-white dark:bg-zinc-900 p-3 md:p-4 rounded-3xl border border-stone-200/60 dark:border-zinc-800/80 shadow-lg shadow-emerald-900/5 flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
            </div>
            <div class="min-w-0">
                <p class="text-[9px] md:text-[10px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest truncate">Total Piutang</p>
                <h4 class="text-xs md:text-base lg:text-lg font-bold text-stone-800 dark:text-white mt-0.5 tracking-tight truncate">
                    Rp {{ number_format($totalOutstanding, 0, ',', '.') }}
                </h4>
            </div>
        </div>

        <!-- Stat 2: Overdue Count -->
        <div class="bg-white dark:bg-zinc-900 p-3 md:p-4 rounded-3xl border border-stone-200/60 dark:border-zinc-800/80 shadow-lg shadow-emerald-900/5 flex items-center gap-3 {{ $overdueCount > 0 ? 'border-rose-200 bg-rose-50/10' : '' }}">
            <div class="w-10 h-10 bg-rose-50 dark:bg-rose-950/40 text-rose-500 dark:text-rose-400 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-xl font-bold">event_busy</span>
            </div>
            <div class="min-w-0">
                <p class="text-[9px] md:text-[10px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest truncate {{ $overdueCount > 0 ? 'text-rose-500 dark:text-rose-400' : '' }}">Jatuh Tempo</p>
                <h4 class="text-xs md:text-base lg:text-lg font-bold {{ $overdueCount > 0 ? 'text-rose-500 dark:text-rose-400' : 'text-stone-800 dark:text-white' }} mt-0.5 tracking-tight truncate">
                    {{ $overdueCount }} Debitur
                </h4>
            </div>
        </div>
    </div>

    <!-- Advanced Filter Trigger Bar -->
    <div class="bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-stone-200/60 dark:border-zinc-800/80 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-widest text-stone-400 dark:text-zinc-400 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">tune</span>
                Pencarian & Penyaringan Lanjutan Pelanggan
            </h3>
            <button type="button" @click="showAdvancedFilters = !showAdvancedFilters"
                    class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors flex items-center gap-1">
                <span x-text="showAdvancedFilters ? 'Tutup Filter' : 'Buka Filter Lanjutan'"></span>
                <span class="material-symbols-outlined text-xs" x-text="showAdvancedFilters ? 'expand_less' : 'expand_more'"></span>
            </button>
        </div>

        <!-- Collapsible Filters -->
        <div x-show="showAdvancedFilters" x-collapse x-cloak class="pt-3 border-t border-stone-100 dark:border-zinc-800/60">
            <form action="{{ route('debts.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Status -->
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold uppercase text-stone-400 dark:text-zinc-400">Pilih Status</label>
                    <select name="status" class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs font-semibold text-stone-700 dark:text-zinc-200 focus:outline-none focus:border-emerald-500">
                        <option value="">-- Semua Status --</option>
                        <option value="unpaid" {{ request()->query('status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar (Unpaid)</option>
                        <option value="partial" {{ request()->query('status') === 'partial' ? 'selected' : '' }}>Cicilan Aktif (Partial)</option>
                        <option value="paid" {{ request()->query('status') === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                    </select>
                </div>

                <!-- Start Due Date -->
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold uppercase text-stone-400 dark:text-zinc-400">Jatuh Tempo Dari</label>
                    <input type="date" name="start_due_date" value="{{ request()->query('start_due_date') }}"
                           class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs font-semibold text-stone-700 dark:text-zinc-200 focus:outline-none focus:border-emerald-500" />
                </div>

                <!-- End Due Date -->
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold uppercase text-stone-400 dark:text-zinc-400">Jatuh Tempo Hingga</label>
                    <input type="date" name="end_due_date" value="{{ request()->query('end_due_date') }}"
                           class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs font-semibold text-stone-700 dark:text-zinc-200 focus:outline-none focus:border-emerald-500" />
                </div>

                <!-- Actions -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 bg-stone-800 text-white font-bold text-xs rounded-xl hover:bg-stone-900 dark:hover:bg-zinc-800 transition-all flex items-center justify-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-[14px]">filter_list</span> Saring
                    </button>
                    <a href="{{ route('debts.index') }}" class="flex-1 py-2 bg-stone-100 dark:bg-zinc-800 text-stone-600 dark:text-white font-bold text-xs rounded-xl hover:bg-stone-200 dark:hover:bg-zinc-750 border border-stone-200 dark:border-zinc-800 transition-all text-center flex items-center justify-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if ($debts->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-3xl p-16 text-center border border-stone-200/60 dark:border-zinc-800/80 shadow-lg shadow-emerald-900/5 max-w-lg mx-auto mt-12">
            <div class="flex flex-col items-center justify-center gap-3">
                <span class="material-symbols-outlined text-5xl text-emerald-600 dark:text-emerald-400 font-light">check_circle</span>
                <p class="text-lg font-bold text-stone-850 dark:text-white mt-2">Semua tagihan lunas!</p>
                <p class="text-xs text-stone-400 dark:text-zinc-500 max-w-sm leading-relaxed">Luar biasa! Tidak ada catatan piutang atau kasbon pelanggan yang aktif/menggantung saat ini di dalam sistem Anda.</p>
                <a href="{{ route('sales.index') }}" 
                   class="mt-4 px-6 py-3 bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] text-white font-bold text-sm rounded-xl shadow-md shadow-emerald-500/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                    <span>POS Penjualan Baru</span>
                </a>
            </div>
        </div>
    @else
        <!-- QUICK SEARCH BAR & CLIENTS GRID -->
        <div class="space-y-4">
            <!-- Quick Search Bar -->
            <div class="relative" id="tour-search-bar">
                <span class="material-symbols-outlined absolute left-4 top-3 text-stone-400 dark:text-zinc-400">search</span>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari nama pelanggan..." 
                       class="w-full pl-11 pr-4 py-3.5 bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-2xl text-xs font-semibold text-stone-800 dark:text-white outline-none transition-all placeholder-stone-400 shadow-sm" />
            </div>

            <!-- Customer Cards Grid Layout -->
            <div id="tour-customer-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="cust in customers.filter(c => c.name.toLowerCase().includes(searchQuery.toLowerCase()))" :key="cust.id">
                    <div @click="openPaymentModal(cust.id)"
                         class="p-5 bg-white dark:bg-zinc-900 rounded-[2rem] border border-stone-200/60 dark:border-zinc-800/80 transition-all cursor-pointer flex flex-col justify-between gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 duration-200">
                        
                        <!-- Top Info Row -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Avatar -->
                                <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 font-bold text-sm flex items-center justify-center flex-shrink-0 shadow-sm">
                                    <span x-text="cust.initials"></span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-black text-stone-850 dark:text-white text-sm truncate" x-text="cust.name"></h4>
                                    <p class="text-[10px] text-stone-400 dark:text-zinc-400 font-semibold truncate mt-0.5" x-text="cust.phone"></p>
                                </div>
                            </div>
                            <!-- Number of Invoices Badge -->
                            <span class="px-2.5 py-1 bg-stone-50 dark:bg-zinc-800 text-stone-600 dark:text-zinc-300 font-bold text-[10px] rounded-full flex-shrink-0 border border-stone-100 dark:border-zinc-800/50"
                                  x-text="cust.invoices.length + ' Nota'">
                            </span>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-stone-100 dark:border-zinc-800/40 my-1"></div>

                        <!-- Bottom Info Row -->
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-500 uppercase tracking-widest">Sisa Tagihan</p>
                                <span class="font-bold text-rose-500 dark:text-rose-400 text-sm mt-0.5 inline-block"
                                      x-text="formatRupiah(cust.total_remaining)">
                                </span>
                            </div>
                            
                            <!-- Action Button trigger modal -->
                            <button type="button" class="px-3.5 py-2 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-500 hover:text-white text-emerald-700 dark:text-emerald-400 font-bold text-[10.5px] rounded-xl transition-all flex items-center gap-1">
                                <span>Bayar / Detail</span>
                                <span class="material-symbols-outlined text-[12px] font-black">arrow_forward_ios</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Laravel Pagination Links -->
            <div class="pt-4">
                {{ $debts->appends(request()->query())->links() }}
            </div>
        </div>
    @endif

    <!-- DETAIL & PAYMENT MODAL (POPUP) -->
    <div x-show="showModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-stone-900/60 dark:bg-black/70 backdrop-blur-md" @click="showModal = false"></div>
        
        <!-- Modal Content Box -->
        <div class="relative bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-slate-100 dark:border-zinc-800/80 shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col z-10 animate-scale-up"
             x-show="showModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Modal Header -->
            <div class="bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md px-6 py-4 border-b border-stone-100 dark:border-zinc-800/60 flex items-center justify-between z-20 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500 text-white font-bold text-sm flex items-center justify-center shadow-sm">
                        <span x-text="getActiveCustomer() ? getActiveCustomer().initials : ''"></span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-stone-850 dark:text-white" x-text="getActiveCustomer() ? getActiveCustomer().name : ''"></h3>
                        <p class="text-[10px] text-stone-400 dark:text-zinc-400 font-semibold mt-0.5" x-text="getActiveCustomer() ? getActiveCustomer().phone : ''"></p>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="w-8 h-8 rounded-full bg-stone-50 dark:bg-zinc-800 text-stone-500 dark:text-zinc-400 hover:bg-stone-100 dark:hover:bg-zinc-700 transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            
            <!-- Modal Body Wrapper -->
            <div class="overflow-y-auto custom-scrollbar flex-1 min-h-0">
                <!-- Modal Body (Two-Column Layout) -->
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- LEFT COLUMN: Debt Details, Active Invoices, and Payment History -->
                    <div class="space-y-6">
                        <!-- Cumulative Outstandings -->
                        <div class="p-4 bg-rose-50/40 dark:bg-rose-950/10 border border-rose-100 dark:border-rose-900/30 rounded-3xl flex justify-between items-center">
                            <div>
                                <p class="text-[10px] font-bold text-rose-500 dark:text-rose-450 uppercase tracking-wider">Total Hutang Aktif</p>
                                <h4 class="text-lg font-black text-rose-600 dark:text-rose-400 mt-0.5" x-text="getActiveCustomer() ? formatRupiah(getActiveCustomer().total_remaining) : 'Rp 0'"></h4>
                            </div>
                            <span class="material-symbols-outlined text-3xl text-rose-300 dark:text-rose-800">account_balance_wallet</span>
                        </div>

                        <!-- Inner Modal Search & Filters for Invoices -->
                        <div class="p-3 bg-stone-50 dark:bg-zinc-850 border border-stone-100 dark:border-zinc-800/60 rounded-2xl space-y-2">
                            <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-wider">Cari & Saring Nota</p>
                            <div class="flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <!-- Search -->
                                    <div class="relative flex-1">
                                        <span class="material-symbols-outlined absolute left-2.5 top-2 text-[16px] text-stone-400 dark:text-zinc-400">search</span>
                                        <input type="text" 
                                               x-model="invoiceSearchQuery" 
                                               placeholder="Cari nomor/deskripsi..." 
                                               class="w-full pl-8 pr-2 py-1.5 bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 focus:border-emerald-500 rounded-xl text-[10.5px] font-medium outline-none text-stone-850 dark:text-white" />
                                    </div>
                                    <!-- Status -->
                                    <select x-model="invoiceStatusFilter" 
                                            class="bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 rounded-xl px-2 py-1.5 text-[10.5px] font-bold text-stone-700 dark:text-zinc-300 focus:outline-none">
                                        <option value="all">Semua Status</option>
                                        <option value="overdue">Jatuh Tempo</option>
                                        <option value="unpaid">Unpaid</option>
                                        <option value="partial">Partial</option>
                                    </select>
                                </div>
                                
                                <!-- Date Range Filters -->
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5 flex-1 min-w-0">
                                        <span class="text-[9px] font-bold text-stone-400 dark:text-zinc-500 uppercase flex-shrink-0">Dari:</span>
                                        <input type="date" 
                                               x-model="invoiceStartDate" 
                                               class="w-full bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 rounded-xl px-2 py-1 text-[10px] font-bold outline-none text-stone-700 dark:text-zinc-350 focus:border-emerald-500" />
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-1 min-w-0">
                                        <span class="text-[9px] font-bold text-stone-400 dark:text-zinc-500 uppercase flex-shrink-0">Hingga:</span>
                                        <input type="date" 
                                               x-model="invoiceEndDate" 
                                               class="w-full bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 rounded-xl px-2 py-1 text-[10px] font-bold outline-none text-stone-700 dark:text-zinc-350 focus:border-emerald-500" />
                                    </div>
                                    <!-- Clear Dates Button -->
                                    <button type="button" 
                                            @click="invoiceStartDate = ''; invoiceEndDate = '';" 
                                            x-show="invoiceStartDate || invoiceEndDate"
                                            class="text-[9px] font-extrabold text-rose-500 hover:text-rose-600 transition-colors flex items-center gap-0.5 flex-shrink-0">
                                        <span class="material-symbols-outlined text-[12px] font-black">close</span>
                                        <span>Reset Tgl</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active Invoices List -->
                        <div class="space-y-3" id="tour-invoice-selector">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[10.5px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm">receipt_long</span>
                                    Nota Tagihan Aktif (<span x-text="getFilteredInvoices().length"></span>)
                                </h4>
                                <!-- Select All Filtered Checkbox -->
                                <button type="button" 
                                        @click="toggleSelectAllFiltered()" 
                                        class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-0.5">
                                    <span x-text="getFilteredInvoices().every(inv => selectedInvoiceIds.includes(inv.id)) ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-1 custom-scrollbar pt-1 pb-2 px-1">
                                <template x-for="inv in getFilteredInvoices()" :key="inv.id">
                                    <div @click="selectInvoice(inv.id)"
                                         class="p-3.5 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between gap-3 bg-white dark:bg-zinc-900"
                                         :class="selectedInvoiceIds.includes(inv.id) ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 ring-1 ring-emerald-500' : 'border-stone-100 dark:border-zinc-800/60 hover:bg-stone-50/60 dark:hover:bg-zinc-800/40'">
                                        
                                        <!-- Card Header: Checkbox + Title -->
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <!-- Checkbox Box -->
                                            <div class="mt-0.5 flex-shrink-0">
                                                <div class="w-4.5 h-4.5 rounded border flex items-center justify-center transition-all"
                                                     :class="selectedInvoiceIds.includes(inv.id) ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-stone-300 dark:border-zinc-700 bg-transparent'">
                                                    <span class="material-symbols-outlined text-[11px] font-bold" x-show="selectedInvoiceIds.includes(inv.id)">check</span>
                                                </div>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-xs font-bold text-stone-850 dark:text-white truncate" x-text="inv.invoice_number"></p>
                                                    <span class="text-[9px] text-stone-400 dark:text-zinc-500 font-semibold flex-shrink-0" x-text="inv.date"></span>
                                                </div>
                                                <p class="text-[10px] text-stone-500 dark:text-zinc-400 truncate mt-1" x-text="inv.description"></p>
                                            </div>
                                        </div>

                                        <!-- Card Footer: Remaining Amount & Status -->
                                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-stone-100 dark:border-zinc-800/40 pl-7">
                                            <p class="text-xs font-black text-stone-700 dark:text-zinc-200" x-text="formatRupiah(inv.remaining_amount)"></p>
                                            <span class="inline-block text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded"
                                                  :class="inv.is_overdue ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-500' : 'bg-stone-100 dark:bg-zinc-850 text-stone-550 dark:text-zinc-350'"
                                                  x-text="inv.due_label">
                                            </span>
                                        </div>
                                    </div>
                                </template>
                                
                                <!-- Empty Filter State -->
                                <div x-show="getFilteredInvoices().length === 0"
                                     class="col-span-1 sm:col-span-2 p-6 text-center text-[10.5px] font-semibold text-stone-400 dark:text-zinc-500 bg-stone-50 dark:bg-zinc-850 rounded-2xl border border-dashed border-stone-200 dark:border-zinc-800">
                                    Tidak ada nota tagihan yang cocok dengan filter.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment History Section -->
                        <div class="space-y-3 pt-2 border-t border-stone-100 dark:border-zinc-800/60">
                            <h4 class="text-[10.5px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">history</span>
                                Riwayat Pembayaran Terbaru
                            </h4>
                            
                            <div class="space-y-2 max-h-40 overflow-y-auto pr-1 custom-scrollbar">
                                <template x-for="p in (getActiveCustomer() ? getActiveCustomer().payments : [])" :key="p.id">
                                    <div class="p-3 rounded-xl bg-stone-50/60 dark:bg-zinc-850 border border-stone-100 dark:border-zinc-800/60 flex items-center justify-between gap-3 text-xs">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-stone-700 dark:text-zinc-250" x-text="p.invoice_number"></span>
                                                <span class="text-[9px] text-stone-400 dark:text-zinc-500 font-semibold" x-text="p.payment_date"></span>
                                            </div>
                                            <p class="text-[9px] text-stone-450 dark:text-zinc-500 mt-1" x-text="'Metode: ' + p.payment_method"></p>
                                        </div>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400 flex-shrink-0" x-text="'+ ' + formatRupiah(p.amount_paid)"></span>
                                    </div>
                                </template>
                                
                                <!-- Empty History State -->
                                <template x-if="getActiveCustomer() && getActiveCustomer().payments.length === 0">
                                    <div class="p-4 text-center text-[10px] text-stone-400 dark:text-zinc-500 font-semibold">
                                        Belum ada riwayat angsuran cicilan.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <!-- RIGHT COLUMN: Payment Posting Form -->
                    <div class="p-5 bg-stone-50/40 dark:bg-zinc-850 rounded-[2rem] border border-stone-100 dark:border-zinc-800/60 space-y-4">
                        <div class="space-y-1">
                            <span class="text-[9px] font-bold tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-full uppercase">Form Pembayaran</span>
                            <h4 class="text-sm font-black text-stone-850 dark:text-white mt-2">Input Nominal Pembayaran</h4>
                        </div>

                        <!-- Selected Invoices Counter Box -->
                        <div class="p-4 bg-white dark:bg-zinc-900 border border-stone-100 dark:border-zinc-800/60 rounded-2xl space-y-2 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-stone-500 dark:text-zinc-450 font-bold">Nota Terpilih:</span>
                                <span class="font-extrabold text-stone-850 dark:text-white" x-text="selectedInvoiceIds.length + ' Nota'"></span>
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-stone-100 dark:border-zinc-800/40">
                                <span class="text-stone-500 dark:text-zinc-450 font-bold">Total Sisa Tagihan Nota Terpilih:</span>
                                <span class="font-black text-rose-500 dark:text-rose-450" 
                                      x-text="getActiveCustomer() ? formatRupiah(getActiveCustomer().invoices.filter(i => selectedInvoiceIds.includes(i.id)).reduce((sum, i) => sum + i.remaining_amount, 0)) : 'Rp 0'">
                                </span>
                            </div>
                        </div>
                        
                        <!-- Payment Form itself -->
                        <form :action="getActiveCustomer() ? '/catatan-utang/pelanggan/' + selectedCustomerId + '/bayar-banyak' : '#'" 
                              method="POST" class="space-y-4">
                            @csrf
                            
                            <!-- Selected Invoice IDs list -->
                            <template x-for="id in selectedInvoiceIds" :key="id">
                                <input type="hidden" name="selected_debts[]" :value="id" />
                            </template>
                            
                            <!-- Nominal Amount Input -->
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Nominal Pembayaran</label>
                                <div class="relative flex items-center" id="tour-quick-amounts">
                                    <span class="absolute left-4 text-stone-500 dark:text-white font-bold text-base">Rp</span>
                                    <input type="text" 
                                           x-model="displayAmount"
                                           @input="
                                               let raw = $event.target.value.replace(/\D/g, '');
                                               let maxAmt = getActiveCustomer() ? getActiveCustomer().invoices.filter(i => selectedInvoiceIds.includes(i.id)).reduce((sum, i) => sum + i.remaining_amount, 0) : 0;
                                               amountToPay = raw ? Math.min(parseInt(raw), maxAmt) : 0;
                                               $event.target.value = amountToPay ? new Intl.NumberFormat('id-ID').format(amountToPay) : '';
                                           "
                                           required 
                                           class="w-full pl-11 pr-4 py-3 bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-2xl text-lg font-bold text-stone-850 dark:text-white outline-none transition-all font-mono" />
                                    <input type="hidden" name="amount_paid" :value="amountToPay" />
                                </div>
                                <p class="text-[9.5px] text-stone-400 dark:text-zinc-505 font-semibold mt-1">
                                    Pembayaran akan secara otomatis didistribusikan pada nota terpilih mulai dari yang paling lama (FIFO).
                                </p>
                            </div>
                            
                            <!-- Quick Cash Pills -->
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="setQuickAmount('lunas')"
                                        class="px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-400 rounded-lg text-[9px] font-bold transition-all shadow-sm border border-emerald-100 dark:border-emerald-900/30">
                                    Bayar Semua (Lunas)
                                </button>
                                <button type="button" @click="setQuickAmount(50000)"
                                        class="px-2.5 py-1.5 bg-white dark:bg-zinc-900 hover:bg-stone-50 dark:hover:bg-zinc-800 text-stone-600 dark:text-white rounded-lg text-[9px] font-bold transition-all shadow-sm border border-stone-200/50 dark:border-zinc-800">
                                    50.000
                                </button>
                                <button type="button" @click="setQuickAmount(100000)"
                                        class="px-2.5 py-1.5 bg-white dark:bg-zinc-900 hover:bg-stone-50 dark:hover:bg-zinc-800 text-stone-600 dark:text-white rounded-lg text-[9px] font-bold transition-all shadow-sm border border-stone-200/50 dark:border-zinc-800">
                                    100.000
                                </button>
                                <button type="button" @click="setQuickAmount(250000)"
                                        class="px-2.5 py-1.5 bg-white dark:bg-zinc-900 hover:bg-stone-50 dark:hover:bg-zinc-800 text-stone-600 dark:text-white rounded-lg text-[9px] font-bold transition-all shadow-sm border border-stone-200/50 dark:border-zinc-800">
                                    250.000
                                </button>
                            </div>
                            
                            <!-- Payment Method Selector -->
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Metode Pembayaran</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label class="border rounded-xl p-2 flex flex-col items-center justify-center gap-1 cursor-pointer transition-all bg-white dark:bg-zinc-900"
                                           :class="paymentMethod === 'cash' ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500' : 'border-stone-200 dark:border-zinc-800 text-stone-500 dark:text-zinc-450 hover:bg-stone-50/65'">
                                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="sr-only" required />
                                        <span class="material-symbols-outlined text-lg">payments</span>
                                        <span class="text-[10px] font-bold">Tunai</span>
                                    </label>
                                    <label class="border rounded-xl p-2 flex flex-col items-center justify-center gap-1 cursor-pointer transition-all bg-white dark:bg-zinc-900"
                                           :class="paymentMethod === 'transfer' ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500' : 'border-stone-200 dark:border-zinc-800 text-stone-500 dark:text-zinc-450 hover:bg-stone-50/65'">
                                        <input type="radio" name="payment_method" value="transfer" x-model="paymentMethod" class="sr-only" required />
                                        <span class="material-symbols-outlined text-lg">account_balance</span>
                                        <span class="text-[10px] font-bold">Transfer</span>
                                    </label>
                                    <label class="border rounded-xl p-2 flex flex-col items-center justify-center gap-1 cursor-pointer transition-all bg-white dark:bg-zinc-900"
                                           :class="paymentMethod === 'qris' ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500' : 'border-stone-200 dark:border-zinc-800 text-stone-500 dark:text-zinc-450 hover:bg-stone-50/65'">
                                        <input type="radio" name="payment_method" value="qris" x-model="paymentMethod" class="sr-only" required />
                                        <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                                        <span class="text-[10px] font-bold">QRIS</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Payment Date -->
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Tanggal Transaksi</label>
                                <input type="date" 
                                       name="payment_date" 
                                       x-model="paymentDate"
                                       required
                                       class="w-full bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl p-2.5 text-xs font-semibold text-stone-750 dark:text-zinc-200 outline-none transition-all" />
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit"
                                    id="tour-submit-button"
                                    :disabled="selectedInvoiceIds.length === 0 || amountToPay <= 0"
                                    class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:bg-stone-100 dark:disabled:bg-zinc-800 disabled:text-stone-400 dark:disabled:text-zinc-500 disabled:shadow-none text-white font-bold py-3.5 px-6 rounded-2xl shadow-md shadow-emerald-500/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-900/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 mt-2">
                                <span class="material-symbols-outlined">receipt_long</span>
                                <span class="text-sm font-bold">Proses Terima Uang</span>
                            </button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- PAYMENT SUCCESS MODAL (RECEIPT SUCCESS POPUP) -->
    <div x-show="isPaymentSuccessModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-stone-900/60 dark:bg-black/70 backdrop-blur-md" @click="isPaymentSuccessModalOpen = false"></div>
        
        <!-- Modal Content Box -->
        <div class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[2rem] shadow-2xl border border-stone-200/60 dark:border-zinc-800/80 max-w-sm w-full mx-4 text-center relative z-10 animate-scale-up"
             x-show="isPaymentSuccessModalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="isPaymentSuccessModalOpen = false">
            
            <!-- Success Icon Animation -->
            <div class="mx-auto w-16 h-16 bg-emerald-50 dark:bg-emerald-950/40 text-[#0b6e4f] dark:text-emerald-400 rounded-full flex items-center justify-center mb-6 shadow-inner animate-bounce">
                <span class="material-symbols-outlined text-4xl font-bold">check_circle</span>
            </div>

            <!-- Header -->
            <div class="mb-5 space-y-1">
                <h3 class="text-xl font-bold text-stone-850 dark:text-white">Pembayaran Berhasil!</h3>
                <p class="text-[9.5px] font-bold text-stone-400 dark:text-zinc-500 uppercase tracking-widest">Cicilan Piutang Telah Dicatat</p>
            </div>

            <!-- Breakdown Detail Box -->
            <div class="p-4 bg-stone-50 dark:bg-zinc-850 border border-stone-100 dark:border-zinc-800/40 rounded-2xl text-left space-y-2 mb-6 text-xs font-semibold">
                <div class="flex justify-between items-center">
                    <span class="text-stone-450 dark:text-zinc-450">Pelanggan:</span>
                    <span class="font-extrabold text-stone-800 dark:text-white">{{ session('payment_customer') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-stone-450 dark:text-zinc-450">Jumlah Bayar:</span>
                    <span class="font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format(session('payment_amount', 0), 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-stone-450 dark:text-zinc-450">Metode Bayar:</span>
                    <span class="font-extrabold text-stone-800 dark:text-white">
                        @if(session('payment_method') === 'cash') Tunai @elseif(session('payment_method') === 'transfer') Transfer @elseif(session('payment_method') === 'qris') QRIS @else {{ strtoupper(session('payment_method')) }} @endif
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-stone-450 dark:text-zinc-450">Tanggal:</span>
                    <span class="font-bold text-stone-700 dark:text-zinc-350">
                        {{ session('payment_date') ? \Carbon\Carbon::parse(session('payment_date'))->translatedFormat('d M Y') : '' }}
                    </span>
                </div>
            </div>

            <!-- Description message -->
            <p class="text-stone-500 dark:text-white text-[11px] font-medium leading-relaxed mb-6">
                Pembayaran cicilan kasbon ini telah sukses didistribusikan ke dalam pembukuan kas sistem secara real-time.
            </p>

            <!-- Actions -->
            <div class="flex flex-col gap-2">
                <button type="button" 
                        @click="isPaymentSuccessModalOpen = false"
                        class="w-full py-3.5 bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] text-white text-xs font-bold rounded-2xl shadow-md active:scale-[0.98] transition-all duration-200 cursor-pointer">
                    Selesai (Tutup)
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnStartTour = document.getElementById('btn-start-tour');
        if (!btnStartTour) return;

        btnStartTour.addEventListener('click', () => {
            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
            if (alpineData) {
                let first = alpineData.customers.find(c => c.invoices.length > 0);
                if (first) {
                    alpineData.openPaymentModal(first.id);
                }
            }

            setTimeout(() => {
                const driver = window.driver.js.driver;
                
                const driverObj = driver({
                    showProgress: true,
                    steps: [
                        {
                            element: '#tour-search-bar',
                            popover: {
                                title: 'Cari Pelanggan',
                                description: 'Ketik nama pelanggan katering atau kasbon di sini untuk memfilter kartu secara instan.',
                                side: 'bottom',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-customer-list',
                            popover: {
                                title: 'Daftar Kartu Pelanggan',
                                description: 'Tampilkan total tagihan aktif dan sisa saldo. Klik tombol detail untuk memunculkan popup pembayaran.',
                                side: 'top',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-invoice-selector',
                            popover: {
                                title: 'Pilih & Filter Nota',
                                description: 'Pilih nota-nota tagihan spesifik yang ingin dicicil bersama dengan mencentang kotak pilihan.',
                                side: 'right',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-quick-amounts',
                            popover: {
                                title: 'Input Nominal & Metode',
                                description: 'Sistem otomatis menjumlahkan tagihan dari nota-nota terpilih. Masukkan nominal yang dibayarkan dan pilih metode bayar.',
                                side: 'left',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-submit-button',
                            popover: {
                                title: 'Proses Terima Uang',
                                description: 'Klik tombol hijau ini untuk menyimpan cicilan kasbon ke dalam pembukuan kas sistem secara instan.',
                                side: 'top',
                                align: 'center'
                            }
                        }
                    ]
                });

                driverObj.drive();
            }, 350);
        });
    });
</script>
@endsection

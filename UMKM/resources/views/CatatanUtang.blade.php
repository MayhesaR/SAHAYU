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
                                'total_amount' => (float)$d->total_amount,
                                'remaining_amount' => (float)$d->remaining_amount,
                                'due_date' => $due->translatedFormat('d M Y'),
                                'due_text' => $dueText,
                                'due_class' => $dueClass,
                                'is_overdue' => $daysDiff < 0,
                                'date' => \Carbon\Carbon::parse($d->created_at)->translatedFormat('d M Y'),
                                'description' => $productDesc,
                                'pay_route' => route('debts.pay', $d),
                                'status' => $d->status
                            ];
                        }
                    }
                @endphp
                {
                    id: {{ $customerId }},
                    name: '{{ addslashes($customer->name ?? 'Umum') }}',
                    phone: '{{ $customer->phone ?? 'Tidak ada kontak' }}',
                    initials: '{{ strtoupper(substr($customer->name ?? 'Umum', 0, 2)) }}',
                    total_remaining: {{ $totalRemaining }},
                    total_amount: {{ $totalAmount }},
                    last_transaction: '{{ $lastTransactionDate }}',
                    invoices: {!! json_encode($invoices) !!}
                },
            @endforeach
        ]
    };
</script>

<div class="px-4 py-6 md:py-8 sm:px-8 max-w-7xl mx-auto space-y-6" 
     x-data="{
        searchQuery: '',
        selectedCustomerId: null,
        selectedDebtId: null,
        amountToPay: 0,
        paymentMethod: 'cash',
        paymentDate: '{{ now()->toDateString() }}',
        showAdvancedFilters: false,
        customers: window.debtData.customers,
        
        init() {
            // Select first customer with active invoices by default
            let first = this.customers.find(c => c.invoices.length > 0);
            if (first) {
                this.selectCustomer(first.id);
            }
        },
        
        selectCustomer(id) {
            this.selectedCustomerId = id;
            let cust = this.customers.find(c => c.id === id);
            if (cust && cust.invoices.length > 0) {
                this.selectInvoice(cust.invoices[0].id);
            } else {
                this.selectedDebtId = null;
                this.amountToPay = 0;
            }
        },
        
        selectInvoice(id) {
            this.selectedDebtId = id;
            let activeInv = this.getActiveInvoice();
            if (activeInv) {
                this.amountToPay = activeInv.remaining_amount;
            }
        },
        
        getActiveCustomer() {
            return this.customers.find(c => c.id === this.selectedCustomerId);
        },
        
        getActiveInvoice() {
            let cust = this.getActiveCustomer();
            if (cust) {
                return cust.invoices.find(i => i.id === this.selectedDebtId);
            }
            return null;
        },
        
        setQuickAmount(amount) {
            let activeInv = this.getActiveInvoice();
            if (activeInv) {
                if (amount === 'lunas') {
                    this.amountToPay = activeInv.remaining_amount;
                } else {
                    this.amountToPay = Math.min(amount, activeInv.remaining_amount);
                }
            }
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
                POS Kasir Piutang Pelanggan
            </h1>
            <p class="text-stone-500 dark:text-white font-medium text-xs md:text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-xs md:text-sm">receipt_long</span>
                Terima angsuran kasbon pelanggan secara instan dengan antarmuka kasir.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.index') }}" 
               class="w-full sm:w-auto px-5 py-2.5 md:py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-900/10 transition-all duration-200 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                <span>POS Penjualan Baru</span>
            </a>
        </div>
    </div>

    <!-- Stats Ribbon (Emerald Soft-Touch Cards) -->
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
                Pencarian & Penyaringan Lanjutan
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
                    <select name="status" class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs font-semibold text-stone-700 dark:text-zinc-50 dark:text-zinc-200 focus:outline-none focus:border-emerald-500">
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
                           class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs font-semibold text-stone-700 dark:text-zinc-50 dark:text-zinc-200 focus:outline-none focus:border-emerald-500" />
                </div>

                <!-- End Due Date -->
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold uppercase text-stone-400 dark:text-zinc-400">Jatuh Tempo Hingga</label>
                    <input type="date" name="end_due_date" value="{{ request()->query('end_due_date') }}"
                           class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs font-semibold text-stone-700 dark:text-zinc-50 dark:text-zinc-200 focus:outline-none focus:border-emerald-500" />
                </div>

                <!-- Actions -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 bg-stone-800 text-white font-bold text-xs rounded-xl hover:bg-stone-900 dark:hover:bg-zinc-800 transition-all flex items-center justify-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-[14px]">filter_list</span> Saring
                    </button>
                    <a href="{{ route('debts.index') }}" class="flex-1 py-2 bg-stone-100 dark:bg-zinc-800 text-stone-600 dark:text-white font-bold text-xs rounded-xl hover:bg-stone-200 dark:hover:bg-zinc-800 border border-stone-200 dark:border-zinc-800 transition-all text-center flex items-center justify-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- MAIN POS SPLIT-SCREEN WORKSPACE -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        
        <!-- LEFT PANEL: Customer List (60%) -->
        <div class="lg:col-span-3 space-y-4">
            
            <!-- Quick Search Bar -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-4 top-3 text-stone-400 dark:text-zinc-400">search</span>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari nama pelanggan..." 
                       class="w-full pl-11 pr-4 py-3 bg-white dark:bg-zinc-900 border border-stone-200 dark:border-zinc-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-2xl text-xs font-semibold text-stone-800 dark:text-white outline-none transition-all placeholder-stone-400 shadow-sm" />
            </div>

            <!-- Customer Cards Container -->
            <div class="lg:max-h-[580px] overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                <template x-for="cust in customers.filter(c => c.name.toLowerCase().includes(searchQuery.toLowerCase()))" :key="cust.id">
                    <div @click="selectCustomer(cust.id)"
                         class="p-4 bg-white dark:bg-zinc-900 rounded-2xl border transition-all cursor-pointer flex items-center justify-between gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 duration-200"
                         :class="selectedCustomerId === cust.id ? 'ring-2 ring-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20 border-emerald-200' : 'border-stone-200/60 dark:border-zinc-800/80'">
                        
                        <!-- Left Info -->
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Initials Avatar -->
                            <div class="w-10 h-10 rounded-xl font-bold text-sm flex items-center justify-center flex-shrink-0 shadow-sm transition-all duration-200"
                                 :class="selectedCustomerId === cust.id ? 'bg-emerald-500 text-white' : 'bg-stone-100 dark:bg-zinc-800 text-stone-700 dark:text-zinc-200'">
                                <span x-text="cust.initials"></span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-stone-800 dark:text-white text-sm truncate" x-text="cust.name"></h4>
                                <p class="text-[10px] text-stone-400 dark:text-zinc-400 font-semibold truncate mt-0.5" x-text="cust.phone"></p>
                            </div>
                        </div>

                        <!-- Right Info -->
                        <div class="text-right flex-shrink-0">
                            <span class="inline-block px-3 py-1.5 bg-rose-50 dark:bg-rose-950/40 text-rose-500 dark:text-rose-400 rounded-full font-bold text-xs shadow-sm shadow-rose-900/5"
                                  x-text="formatRupiah(cust.total_remaining)">
                            </span>
                            <p class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold mt-1" x-text="cust.invoices.length + ' Nota Aktif'"></p>
                        </div>
                    </div>
                </template>

                <!-- No Results Empty State -->
                <div x-show="customers.filter(c => c.name.toLowerCase().includes(searchQuery.toLowerCase())).length === 0"
                     class="p-8 text-center text-stone-400 dark:text-zinc-400 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 rounded-2xl border border-dashed border-stone-200 dark:border-zinc-800">
                    <span class="material-symbols-outlined text-3xl text-stone-300 mb-2">person_search</span>
                    <p class="text-xs font-semibold">Tidak ada pelanggan dengan tagihan aktif.</p>
                </div>
            </div>

            <!-- Laravel Pagination Links -->
            <div class="pt-2">
                {{ $debts->appends(request()->query())->links() }}
            </div>
        </div>

        <!-- RIGHT PANEL: Checkout Panel (40%) -->
        <div class="lg:col-span-2">
            <div class="lg:sticky lg:top-[88px] space-y-4">
                
                <!-- No Customer Selected State -->
                <div x-show="!selectedCustomerId" 
                     class="bg-white dark:bg-zinc-900 p-8 rounded-3xl border border-stone-200/60 dark:border-zinc-800/80 shadow-lg shadow-emerald-900/5 text-center flex flex-col items-center justify-center h-96">
                    <div class="w-16 h-16 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 rounded-full flex items-center justify-center text-stone-300 mb-4 shadow-inner">
                        <span class="material-symbols-outlined text-4xl">payments</span>
                    </div>
                    <h3 class="text-sm font-bold text-stone-700 dark:text-zinc-50 dark:text-zinc-200">Pilih Pelanggan</h3>
                    <p class="text-xs text-stone-400 dark:text-zinc-400 mt-1 max-w-[200px] leading-relaxed">Silakan pilih nama pelanggan di sebelah kiri untuk menginput nominal angsuran kasbon.</p>
                </div>

                <!-- Customer Selected & Active Checkout Panel -->
                <div x-show="selectedCustomerId" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="bg-white dark:bg-zinc-900 p-6 rounded-3xl border border-stone-200/60 dark:border-zinc-800/80 shadow-lg shadow-emerald-900/5 space-y-5">
                    
                    <!-- Checkout Header Info -->
                    <div class="pb-4 border-b border-stone-100 dark:border-zinc-800/60">
                        <span class="text-[9px] font-bold tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-full uppercase">Detail Pembayaran</span>
                        <h3 class="text-lg font-bold text-stone-800 dark:text-white mt-3" x-text="getActiveCustomer() ? getActiveCustomer().name : ''"></h3>
                        <div class="flex justify-between items-center mt-3 p-3.5 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 rounded-2xl border border-stone-100 dark:border-zinc-800/60 shadow-inner">
                            <span class="text-xs text-stone-500 dark:text-zinc-400 font-semibold">Total Tagihan Akumulatif:</span>
                            <span class="text-base font-bold text-rose-500 dark:text-rose-400" x-text="getActiveCustomer() ? formatRupiah(getActiveCustomer().total_remaining) : 'Rp 0'"></span>
                        </div>
                    </div>

                    <!-- Unpaid Invoice List -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Pilih Nota Invoice</label>
                            <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold" x-text="(getActiveCustomer() ? getActiveCustomer().invoices.length : 0) + ' nota belum lunas'"></span>
                        </div>
                        
                        <div class="max-h-40 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                            <template x-for="inv in (getActiveCustomer() ? getActiveCustomer().invoices : [])" :key="inv.id">
                                <div @click="selectInvoice(inv.id)"
                                     class="p-3 bg-white dark:bg-zinc-900 border rounded-xl cursor-pointer transition-all flex items-center justify-between gap-3"
                                     :class="selectedDebtId === inv.id ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 ring-1 ring-emerald-500' : 'border-stone-100 dark:border-zinc-800/60 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800'">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-bold text-stone-700 dark:text-zinc-50 dark:text-white text-xs font-mono" x-text="'#' + String(inv.sale_id || inv.id).padStart(5, '0')"></span>
                                            <!-- Status Badges -->
                                            <span x-show="inv.is_overdue" class="text-[8px] bg-rose-100 text-rose-600 dark:text-rose-400 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider">Terlambat</span>
                                            <span x-show="!inv.is_overdue && inv.status === 'unpaid'" class="text-[8px] bg-amber-100 text-amber-700 dark:text-amber-400 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider">Belum Bayar</span>
                                            <span x-show="!inv.is_overdue && inv.status === 'partial'" class="text-[8px] bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider">Cicilan</span>
                                        </div>
                                        <p class="text-[10px] text-stone-400 dark:text-zinc-400 font-medium truncate mt-0.5" x-text="inv.description"></p>
                                        <p class="text-[10px] mt-1" :class="inv.due_class" x-text="inv.due_text"></p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="font-bold text-stone-800 dark:text-white text-xs" x-text="formatRupiah(inv.remaining_amount)"></p>
                                        <p class="text-[8px] text-stone-400 dark:text-zinc-400 font-bold mt-0.5" x-text="'Dibuat: ' + inv.date"></p>
                                    </div>
                                </div>
                            </template>

                            <!-- If customer has no unpaid invoices (i.e. all paid) -->
                            <div x-show="getActiveCustomer() && getActiveCustomer().invoices.length === 0"
                                 class="p-4 text-center text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-xs font-bold border border-emerald-100">
                                <span class="material-symbols-outlined text-sm align-middle mr-1">check_circle</span>
                                Semua tagihan customer ini telah lunas!
                            </div>
                        </div>
                    </div>

                    <!-- Payment Posting Form -->
                    <form x-bind:action="getActiveInvoice() ? getActiveInvoice().pay_route : '#'" method="POST" class="space-y-4">
                        @csrf

                        <!-- Payment Amount Field -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Nominal Pembayaran</label>
                            <div class="relative flex items-center">
                                <span class="absolute left-4 text-stone-500 dark:text-white font-bold text-base">Rp</span>
                                <input type="number" 
                                       name="amount_paid" 
                                       x-model.number="amountToPay"
                                       x-bind:max="getActiveInvoice() ? getActiveInvoice().remaining_amount : 0"
                                       min="1"
                                       required 
                                       class="w-full pl-11 pr-4 py-3 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 focus:bg-white dark:focus:bg-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-2xl text-lg font-bold text-stone-800 dark:text-white outline-none transition-all font-mono" />
                            </div>
                        </div>

                        <!-- Quick Cash Pills -->
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" @click="setQuickAmount('lunas')"
                                    class="px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-400 rounded-lg text-[10px] font-bold transition-all shadow-sm border border-emerald-100">
                                Bayar Semua (Lunas)
                            </button>
                            <button type="button" @click="setQuickAmount(50000)"
                                    class="px-2.5 py-1.5 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 text-stone-600 dark:text-white rounded-lg text-[10px] font-bold transition-all shadow-sm border border-stone-200/50 dark:border-zinc-850">
                                50.000
                            </button>
                            <button type="button" @click="setQuickAmount(100000)"
                                    class="px-2.5 py-1.5 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 text-stone-600 dark:text-white rounded-lg text-[10px] font-bold transition-all shadow-sm border border-stone-200/50 dark:border-zinc-850">
                                100.000
                            </button>
                            <button type="button" @click="setQuickAmount(250000)"
                                    class="px-2.5 py-1.5 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 text-stone-600 dark:text-white rounded-lg text-[10px] font-bold transition-all shadow-sm border border-stone-200/50 dark:border-zinc-850">
                                250.000
                            </button>
                            <button type="button" @click="setQuickAmount(500000)"
                                    class="px-2.5 py-1.5 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 text-stone-600 dark:text-white rounded-lg text-[10px] font-bold transition-all shadow-sm border border-stone-200/50 dark:border-zinc-850">
                                500.000
                            </button>
                        </div>

                        <!-- Channel Selector Tabs -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Saluran Pembayaran</label>
                            <div class="grid grid-cols-3 gap-2">
                                <!-- Cash -->
                                <label class="border rounded-xl p-2 flex flex-col items-center justify-center gap-1 cursor-pointer transition-all"
                                       :class="paymentMethod === 'cash' ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500' : 'border-stone-200 dark:border-zinc-800 text-stone-500 dark:text-zinc-400 hover:bg-stone-50/60'">
                                    <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="sr-only" required />
                                    <span class="material-symbols-outlined text-lg">payments</span>
                                    <span class="text-[10px] font-bold">Tunai</span>
                                </label>
                                <!-- Transfer -->
                                <label class="border rounded-xl p-2 flex flex-col items-center justify-center gap-1 cursor-pointer transition-all"
                                       :class="paymentMethod === 'transfer' ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500' : 'border-stone-200 dark:border-zinc-800 text-stone-500 dark:text-zinc-400 hover:bg-stone-50/60'">
                                    <input type="radio" name="payment_method" value="transfer" x-model="paymentMethod" class="sr-only" required />
                                    <span class="material-symbols-outlined text-lg">account_balance</span>
                                    <span class="text-[10px] font-bold">Transfer</span>
                                </label>
                                <!-- QRIS -->
                                <label class="border rounded-xl p-2 flex flex-col items-center justify-center gap-1 cursor-pointer transition-all"
                                       :class="paymentMethod === 'qris' ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500' : 'border-stone-200 dark:border-zinc-800 text-stone-500 dark:text-zinc-400 hover:bg-stone-50/60'">
                                    <input type="radio" name="payment_method" value="qris" x-model="paymentMethod" class="sr-only" required />
                                    <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                                    <span class="text-[10px] font-bold">QRIS</span>
                                </label>
                            </div>
                        </div>

                        <!-- Date input -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Tanggal Transaksi</label>
                            <input type="date" 
                                   name="payment_date" 
                                   x-model="paymentDate"
                                   required
                                   class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 focus:bg-white dark:focus:bg-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl p-2.5 text-xs font-semibold text-stone-700 dark:text-zinc-50 dark:text-zinc-200 outline-none transition-all" />
                        </div>

                        <!-- Submit Receipt Payment -->
                        <button type="submit"
                                x-bind:disabled="!getActiveInvoice() || amountToPay <= 0"
                                class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:bg-stone-100 dark:disabled:bg-zinc-800 disabled:text-stone-400 dark:disabled:text-zinc-300 dark:disabled:text-white disabled:shadow-none text-white font-bold py-3.5 px-6 rounded-2xl shadow-md shadow-emerald-500/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-900/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 mt-2">
                            <span class="material-symbols-outlined">receipt_long</span>
                            <span class="text-sm">Terima Pembayaran</span>
                        </button>
                    </form>
                </div>
                
            </div>
        </div>

    </div>

</div>
@endsection

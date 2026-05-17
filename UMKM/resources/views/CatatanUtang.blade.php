@extends('layouts.app')
@section('title', 'Piutang & Kasbon Pelanggan')
@section('page_title', 'Piutang / Kasbon Pelanggan')

@section('content')
<div class="px-4 py-8 sm:px-8 max-w-7xl mx-auto space-y-8" 
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
    <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight font-manrope">
                Piutang & Kasbon Pelanggan
            </h1>
            <p class="text-slate-500 font-medium text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">menu_book</span>
                Manajemen CRM tagihan, piutang tempo, dan angsuran cicilan pelanggan.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.index') }}" 
               class="px-5 py-3 bg-emerald-600 text-white font-black text-xs rounded-xl shadow-md hover:bg-emerald-700 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                <span>Kasir POS Baru</span>
            </a>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Stat 1: Total Outstanding -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xl relative overflow-hidden group">
            <div class="w-10 h-10 bg-amber-50 text-amber-700 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Sisa Piutang Aktif</p>
            <h3 class="text-3xl font-black text-slate-800 mt-1">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-slate-400 font-bold mt-4 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                <span>Tagihan kumulatif belum terbayar</span>
            </p>
        </div>

        <!-- Stat 2: Overdue Count -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xl relative overflow-hidden group {{ $overdueCount > 0 ? 'border-rose-100 bg-rose-50/10' : '' }}">
            <div class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl font-bold">event_busy</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest {{ $overdueCount > 0 ? 'text-rose-600' : '' }}">Lewati Jatuh Tempo</p>
            <h3 class="text-3xl font-black {{ $overdueCount > 0 ? 'text-rose-600' : 'text-slate-800' }} mt-1">{{ $overdueCount }} Pelanggan</h3>
            <p class="text-[10px] {{ $overdueCount > 0 ? 'text-rose-500' : 'text-slate-400' }} font-bold mt-4 flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px]">schedule</span>
                <span>Membutuhkan follow-up segera</span>
            </p>
        </div>

        <!-- Stat 3: Maximum Plafon -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xl relative overflow-hidden group">
            <div class="w-10 h-10 bg-teal-50 text-teal-700 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">contacts</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Batas Limit Kredit</p>
            <h3 class="text-3xl font-black text-slate-800 mt-1">Rp 5.000.000</h3>
            <p class="text-[10px] text-slate-400 font-bold mt-4">Plafon acuan operasional kasbon</p>
        </div>
    </div>

    <!-- Live CRM Search & Filters Panel (Easy-to-tap UI) -->
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xl space-y-4">
        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Penyaringan & Penelusuran Cepat</h3>
        <form action="{{ route('debts.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
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

            <!-- Due Date Picker -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase text-slate-400">Jatuh Tempo Pada</label>
                <input type="date" name="due_date" value="{{ request()->query('due_date') }}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none focus:border-emerald-600" />
            </div>

            <!-- Form Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-4 bg-slate-800 text-white font-black text-xs rounded-xl hover:bg-slate-900 transition-all flex items-center justify-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[14px]">filter_list</span> Saring
                </button>
                <a href="{{ route('debts.index') }}" class="flex-1 py-2 px-4 bg-slate-100 text-slate-600 font-black text-xs rounded-xl hover:bg-slate-200 border border-slate-200 transition-all flex items-center justify-center gap-1.5 shadow-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Active Debts Ledger Table -->
    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h4 class="text-base font-black text-slate-800 font-manrope">Buku Besar Ledger Piutang</h4>
                <p class="text-xs text-slate-400 mt-0.5">Daftar terperinci rincian angsuran kasbon pelanggan.</p>
            </div>
            <span class="text-[10px] font-black text-slate-400 bg-white px-3 py-1.5 rounded-full border border-slate-100">
                {{ $debts->total() }} akun terdaftar
            </span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Pelanggan</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Jatuh Tempo</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Limit Plafon</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Sisa Tagihan</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Progres Repayment</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm" x-data="{ openLogs: null }">
                    @forelse ($debts as $d)
                        @php
                            $total = (float) $d->total_amount;
                            $rem = (float) $d->remaining_amount;
                            $paid = $total - $rem;
                            $percent = $total > 0 ? round(($paid / $total) * 100) : 0;
                            
                            // Check if overdue
                            $isOverdue = \Carbon\Carbon::parse($d->due_date)->isPast() && $d->status !== 'paid';
                        @endphp
                        
                        <!-- Main row -->
                        <tr class="hover:bg-slate-50/40 transition-colors group">
                            
                            <!-- Debtor Name -->
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-slate-100 text-slate-700 rounded-full flex items-center justify-center font-black text-sm">
                                        {{ substr($d->customer->name ?? 'P', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800">{{ $d->customer->name ?? 'Umum' }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ $d->customer->phone ?? 'Tidak ada kontak' }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Due date -->
                            <td class="px-6 py-6 font-bold">
                                <div>
                                    <p class="{{ $isOverdue ? 'text-rose-600 font-black' : 'text-slate-600' }}">
                                        {{ \Carbon\Carbon::parse($d->due_date)->translatedFormat('d M Y') }}
                                    </p>
                                    @php
                                        $dueDate = \Carbon\Carbon::parse($d->due_date)->startOfDay();
                                        $today = \Carbon\Carbon::now()->startOfDay();
                                        $diffInDays = $today->diffInDays($dueDate, false);
                                        $diffInDays = (int) round($diffInDays);
                                        $absDiff = abs($diffInDays);
                                    @endphp
                                    <p class="text-[9px] font-black uppercase tracking-wider mt-0.5 {{ $diffInDays < 0 ? 'text-rose-500' : 'text-slate-400' }}">
                                        @if($diffInDays < 0)
                                            Terlewat {{ $absDiff }} hari
                                        @elseif($diffInDays === 0)
                                            Jatuh tempo hari ini
                                        @else
                                            Sisa {{ $absDiff }} hari lagi
                                        @endif
                                    </p>
                                </div>
                            </td>

                            <!-- Plafon / Total amount -->
                            <td class="px-6 py-6 font-bold text-slate-600">
                                Rp {{ number_format($d->total_amount, 0, ',', '.') }}
                            </td>

                            <!-- Remaining amount -->
                            <td class="px-6 py-6 font-black text-slate-900">
                                Rp {{ number_format($d->remaining_amount, 0, ',', '.') }}
                            </td>

                            <!-- repayment progress bar -->
                            <td class="px-6 py-6">
                                <div class="w-32 md:w-40 space-y-1.5">
                                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-500">
                                        <span>Terbayar: {{ $percent }}%</span>
                                        <span>Rp {{ number_format($paid, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/20">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $d->status === 'paid' ? 'bg-emerald-600' : 'bg-amber-500' }}" 
                                             style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-6">
                                @if($d->status === 'paid')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 text-[10px] font-black uppercase tracking-wider rounded-xl border border-emerald-100">
                                        Lunas
                                    </span>
                                @elseif($d->status === 'partial')
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-800 text-[10px] font-black uppercase tracking-wider rounded-xl border border-blue-100">
                                        Dicicil
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-800 text-[10px] font-black uppercase tracking-wider rounded-xl border border-rose-100">
                                        Belum Bayar
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Log History toggle -->
                                    <button @click="openLogs = (openLogs === {{ $d->id }} ? null : {{ $d->id }})" 
                                            type="button" 
                                            class="p-2 text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all" 
                                            title="Histori Angsuran">
                                        <span class="material-symbols-outlined text-lg">history</span>
                                    </button>

                                    <!-- Pay button -->
                                    @if($d->status !== 'paid')
                                        <button @click="openPaymentModal(
                                                    '{{ $d->id }}', 
                                                    '{{ addslashes($d->customer->name ?? '') }}', 
                                                    '{{ $d->total_amount }}', 
                                                    '{{ $d->remaining_amount }}', 
                                                    '{{ route('debts.pay', $d) }}'
                                                )"
                                                type="button"
                                                class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white font-black text-xs rounded-xl shadow-sm transition-all flex items-center gap-1 active:scale-95">
                                            <span class="material-symbols-outlined text-[14px]">price_check</span>
                                            <span>Bayar Cicilan</span>
                                        </button>
                                    @else
                                        <button disabled 
                                                class="px-3.5 py-2 bg-slate-100 text-slate-400 text-xs font-black rounded-xl border border-slate-200/20 flex items-center gap-1 cursor-not-allowed">
                                            <span class="material-symbols-outlined text-[14px]">check</span>
                                            <span>Selesai</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Expandable Installment Payments Log -->
                        <tr x-show="openLogs === {{ $d->id }}" x-cloak class="bg-slate-50/50">
                            <td colspan="7" class="px-8 py-4 border-t border-slate-100">
                                <div class="bg-white p-6 rounded-2xl border border-slate-100 space-y-4 shadow-inner max-w-3xl">
                                    <div class="flex justify-between items-center">
                                        <h5 class="text-xs font-black uppercase tracking-widest text-slate-500">Histori Pembayaran Cicilan</h5>
                                        <span class="text-[9px] font-bold text-slate-400">Total Pinjaman: Rp {{ number_format($d->total_amount, 0, ',', '.') }}</span>
                                    </div>

                                    @if($d->payments->isEmpty())
                                        <p class="text-xs font-semibold text-slate-400 py-2">Belum ada angsuran cicilan yang tercatat untuk akun utang ini.</p>
                                    @else
                                        <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                                            @foreach($d->payments as $p)
                                                <div class="relative flex justify-between items-center text-xs">
                                                    <!-- Dot marker -->
                                                    <span class="absolute -left-[21px] top-1.5 w-2 h-2 rounded-full bg-amber-500 border-2 border-white shadow-sm"></span>
                                                    
                                                    <div class="space-y-0.5">
                                                        <p class="font-black text-slate-800">Pembayaran Angsuran</p>
                                                        <p class="text-[10px] text-slate-400 font-medium">
                                                            Tanggal: {{ \Carbon\Carbon::parse($p->payment_date)->translatedFormat('d F Y') }} 
                                                            via <span class="uppercase font-bold text-slate-600">{{ $p->payment_method }}</span>
                                                        </p>
                                                    </div>
                                                    <span class="font-mono font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                                                        + Rp {{ number_format($p->amount_paid, 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-8 py-6 text-sm text-slate-400 font-semibold text-center">
                                Belum ada data catatan piutang / kasbon yang terdaftar untuk penyaringan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-8 py-4 bg-slate-50 border-t border-slate-100">
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

@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight font-manrope">
                Catat Pengeluaran <span class="text-rose-600 dark:text-rose-400"></span>
            </h1>
            <p class="text-slate-500 dark:text-white font-medium text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">receipt_long</span>
                Pencatatan kas keluar harian operasional UMKM secara cepat, disiplin, dan real-time.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Guided Tour Button -->
            <button type="button" id="btn-start-tour"
                    class="bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-2.5 text-xs font-bold transition-all flex items-center justify-center gap-2 border border-emerald-200/50 shadow-sm w-full sm:w-auto">
                <span class="material-symbols-outlined text-[16px]">lightbulb</span>
                Panduan Pengeluaran
            </button>

            <!-- BACK TO DASHBOARD -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-zinc-900 text-slate-600 dark:text-white font-bold text-xs rounded-xl shadow-sm hover:shadow-md hover:text-primary transition-all border border-slate-100 dark:border-zinc-800/60 w-fit">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div id="tour-expense-stats" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Hari Ini -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">calendar_today</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Pengeluaran Hari Ini</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">Rp {{ number_format($todayExpensesSum, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-slate-400 dark:text-zinc-400 font-medium mt-4">Kas keluar operasional yang dicatat hari ini</p>
        </div>

        <!-- Card 2: Bulan Ini -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">summarize</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Total Bulan Ini</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">Rp {{ number_format($monthExpensesSum, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-slate-400 dark:text-zinc-400 font-medium mt-4">Akumulasi pengeluaran pada bulan ini</p>
        </div>

        <!-- Card 3: Total Logs -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">receipt</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Total Catatan Kas Keluar</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($totalExpensesCount, 0, ',', '.') }} <span class="text-sm text-slate-400 dark:text-zinc-400">Entri</span></h3>
            <p class="text-[10px] text-slate-400 dark:text-zinc-400 font-medium mt-4">Jumlah nota petty cash yang telah dibukukan</p>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN COCKPIT LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Side: Form to Record Expense (5 columns) -->
        <div id="tour-expense-form" class="lg:col-span-5 bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm space-y-6">
            <div class="space-y-1">
                <h4 class="text-lg font-black text-slate-900 dark:text-white font-manrope">Catat Kas Keluar Baru</h4>
                <p class="text-xs text-slate-400 dark:text-zinc-400">Masukkan detail pengeluaran operasional di bawah ini secara lengkap.</p>
            </div>

            <!-- Flash Alert -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded-2xl flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('expenses.store') }}" method="POST" class="space-y-4" x-data="{
                rawAmount: '{{ old('amount') ?: '' }}',
                displayAmount: '',
                init() {
                    if (this.rawAmount) {
                        this.displayAmount = new Intl.NumberFormat('id-ID').format(this.rawAmount);
                    }
                },
                updateAmount(val) {
                    let raw = val.replace(/\D/g, '');
                    this.rawAmount = raw ? parseInt(raw) : '';
                    this.displayAmount = this.rawAmount ? new Intl.NumberFormat('id-ID').format(this.rawAmount) : '';
                }
            }">
                @csrf

                <!-- Tanggal -->
                <div class="space-y-2">
                    <label for="expense_date" class="block text-[10px] font-black uppercase tracking-wider text-stone-450 dark:text-zinc-400">Tanggal Pengeluaran</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-3.5 text-stone-400 dark:text-white text-sm">calendar_today</span>
                        <input type="date"
                               id="expense_date"
                               name="expense_date"
                               value="{{ old('expense_date', date('Y-m-d')) }}"
                               required
                               class="w-full bg-slate-50/70 dark:bg-zinc-850/70 dark:bg-zinc-800/70 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-semibold text-slate-800 dark:text-white focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none" />
                    </div>
                    @error('expense_date')
                        <p class="text-rose-600 dark:text-rose-400 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori -->
                <div class="space-y-2">
                    <label for="category" class="block text-[10px] font-black uppercase tracking-wider text-stone-450 dark:text-zinc-400">Kategori Pengeluaran</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-3.5 text-stone-400 dark:text-white text-sm">category</span>
                        <select id="category"
                                name="category"
                                required
                                class="w-full bg-slate-50/70 dark:bg-zinc-850/70 dark:bg-zinc-800/70 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl pl-12 pr-10 py-3.5 text-sm font-semibold text-slate-800 dark:text-white focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none appearance-none cursor-pointer">
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <option value="Listrik/Air" {{ old('category') === 'Listrik/Air' ? 'selected' : '' }}>Listrik/Air</option>
                            <option value="Transportasi" {{ old('category') === 'Transportasi' ? 'selected' : '' }}>Transportasi (BBM, Parkir, Tol)</option>
                            <option value="Perlengkapan" {{ old('category') === 'Perlengkapan' ? 'selected' : '' }}>Perlengkapan Toko</option>
                            <option value="Gaji/Honor" {{ old('category') === 'Gaji/Honor' ? 'selected' : '' }}>Gaji / Honor Harian</option>
                            <option value="Lain-lain" {{ old('category') === 'Lain-lain' ? 'selected' : '' }}>Lain-lain</option>
                        </select>
                    </div>
                    @error('category')
                        <p class="text-rose-600 dark:text-rose-400 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nominal -->
                <div class="space-y-2">
                    <label for="amount_display" class="block text-[10px] font-black uppercase tracking-wider text-stone-450 dark:text-zinc-400">Nominal Pengeluaran (Rupiah)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-stone-400 dark:text-white text-sm font-black">Rp</span>
                        <input type="text"
                               id="amount_display"
                               x-model="displayAmount"
                               @input="updateAmount($event.target.value)"
                               placeholder="Contoh: 15.000"
                               required
                               class="w-full bg-slate-50/70 dark:bg-zinc-850/70 dark:bg-zinc-800/70 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-semibold text-slate-800 dark:text-white focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none font-mono" />
                        <input type="hidden" name="amount" :value="rawAmount" />
                    </div>
                    @error('amount')
                        <p class="text-rose-600 dark:text-rose-400 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div class="space-y-2">
                    <label for="description" class="block text-[10px] font-black uppercase tracking-wider text-stone-450 dark:text-zinc-400">Keterangan / Deskripsi (Opsional)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-3.5 text-stone-400 dark:text-white text-sm">description</span>
                        <textarea id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Tulis detail keperluan pengeluaran..."
                                  class="w-full bg-slate-50/70 dark:bg-zinc-850/70 dark:bg-zinc-800/70 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-semibold text-slate-800 dark:text-white focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none">{{ old('description') }}</textarea>
                    </div>
                    @error('description')
                        <p class="text-rose-600 dark:text-rose-400 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full py-4 text-white hover:bg-[#09523b] font-bold text-sm rounded-2xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15 bg-[#0b6e4f] dark:bg-emerald-600">
                    <span class="material-symbols-outlined text-lg">save</span>
                    <span>Simpan Pengeluaran Baru</span>
                </button>
            </form>
        </div>

        <!-- Right Side: Recent Expenditures Table (7 columns) -->
        <div id="tour-expense-table" class="lg:col-span-7 bg-white dark:bg-zinc-900 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm overflow-hidden flex flex-col">

            <!-- Table Header -->
            <div class="px-8 py-6 border-b border-stone-200/60 dark:border-zinc-800/80">
                <h4 class="text-lg font-black text-slate-900 dark:text-white font-manrope">Log Kas Keluar Terbaru</h4>
                <p class="text-xs text-slate-400 dark:text-zinc-400">Daftar transaksi pengeluaran operasional terdaftar.</p>
            </div>

            <!-- Filter, Sort & Export Toolbar -->
            <div class="px-8 py-4 bg-stone-50/40 dark:bg-zinc-850/40 dark:bg-zinc-800/40 border-b border-stone-200/60 dark:border-zinc-800/80 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <form action="{{ route('expenses.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <!-- Date Range -->
                    <div class="flex items-center gap-2">
                        <input type="date"
                               name="start_date"
                               value="{{ request('start_date') }}"
                               class="bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-50 dark:text-zinc-200 outline-none focus:border-[#0b6e4f] dark:focus:border-emerald-500 transition-all" />
                        <span class="text-xs text-slate-400 dark:text-zinc-400 font-bold">s/d</span>
                        <input type="date"
                               name="end_date"
                               value="{{ request('end_date') }}"
                               class="bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-50 dark:text-zinc-200 outline-none focus:border-[#0b6e4f] dark:focus:border-emerald-500 transition-all" />
                    </div>

                    <!-- Category Filter -->
                    <select name="category"
                            class="bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-50 dark:text-zinc-200 outline-none focus:border-[#0b6e4f] dark:focus:border-emerald-500 transition-all">
                        <option value="all" {{ request('category') === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="Listrik/Air" {{ request('category') === 'Listrik/Air' ? 'selected' : '' }}>Listrik/Air</option>
                        <option value="Transportasi" {{ request('category') === 'Transportasi' ? 'selected' : '' }}>Transportasi</option>
                        <option value="Perlengkapan" {{ request('category') === 'Perlengkapan' ? 'selected' : '' }}>Perlengkapan</option>
                        <option value="Gaji/Honor" {{ request('category') === 'Gaji/Honor' ? 'selected' : '' }}>Gaji/Honor</option>
                        <option value="Lain-lain" {{ request('category') === 'Lain-lain' ? 'selected' : '' }}>Lain-lain</option>
                    </select>

                    <!-- Sort Filter -->
                    <select name="sort_by"
                            class="bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-50 dark:text-zinc-200 outline-none focus:border-[#0b6e4f] dark:focus:border-emerald-500 transition-all">
                        <option value="newest" {{ request('sort_by') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="highest" {{ request('sort_by') === 'highest' ? 'selected' : '' }}>Nominal Terbesar</option>
                    </select>

                    <button type="submit"
                            class="px-4 py-2 bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                        Cari
                    </button>
                    @if(request('start_date') || request('end_date') || (request('category') && request('category') !== 'all') || request('sort_by') !== 'newest')
                        <a href="{{ route('expenses.index') }}"
                           class="px-4 py-2 bg-stone-100 dark:bg-zinc-800 hover:bg-stone-200 dark:hover:bg-zinc-800 text-slate-600 dark:text-white rounded-xl text-xs font-bold transition-all">
                            Reset
                        </a>
                    @endif
                </form>

                <!-- Excel Export -->
                <a href="{{ route('expenses.export', request()->all()) }}"
                   class="px-4 py-2 bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    <span>Ekspor Excel</span>
                </a>
            </div>

            <!-- Table Body -->
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-stone-200/60 dark:border-zinc-800/80 bg-stone-50/40 dark:bg-zinc-850/40 dark:bg-zinc-800/40">
                            <th class="px-8 py-4 text-xs font-black text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Waktu / Tanggal</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Kategori</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Deskripsi</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Nominal</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 dark:text-white uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-150">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-stone-50/40 dark:hover:bg-zinc-850/40 dark:hover:bg-zinc-800/40 transition-colors group">
                                <!-- Column 1: Tanggal -->
                                <td class="px-8 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-800 dark:text-white">
                                            {{ $expense->expense_date->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 dark:text-zinc-400 font-medium flex items-center gap-1 mt-0.5">
                                            <span class="material-symbols-outlined text-xs">schedule</span>
                                            {{ $expense->created_at->format('H:i') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Column 2: Kategori -->
                                <td class="px-6 py-5">
                                    @php
                                        $badgeColor = match($expense->category) {
                                            'Listrik/Air' => 'bg-amber-50 text-amber-600 border-amber-100/50',
                                            'Transportasi' => 'bg-blue-50 text-blue-600 border-blue-100/50',
                                            'Perlengkapan' => 'bg-emerald-50 text-emerald-600 border-emerald-100/50',
                                            'Gaji/Honor' => 'bg-purple-50 text-purple-600 border-purple-100/50',
                                            default => 'bg-slate-50 text-slate-600 border-slate-100/50',
                                        };
                                        $icon = match($expense->category) {
                                            'Listrik/Air' => 'electrical_services',
                                            'Transportasi' => 'local_shipping',
                                            'Perlengkapan' => 'storefront',
                                            'Gaji/Honor' => 'badge',
                                            default => 'payments',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black border uppercase tracking-wider {{ $badgeColor }}">
                                        <span class="material-symbols-outlined text-[12px]">{{ $icon }}</span>
                                        {{ $expense->category }}
                                    </span>
                                </td>

                                <!-- Column 3: Deskripsi -->
                                <td class="px-6 py-5 text-xs text-slate-500 dark:text-zinc-400 font-semibold max-w-[150px] truncate">
                                    {{ $expense->description ?: '-' }}
                                </td>

                                <!-- Column 4: Nominal -->
                                <td class="px-6 py-5">
                                    <span class="text-sm font-black text-rose-600 dark:text-rose-400 font-mono">
                                        - Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- Column 5: Aksi -->
                                <td class="px-6 py-5 text-center">
                                    <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan pengeluaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center mx-auto border border-rose-100">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                                        <span class="material-symbols-outlined text-4xl text-stone-400 dark:text-zinc-500 font-light">receipt_long</span>
                                        <p class="font-bold text-stone-700 dark:text-zinc-200">Belum ada pengeluaran</p>
                                        <p class="text-xs text-stone-400 dark:text-zinc-500">Gunakan form di samping untuk mencatat kas keluar harian operasional.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            @if($expenses->hasPages())
                <div class="px-8 py-6 border-t border-stone-200/60 dark:border-zinc-800/80 bg-stone-50/40 dark:bg-zinc-850/40 dark:bg-zinc-800/40 mt-auto">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap');
    body { font-family: 'Manrope', sans-serif; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnStartTour = document.getElementById('btn-start-tour');
        if (btnStartTour && window.driver) {
            const driver = window.driver.js.driver;
            const tour = driver({
                showProgress: true,
                animate: true,
                nextBtnText: 'Lanjut →',
                prevBtnText: '← Kembali',
                doneBtnText: 'Selesai ✓',
                popoverClass: 'driverjs-theme-emerald',
                steps: [
                    {
                        element: '#tour-expense-stats',
                        popover: {
                            title: 'Ringkasan Kas Keluar',
                            description: 'Pantau total uang kasir yang Anda pakai hari ini dan bulan ini untuk keperluan operasional toko.',
                            side: 'bottom',
                            align: 'center'
                        }
                    },
                    {
                        element: '#tour-expense-form',
                        popover: {
                            title: 'Catat Bon & Struk',
                            description: 'Beli galon, bayar listrik, atau parkir? Segera catat di form ini agar uang kasir di dompet dan sistem tetap seimbang.',
                            side: 'right',
                            align: 'start'
                        }
                    },
                    {
                        element: '#tour-expense-table',
                        popover: {
                            title: 'Riwayat Pengeluaran',
                            description: 'Lacak semua riwayat kas keluar harian secara transparan. Anda juga bisa mengunduh rekapitulasi ke Excel.',
                            side: 'top',
                            align: 'start'
                        }
                    }
                ]
            });

            btnStartTour.addEventListener('click', () => {
                tour.drive();
            });
        }
    });
</script>
@endsection

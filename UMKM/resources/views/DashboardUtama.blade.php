@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard Utama')

@section('content')
<!-- Dashboard Container -->
<div class="px-2 py-4 sm:px-4 space-y-8 bg-transparent min-h-screen">

    <!-- HEADER & DATE FILTER -->
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1.5">
            <h1 class="text-3xl font-black text-stone-800 dark:text-white tracking-tight font-manrope">
                Selamat Datang, <span class="bg-gradient-to-r from-emerald-600 to-emerald-500 bg-clip-text text-transparent">{{ $companyName }}</span> 👋
            </h1>
            <p class="text-stone-500 dark:text-white font-bold text-xs flex items-center flex-wrap gap-2">
                <span class="material-symbols-outlined text-sm text-stone-400 dark:text-zinc-400">calendar_today</span>
                <span>{{ \Carbon\Carbon::parse($targetDate)->translatedFormat('l, d F Y') }}</span>
                @if($isTimeTravel)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 animate-pulse">
                        <span class="material-symbols-outlined text-[12px]">hourglass_empty</span>
                        <span>Mode Penelusuran Waktu</span>
                    </span>
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Date Filter & Range Selector Wrapper -->
            <div class="flex items-center bg-white dark:bg-zinc-900 p-1.5 rounded-2xl border border-stone-200/60 dark:border-zinc-800/80 shadow-md shadow-stone-200/10 w-fit">
                <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-3">
                    <div class="flex items-center gap-2 pl-2">
                        <span class="material-symbols-outlined text-stone-400 dark:text-white text-base">travel_explore</span>
                        <input type="date"
                               name="date"
                               value="{{ $targetDate }}"
                               onchange="this.form.submit()"
                               class="border-none bg-stone-100/60 dark:bg-zinc-800/60 hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 rounded-xl p-2 text-xs font-semibold text-stone-700 dark:text-zinc-50 dark:text-zinc-200 focus:ring-0 outline-none cursor-pointer transition-all" />
                    </div>

                    <div class="h-6 w-px bg-stone-200 dark:bg-zinc-800"></div>

                    <div class="flex items-center gap-1 pr-1">
                        <a href="{{ route('dashboard', ['range' => '1']) }}"
                           class="px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all duration-200 {{ $currentFilter == '1' && !$isTimeTravel ? 'bg-[#0b6e4f] dark:bg-emerald-600 text-white shadow-sm' : 'text-stone-500 dark:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800' }}">
                            Hari Ini
                        </a>
                        <a href="{{ route('dashboard', ['range' => '7']) }}"
                           class="px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all duration-200 {{ $currentFilter == '7' && !$isTimeTravel ? 'bg-[#0b6e4f] dark:bg-emerald-600 text-white shadow-sm' : 'text-stone-500 dark:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800' }}">
                            7 Hari
                        </a>
                        <a href="{{ route('dashboard', ['range' => '30']) }}"
                           class="px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all duration-200 {{ $currentFilter == '30' && !$isTimeTravel ? 'bg-[#0b6e4f] dark:bg-emerald-600 text-white shadow-sm' : 'text-stone-500 dark:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800' }}">
                            30 Hari
                        </a>
                    </div>
                </form>
            </div>

            <!-- Export Excel Button (Secondary) -->
            <a href="{{ route('dashboard.export', array_merge(request()->all(), ['date' => $targetDate, 'range' => $currentFilter])) }}"
               class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 text-stone-700 dark:text-zinc-50 dark:text-white rounded-xl text-xs font-semibold shadow-sm transition-all duration-200">
                <span class="material-symbols-outlined text-[16px] text-stone-500 dark:text-zinc-400">download</span>
                <span>Ekspor</span>
            </a>

            <!-- Quick POS Action Button (Primary) -->
            <button type="button" id="btn-start-tour"
                    class="bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all flex items-center gap-2 border border-emerald-100/30 dark:border-emerald-900/30 shadow-sm hover:scale-[1.02] active:scale-[0.98]">
                <span class="material-symbols-outlined text-[16px] font-bold">help</span>
                <span>Panduan Fitur</span>
            </button>

            <a href="{{ route('sales.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 bg-[#0b6e4f] dark:bg-emerald-600 hover:opacity-95 text-white rounded-xl text-xs font-semibold shadow-sm transition-all duration-200">
                <span class="material-symbols-outlined text-[16px]">add</span>
                <span>POS Kasir</span>
            </a>
        </div>
    </div>

    <!-- METRICS CARDS (ROW 1) -->
    @if(auth()->check() && auth()->user()->isStaff())
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="tour-dashboard-stats">
            <!-- Pemasukan Card (Sales) -->
            <div class="relative overflow-hidden bg-[#0b6e4f] dark:bg-emerald-600 text-white p-6 rounded-[1.5rem] border border-[#0b6e4f] dark:border-emerald-500 shadow-md shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/5 rounded-full blur-xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="w-10 h-10 bg-white/10 text-emerald-300 rounded-xl flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-lg">payments</span>
                        </div>
                        <p class="text-[9px] font-bold text-stone-300/80 uppercase tracking-widest flex items-center gap-1.5">
                            <span>Arus Kas Masuk Tunai</span>
                            <span class="relative inline-block group/tooltip">
                                <button type="button" class="text-stone-300 hover:text-white transition-colors focus:outline-none flex items-center">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2.5 bg-stone-900 dark:bg-zinc-800 text-white text-[9px] rounded-xl shadow-xl opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-300 z-50 text-center font-medium font-sans normal-case tracking-normal leading-normal">
                                    Total uang tunai fisik yang masuk ke kasir (Hasil Jualan Tunai + Pembayaran Cicilan Utang).
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-stone-900"></span>
                                </span>
                            </span>
                        </p>
                        <h3 class="text-2xl font-black text-white tracking-tight mt-1">Rp {{ number_format($totalSales, 0, ',', '.') }}</h3>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/10 flex items-center justify-between">
                        <span class="text-[9px] text-stone-300/70 font-bold uppercase tracking-wider">Growth vs Lalu</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white/10 text-emerald-300">
                            <span class="material-symbols-outlined text-xs">{{ $salesGrowth >= 0 ? 'trending_up' : 'trending_down' }}</span>
                            {{ abs(round($salesGrowth, 1)) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pengeluaran Card (Expenses) -->
            <div class="relative overflow-hidden bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="w-10 h-10 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-lg">shopping_cart</span>
                        </div>
                        <p class="text-[9px] font-bold text-stone-450 dark:text-zinc-400 uppercase tracking-widest flex items-center gap-1.5">
                            <span>Arus Kas Keluar Tunai</span>
                            <span class="relative inline-block group/tooltip">
                                <button type="button" class="text-stone-400 dark:text-white hover:text-stone-600 dark:hover:text-zinc-400 transition-colors focus:outline-none flex items-center">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2.5 bg-stone-900 dark:bg-zinc-800 text-white text-[9px] rounded-xl shadow-xl opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-300 z-50 text-center font-medium font-sans normal-case tracking-normal leading-normal">
                                    Total uang tunai fisik yang keluar dari kasir (Biaya Operasional + Belanja Stok Bahan).
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-stone-900"></span>
                                </span>
                            </span>
                        </p>
                        <h3 class="text-2xl font-black text-stone-850 dark:text-white tracking-tight mt-1">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h3>
                    </div>
                    <div class="mt-6 pt-4 border-t border-stone-200/50 dark:border-zinc-850 flex items-center justify-between">
                        <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold uppercase tracking-wider">Growth vs Lalu</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $expenseGrowth <= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                            <span class="material-symbols-outlined text-xs">{{ $expenseGrowth <= 0 ? 'trending_down' : 'trending_up' }}</span>
                            {{ abs(round($expenseGrowth, 1)) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Profit Card (Earnings) -->
            @php
                $profit = $totalSales - $totalExpenses;
            @endphp
            <div class="relative overflow-hidden bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-lg">account_balance_wallet</span>
                        </div>
                        <p class="text-[9px] font-bold text-stone-455 dark:text-zinc-400 uppercase tracking-widest flex items-center gap-1.5">
                            <span>Sisa Arus Kas (Net)</span>
                            <span class="relative inline-block group/tooltip">
                                <button type="button" class="text-stone-400 dark:text-white hover:text-stone-600 dark:hover:text-zinc-400 transition-colors focus:outline-none flex items-center">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2.5 bg-stone-900 dark:bg-zinc-800 text-white text-[9px] rounded-xl shadow-xl opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-300 z-50 text-center font-medium font-sans normal-case tracking-normal leading-normal">
                                    Sisa saldo uang tunai fisik di laci kasir (Arus Kas Masuk Tunai - Arus Kas Keluar Tunai).
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-stone-900"></span>
                                </span>
                            </span>
                        </p>
                        <h3 class="text-2xl font-black {{ $profit >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} tracking-tight mt-1">Rp {{ number_format($profit, 0, ',', '.') }}</h3>
                    </div>
                    <div class="mt-6 pt-4 border-t border-stone-200/50 dark:border-zinc-850 flex items-center justify-between">
                        <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold uppercase tracking-wider">Status Neraca</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $profit >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                            {{ $profit >= 0 ? 'Surplus' : 'Defisit' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" id="tour-dashboard-stats">
            <!-- Sales Card -->
            <div class="relative overflow-hidden bg-[#0b6e4f] dark:bg-emerald-600 text-white p-6 rounded-[1.5rem] border border-[#0b6e4f] dark:border-emerald-500 shadow-md shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/5 rounded-full blur-xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="w-10 h-10 bg-white/10 text-emerald-300 rounded-xl flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-lg">payments</span>
                        </div>
                        <p class="text-[9px] font-bold text-stone-300/85 uppercase tracking-widest flex items-center gap-1.5">
                            <span>Arus Kas Masuk Tunai</span>
                            <span class="relative inline-block group/tooltip">
                                <button type="button" class="text-stone-300 hover:text-white transition-colors focus:outline-none flex items-center">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2.5 bg-stone-900 dark:bg-zinc-800 text-white text-[9px] rounded-xl shadow-xl opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-300 z-50 text-center font-medium font-sans normal-case tracking-normal leading-normal">
                                    Total uang tunai fisik yang masuk ke kasir (Hasil Jualan Tunai + Pembayaran Cicilan Utang).
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-stone-900"></span>
                                </span>
                            </span>
                        </p>
                        <h3 class="text-2xl font-black text-white tracking-tight mt-1">Rp {{ number_format($totalSales, 0, ',', '.') }}</h3>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/10 flex items-center justify-between">
                        <span class="text-[9px] text-stone-300/70 font-bold uppercase tracking-wider">Growth vs Lalu</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white/10 text-emerald-300">
                            <span class="material-symbols-outlined text-xs">{{ $salesGrowth >= 0 ? 'trending_up' : 'trending_down' }}</span>
                            {{ abs(round($salesGrowth, 1)) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Expense Card -->
            <div class="relative overflow-hidden bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="w-10 h-10 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-lg">shopping_cart</span>
                        </div>
                        <p class="text-[9px] font-bold text-stone-450 dark:text-zinc-400 uppercase tracking-widest flex items-center gap-1.5">
                            <span>Arus Kas Keluar Tunai</span>
                            <span class="relative inline-block group/tooltip">
                                <button type="button" class="text-stone-400 dark:text-white hover:text-stone-600 dark:hover:text-zinc-400 transition-colors focus:outline-none flex items-center">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2.5 bg-stone-900 dark:bg-zinc-800 text-white text-[9px] rounded-xl shadow-xl opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-300 z-50 text-center font-medium font-sans normal-case tracking-normal leading-normal">
                                    Total uang tunai fisik yang keluar dari kasir (Biaya Operasional + Belanja Stok Bahan).
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-stone-900"></span>
                                </span>
                            </span>
                        </p>
                        <h3 class="text-2xl font-black text-stone-850 dark:text-white tracking-tight mt-1">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h3>
                    </div>
                    <div class="mt-6 pt-4 border-t border-stone-200/50 dark:border-zinc-850 flex items-center justify-between">
                        <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold uppercase tracking-wider">Growth vs Lalu</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $expenseGrowth <= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                            <span class="material-symbols-outlined text-xs">{{ $expenseGrowth <= 0 ? 'trending_down' : 'trending_up' }}</span>
                            {{ abs(round($expenseGrowth, 1)) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Production Card -->
            <div class="relative overflow-hidden bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-lg">precision_manufacturing</span>
                        </div>
                        <p class="text-[9px] font-bold text-stone-450 dark:text-zinc-400 uppercase tracking-widest">Produksi Selesai</p>
                        <h3 class="text-2xl font-black text-stone-850 dark:text-white tracking-tight mt-1">{{ number_format($totalProduction, 0, ',', '.') }} <span class="text-xs text-stone-455 dark:text-zinc-400 font-semibold">Unit</span></h3>
                    </div>
                    <div class="mt-6 pt-4 border-t border-stone-200/50 dark:border-zinc-850 flex items-center justify-between">
                        <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold uppercase tracking-wider">Periode Ini</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-750">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Stock Health Card -->
            <div class="relative overflow-hidden bg-white dark:bg-zinc-900 p-6 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm {{ $lowStock > 0 ? 'ring-1 ring-amber-500/30' : '' }}">
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="w-10 h-10 {{ $lowStock > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} rounded-xl flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-lg">{{ $lowStock > 0 ? 'inventory_2' : 'check_circle' }}</span>
                        </div>
                        <p class="text-[9px] font-bold text-stone-455 dark:text-zinc-400 uppercase tracking-widest">Kesehatan Stok</p>
                        <h3 class="text-2xl font-black text-stone-850 dark:text-white tracking-tight mt-1">{{ $stockSafePercent }}% <span class="text-xs text-stone-455 dark:text-zinc-400 font-semibold">Aman</span></h3>
                    </div>
                    <div class="mt-6 pt-4 border-t border-stone-200/50 dark:border-zinc-850 flex items-center justify-between">
                        <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold uppercase tracking-wider">Status Bahan</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-bold {{ $lowStock > 0 ? 'bg-amber-50 text-amber-750' : 'bg-emerald-50 text-emerald-750' }}">
                            {{ $lowStock > 0 ? $lowStock . 'Kritis' : 'Aman' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 2-COLUMN GRID (Baront Layout Style) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column (2/3 width) -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Financial Trend Chart (Line/Bar Chart) -->
            <div id="tour-dashboard-chart" class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <h4 class="text-sm font-bold text-stone-800 dark:text-white font-manrope">Tren Arus Kas</h4>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-stone-500 dark:text-zinc-400">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#0b6e4f] dark:bg-emerald-600 shadow-sm shadow-[#0b6e4f]/25 dark:shadow-emerald-950/15"></span>
                            Arus Masuk
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-stone-500 dark:text-zinc-400">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-sm shadow-rose-500/25"></span>
                            Arus Keluar
                        </span>
                    </div>
                </div>
                <div class="h-[360px] w-full">
                    <canvas id="financialTrendChart"></canvas>
                </div>
            </div>

            <!-- Recent Activities (Table format separated by lines) -->
            <div class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-sm font-bold text-stone-800 dark:text-white font-manrope">Aktivitas Terbaru</h4>
                        <p class="text-xs text-stone-400 dark:text-zinc-400 font-medium mt-0.5">Catatan transaksi dan kegiatan produksi terakhir</p>
                    </div>
                    <span class="text-stone-450 dark:text-zinc-400"><span class="material-symbols-outlined text-xl">history</span></span>
                </div>

                <div class="divide-y divide-stone-100 dark:divide-zinc-800/60">
                    @forelse($recentActivities as $activity)
                        @php
                            $bgCol = 'bg-stone-50 text-stone-600';
                            if ($activity['color'] === 'emerald') $bgCol = 'bg-emerald-50 text-[#0b6e4f]';
                            if ($activity['color'] === 'rose') $bgCol = 'bg-rose-50 text-rose-600';
                            if ($activity['color'] === 'indigo') $bgCol = 'bg-indigo-50 text-indigo-600';
                            if ($activity['color'] === 'fuchsia') $bgCol = 'bg-fuchsia-50 text-fuchsia-600';
                            if ($activity['color'] === 'sky') $bgCol = 'bg-sky-50 text-sky-600';
                            if ($activity['color'] === 'amber') $bgCol = 'bg-amber-50 text-amber-600';
                        @endphp
                        <div class="flex items-center justify-between py-3.5 hover:bg-stone-50/50 dark:hover:bg-zinc-850/30 dark:hover:bg-zinc-800/30 dark:hover:bg-transparent px-1 transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <!-- Circular initial/icon badge -->
                                <div class="w-9 h-9 {{ $bgCol }} rounded-full flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0">
                                    <span class="material-symbols-outlined text-base">{{ $activity['icon'] }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-stone-850 dark:text-white">{{ $activity['title'] }}</p>
                                    <p class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold mt-0.5">{{ $activity['time']->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-stone-850 dark:text-white bg-stone-100/60 dark:bg-zinc-800/60 px-3 py-1 rounded-lg border border-stone-200/30 dark:border-zinc-800/30">{{ $activity['amount'] }}</span>
                        </div>
                    @empty
                        <div class="py-12 text-center flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                            <span class="material-symbols-outlined text-4xl text-stone-400 dark:text-zinc-500 font-light">hourglass_empty</span>
                            <p class="font-bold text-stone-700 dark:text-zinc-200">Belum ada aktivitas</p>
                            <p class="text-xs text-stone-400 dark:text-zinc-500">Aktivitas baru akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Produk Terlaris Card -->
            <div class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-sm font-bold text-stone-800 dark:text-white font-manrope">Produk Terlaris</h4>
                        <p class="text-xs text-stone-400 dark:text-zinc-400 font-medium mt-0.5">Produk dengan performa penjualan tertinggi</p>
                    </div>
                    <span class="text-stone-450 dark:text-zinc-400"><span class="material-symbols-outlined text-xl">workspace_premium</span></span>
                </div>

                <div class="space-y-5">
                    @php
                        $maxQty = $topProducts->first()->total_qty ?? 1;
                    @endphp
                    @forelse($topProducts as $index => $prod)
                        @php
                            $percentage = $maxQty > 0 ? ($prod->total_qty / $maxQty) * 100 : 0;
                            // Colors for the index badge
                            $badgeColor = 'bg-stone-100 text-stone-600';
                            if ($index === 0) $badgeColor = 'bg-amber-50 text-amber-600 border border-amber-200/50';
                            if ($index === 1) $badgeColor = 'bg-slate-100 text-slate-600 border border-slate-200/50';
                            if ($index === 2) $badgeColor = 'bg-orange-50 text-orange-600 border border-orange-200/50';
                        @endphp
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <!-- Index Badge -->
                                    <span class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold {{ $badgeColor }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-xs font-bold text-stone-800 dark:text-white">{{ $prod->name }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-stone-850 dark:text-white">{{ $prod->total_qty }} unit</span>
                                    <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold block">Rp {{ number_format($prod->total_revenue, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div class="h-1.5 w-full bg-stone-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full bg-[#0b6e4f]/80 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                            <span class="material-symbols-outlined text-4xl text-stone-400 dark:text-zinc-500 font-light">inventory</span>
                            <p class="font-bold text-stone-700 dark:text-zinc-200">Belum ada penjualan</p>
                            <p class="text-xs text-stone-400 dark:text-zinc-500">Data penjualan produk akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column (1/3 width) -->
        <div class="space-y-8">

            <!-- Stacked Quick Actions -->
            <div id="tour-dashboard-actions" class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="mb-5">
                    <h4 class="text-sm font-bold text-stone-800 dark:text-white font-manrope">Pintasan Aksi</h4>
                    <p class="text-xs text-stone-400 dark:text-zinc-400 font-medium mt-0.5">Akses cepat menu operasional utama</p>
                </div>
                <div class="space-y-3">
                    <!-- Kasir POS -->
                    <a href="{{ route('sales.index') }}" class="group flex items-center justify-between p-4 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 hover:border-emerald-500/40 hover:bg-stone-50/40 dark:hover:bg-zinc-850/40 dark:hover:bg-zinc-800/40 rounded-2xl shadow-sm transition-all duration-200">
                        <div class="flex items-center gap-3.5">
                            <div class="w-9 h-9 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center border border-emerald-100 shadow-sm">
                                <span class="material-symbols-outlined text-base font-bold">add_shopping_cart</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-800 dark:text-white group-hover:text-[#0b6e4f] dark:group-hover:text-white transition-colors">Catat Penjualan</p>
                                <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-wider mt-0.5">Kasir POS</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-stone-400 dark:text-white group-hover:text-[#0b6e4f] dark:group-hover:text-emerald-400 group-hover:translate-x-1 transition-all text-sm">chevron_right</span>
                    </a>

                    <!-- Input Stok -->
                    <a href="{{ route('materials.index') }}" class="group flex items-center justify-between p-4 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 hover:border-sky-500/40 hover:bg-stone-50/40 dark:hover:bg-zinc-850/40 dark:hover:bg-zinc-800/40 rounded-2xl shadow-sm transition-all duration-200">
                        <div class="flex items-center gap-3.5">
                            <div class="w-9 h-9 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center border border-sky-100 shadow-sm">
                                <span class="material-symbols-outlined text-base font-bold">inventory_2</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-800 dark:text-white group-hover:text-sky-600 transition-colors">Input Stok Bahan</p>
                                <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-wider mt-0.5">Stok Bahan</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-stone-400 dark:text-white group-hover:text-sky-600 group-hover:translate-x-1 transition-all text-sm">chevron_right</span>
                    </a>

                    <!-- Mulai Produksi -->
                    <a href="{{ route('productions.index') }}" class="group flex items-center justify-between p-4 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 hover:border-indigo-500/40 hover:bg-stone-50/40 dark:hover:bg-zinc-850/40 dark:hover:bg-zinc-800/40 rounded-2xl shadow-sm transition-all duration-200">
                        <div class="flex items-center gap-3.5">
                            <div class="w-9 h-9 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center border border-indigo-100 shadow-sm">
                                <span class="material-symbols-outlined text-base font-bold">precision_manufacturing</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-white transition-colors">Mulai Produksi</p>
                                <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-wider mt-0.5">Manufaktur</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-stone-400 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:translate-x-1 transition-all text-sm">chevron_right</span>
                    </a>

                    <!-- Tanya AI -->
                    <a href="{{ route('ai.index') }}" class="group flex items-center justify-between p-4 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 hover:border-fuchsia-500/40 hover:bg-stone-50/40 dark:hover:bg-zinc-850/40 dark:hover:bg-zinc-800/40 rounded-2xl shadow-sm transition-all duration-200">
                        <div class="flex items-center gap-3.5">
                            <div class="w-9 h-9 bg-fuchsia-50 text-fuchsia-600 rounded-xl flex items-center justify-center border border-fuchsia-100 shadow-sm">
                                <span class="material-symbols-outlined text-base font-bold">psychology</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-800 dark:text-white group-hover:text-fuchsia-600 transition-colors">Tanya AI Assistant</p>
                                <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-wider mt-0.5">Kecerdasan Buatan</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-stone-400 dark:text-white group-hover:text-fuchsia-600 group-hover:translate-x-1 transition-all text-sm">chevron_right</span>
                    </a>
                </div>
            </div>

            <!-- AI Insight Banner -->
            <div class="relative overflow-hidden bg-emerald-50/40 dark:bg-emerald-950/40 p-5 rounded-[1.5rem] border border-emerald-100/80 shadow-sm flex items-start gap-4">
                <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-emerald-400/10 rounded-full blur-xl pointer-events-none"></div>
                <div class="flex-shrink-0 w-9 h-9 bg-[#0b6e4f] dark:bg-emerald-600 text-white rounded-xl flex items-center justify-center shadow-sm relative mt-0.5">
                    <span class="material-symbols-outlined text-base">psychology</span>
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-emerald-400 rounded-full border border-emerald-50 animate-ping"></span>
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-emerald-500 rounded-full border border-emerald-50"></span>
                </div>
                <div class="flex-1 space-y-1.5">
                    <span class="text-[9px] font-black uppercase tracking-wider text-[#0b6e4f] dark:text-emerald-400 block">Asisten AI SAHAYU</span>
                    <p class="text-stone-750 dark:text-zinc-50 dark:text-white font-medium text-xs leading-relaxed italic">
                        "{{ $aiInsight }}"
                    </p>
                    <div class="pt-2">
                        <a href="{{ route('ai.index') }}" class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-[#0b6e4f] dark:text-emerald-400 hover:underline">
                            <span>Lihat Analisis Detail</span>
                            <span class="material-symbols-outlined text-[10px]">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cost Structure Chart (Doughnut) -->
            <div class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm flex flex-col justify-between">
                <div>
                    <h4 class="text-sm font-bold text-stone-800 dark:text-white font-manrope text-center mb-6">Struktur Biaya</h4>
                    <div class="relative flex items-center justify-center">
                        <div class="h-[200px] w-full">
                            <canvas id="costStructureChart"></canvas>
                        </div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                            <span class="text-xl font-bold text-stone-800 dark:text-white tracking-tight">100%</span>
                            <span class="text-[8px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest mt-0.5">Total HPP</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-3.5 mt-6 pt-4 border-t border-stone-200/50 dark:border-zinc-850">
                    <!-- Legend Item: Material -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#0b6e4f] dark:bg-emerald-600 shadow-sm"></span>
                            <span class="font-bold text-stone-500 dark:text-zinc-400">Bahan Baku</span>
                        </div>
                        <span class="font-bold text-stone-850 dark:text-white">Rp {{ number_format($costDist['material'], 0, ',', '.') }}</span>
                    </div>
                    <!-- Legend Item: Labor -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600/90 shadow-sm"></span>
                            <span class="font-bold text-stone-500 dark:text-zinc-400">Tenaga Kerja</span>
                        </div>
                        <span class="font-bold text-stone-850 dark:text-white">Rp {{ number_format($costDist['labor'], 0, ',', '.') }}</span>
                    </div>
                    <!-- Legend Item: Overhead -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#0284c7]/95 shadow-sm"></span>
                            <span class="font-bold text-stone-500 dark:text-zinc-400">Overhead</span>
                        </div>
                        <span class="font-bold text-stone-850 dark:text-white">Rp {{ number_format($costDist['overhead'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Stock Alerts -->
            <div class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] border border-stone-200/60 dark:border-zinc-800/80 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-sm font-bold text-stone-800 dark:text-white font-manrope">Peringatan Stok</h4>
                        <p class="text-xs text-stone-400 dark:text-zinc-400 font-medium mt-0.5">Bahan baku di bawah batas minimum</p>
                    </div>
                    <span class="inline-flex p-2 rounded-xl {{ $lowStock > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                        <span class="material-symbols-outlined text-xl">
                            {{ $lowStock > 0 ? 'warning' : 'verified' }}
                        </span>
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($lowStockMaterials as $mat)
                        <div class="flex items-center justify-between p-3.5 bg-amber-50/45 dark:bg-amber-950/45 hover:bg-amber-50/60 dark:hover:bg-amber-950/60 border border-amber-500/25 rounded-2xl transition-all duration-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8.5 h-8.5 bg-white dark:bg-zinc-900 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center shadow-sm">
                                    <span class="material-symbols-outlined text-base">inventory_2</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-stone-850 dark:text-white truncate max-w-[100px]">{{ $mat->name }}</p>
                                    <p class="text-[9px] text-stone-400 dark:text-zinc-400 font-bold mt-0.5">Stok: <span class="text-amber-600 dark:text-amber-400">{{ $mat->stock }} {{ $mat->unit }}</span></p>
                                </div>
                            </div>
                            <a href="{{ route('materials.index') }}" class="text-[9px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-white dark:bg-zinc-900 hover:bg-amber-600 hover:text-white px-2.5 py-1.5 rounded-lg border border-amber-500/10 shadow-sm transition-all duration-200">
                                Restok
                            </a>
                        </div>
                    @empty
                        <div class="py-8 text-center flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                            <span class="material-symbols-outlined text-4xl text-emerald-550 text-emerald-500 dark:text-emerald-400 font-light">done_all</span>
                            <p class="font-bold text-stone-700 dark:text-zinc-200">Semua Stok Aman</p>
                            <p class="text-xs text-stone-400 dark:text-zinc-500">Bahan baku di atas batas minimum.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

<!-- SCRIPTS: Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Financial Trend Chart
        const ctxFinancial = document.getElementById('financialTrendChart').getContext('2d');

        // Custom Area Gradients
        const gradSales = ctxFinancial.createLinearGradient(0, 0, 0, 360);
        gradSales.addColorStop(0, 'rgba(11, 110, 79, 0.22)');
        gradSales.addColorStop(0.5, 'rgba(11, 110, 79, 0.08)');
        gradSales.addColorStop(1, 'rgba(11, 110, 79, 0)');

        const gradExp = ctxFinancial.createLinearGradient(0, 0, 0, 360);
        gradExp.addColorStop(0, 'rgba(244, 63, 94, 0.22)');
        gradExp.addColorStop(0.5, 'rgba(244, 63, 94, 0.08)');
        gradExp.addColorStop(1, 'rgba(244, 63, 94, 0)');

        new Chart(ctxFinancial, {
            type: '{{ $chartType }}',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Arus Kas Masuk',
                        data: @json($chartSales),
                        borderColor: '#0b6e4f',
                        backgroundColor: {!! $chartType == 'bar' ? '"#0b6e4f"' : 'gradSales' !!},
                        borderWidth: {{ $chartType == "bar" ? 0 : 3.5 }},
                        fill: true,
                        tension: 0.42,
                        pointRadius: {{ $chartType == "bar" ? 0 : 3 }},
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#0b6e4f',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        borderRadius: 6,
                        maxBarThickness: 32,
                    },
                    {
                        label: 'Arus Kas Keluar',
                        data: @json($chartExpenses),
                        borderColor: '#f43f5e',
                        backgroundColor: {!! $chartType == 'bar' ? '"#f43f5e"' : 'gradExp' !!},
                        borderWidth: {{ $chartType == "bar" ? 0 : 3.5 }},
                        fill: true,
                        tension: 0.42,
                        pointRadius: {{ $chartType == "bar" ? 0 : 3 }},
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#f43f5e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        borderRadius: 6,
                        maxBarThickness: 32,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1c1917',
                        padding: 14,
                        cornerRadius: 14,
                        titleFont: { size: 10, weight: '800', family: 'Manrope' },
                        bodyFont: { size: 13, weight: 'bold', family: 'Inter' },
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        callbacks: {
                            label: (context) => ' ' + context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { weight: 'bold', size: 9, family: 'Inter' },
                            color: '#a8a29e',
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: {{ $currentFilter == '1' ? 8 : 12 }}
                        }
                    },
                    y: {
                        grid: { color: 'rgba(120, 113, 108, 0.08)' },
                        ticks: {
                            font: { weight: 'bold', size: 9, family: 'Inter' },
                            color: '#a8a29e',
                            callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value)
                        },
                        beginAtZero: true
                    }
                }
            }
        });

        // Cost Structure Chart (Doughnut)
        const ctxCost = document.getElementById('costStructureChart').getContext('2d');
        new Chart(ctxCost, {
            type: 'doughnut',
            data: {
                labels: ['Bahan Baku', 'Tenaga Kerja', 'Overhead'],
                datasets: [{
                    data: [{{ $costDist['material'] }}, {{ $costDist['labor'] }}, {{ $costDist['overhead'] }}],
                    backgroundColor: ['#0b6e4f', '#d97706', '#0284c7'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '82%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1c1917',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: { size: 10, weight: '800', family: 'Manrope' },
                        bodyFont: { size: 13, weight: 'bold', family: 'Inter' },
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        callbacks: {
                            label: (context) => ' ' + context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed)
                        }
                    }
                }
            }
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const btnStartTour = document.getElementById('btn-start-tour');
        if (!btnStartTour) return;

        btnStartTour.addEventListener('click', () => {
            const driver = window.driver.js.driver;
            
            const driverObj = driver({
                showProgress: true,
                steps: [
                    {
                        element: '#tour-dashboard-stats',
                        popover: {
                            title: 'Ringkasan Finansial',
                            description: 'Kartu ini menampilkan pergerakan arus kas tunai Anda secara real-time, membandingkan performa penjualan dan pengeluaran dengan periode sebelumnya.',
                            side: 'bottom',
                            align: 'start'
                        }
                    },
                    {
                        element: '#tour-dashboard-chart',
                        popover: {
                            title: 'Tren Arus Kas',
                            description: 'Grafik interaktif ini membantu Anda memvisualisasikan tren pemasukan versus pengeluaran dari hari ke hari.',
                            side: 'top',
                            align: 'center'
                        }
                    },
                    {
                        element: '#tour-dashboard-actions',
                        popover: {
                            title: 'Pintasan Operasional',
                            description: 'Akses menu kasir, stok bahan, produksi, atau bahkan asisten AI dengan satu sentuhan dari sini.',
                            side: 'left',
                            align: 'start'
                        }
                    }
                ]
            });

            driverObj.drive();
        });
    });
</script>
@endsection

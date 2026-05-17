@extends('layouts.app')

@section('content')
<div class="px-4 py-6 sm:px-8 space-y-6 bg-slate-50/50 min-h-screen">
    
    <!-- HEADER -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <h1 class="text-3xl font-black text-slate-900 tracking-tight font-manrope">
                Riwayat Transaksi
            </h1>
            <p class="text-slate-500 font-medium text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">history</span>
                Log operasional, penjualan, dan pembayaran terpusat untuk UMKM Anda.
            </p>
        </div>
        
        <!-- BACK TO DASHBOARD -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-5 py-2.5 bg-white text-slate-600 font-bold text-xs rounded-xl shadow-sm hover:shadow-md hover:text-primary transition-all border border-slate-100 w-fit">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali ke Dashboard
        </a>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Total Omzet -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Omzet Penjualan</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($totalSalesAmount, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-slate-400 font-medium mt-4">Akumulasi seluruh transaksi penjualan</p>
        </div>

        <!-- Card 2: Total Transaksi Penjualan -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-teal-50 text-teal-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">shopping_cart</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Transaksi Penjualan</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalSalesCount, 0, ',', '.') }} <span class="text-sm text-slate-400">Transaksi</span></h3>
            <p class="text-[10px] text-slate-400 font-medium mt-4">Jumlah nota penjualan yang dicatat</p>
        </div>

        <!-- Card 3: Total Operasi Produksi -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">precision_manufacturing</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Operasi Produksi</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalProductionCount, 0, ',', '.') }} <span class="text-sm text-slate-400">Batch</span></h3>
            <p class="text-[10px] text-slate-400 font-medium mt-4">Jumlah batch produksi barang jadi</p>
        </div>
    </div>

    <!-- ADVANCED FILTERING BOARD -->
    <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-4" x-data="{ showAdvanced: {{ ($startDate || $endDate || $sortBy !== 'transaction_date' || $currentType !== 'all') ? 'true' : 'false' }} }">
        <form method="GET" action="{{ route('history.index') }}" class="space-y-4">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Text Search Input -->
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-4 top-3.5 text-slate-400">search</span>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Cari Pelanggan, No. Transaksi, atau Keterangan..." 
                           class="w-full bg-slate-50/70 border-2 border-slate-100 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none" />
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Advanced Filter Button -->
                    <button type="button" 
                            @click="showAdvanced = !showAdvanced" 
                            class="px-5 py-3.5 bg-slate-50 border-2 border-slate-100 hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">tune</span>
                        <span>Filter Lanjutan</span>
                        <span class="material-symbols-outlined text-[12px] transition-transform" :class="showAdvanced ? 'rotate-180' : ''">keyboard_arrow_down</span>
                    </button>
                    
                    <!-- Reset Button -->
                    @if($search || $startDate || $endDate || $currentType !== 'all' || $sortBy !== 'transaction_date')
                        <a href="{{ route('history.index') }}" 
                           class="px-5 py-3.5 bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-xs rounded-xl transition-all flex items-center gap-2 border border-rose-200">
                            <span class="material-symbols-outlined text-[16px]">clear_all</span>
                            <span>Reset</span>
                        </a>
                    @endif

                    <!-- Submit Button -->
                    <button type="submit" 
                            style="background-color: #005050;" 
                            class="px-6 py-3.5 text-white hover:opacity-95 font-bold text-xs rounded-xl transition-all flex items-center gap-2 shadow-sm shadow-teal-950/20">
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        <span>Terapkan</span>
                    </button>
                </div>
            </div>

            <!-- Collapsible Advanced Filtering Panel -->
            <div x-show="showAdvanced" 
                 x-collapse
                 x-cloak
                 class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-4 border-t border-dashed border-slate-100">
                
                <!-- Filter 1: Kategori Tipe -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Jenis Transaksi</label>
                    <select name="type" 
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-3 text-xs font-semibold text-slate-800 focus:bg-white focus:border-emerald-600 transition-all outline-none">
                        <option value="all" {{ $currentType === 'all' ? 'selected' : '' }}>-- Semua Jenis --</option>
                        <option value="sale_cash" {{ $currentType === 'sale_cash' ? 'selected' : '' }}>Penjualan Tunai</option>
                        <option value="sale_debt" {{ $currentType === 'sale_debt' ? 'selected' : '' }}>Penjualan Piutang</option>
                        <option value="payment" {{ $currentType === 'payment' ? 'selected' : '' }}>Pembayaran Cicilan</option>
                        <option value="production" {{ $currentType === 'production' ? 'selected' : '' }}>Produksi Barang</option>
                    </select>
                </div>

                <!-- Filter 2: Sort By -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Urutkan Berdasarkan</label>
                    <select name="sort_by" 
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-3 text-xs font-semibold text-slate-800 focus:bg-white focus:border-emerald-600 transition-all outline-none">
                        <option value="transaction_date" {{ $sortBy === 'transaction_date' ? 'selected' : '' }}>Tanggal Transaksi (Terbaru)</option>
                        <option value="input_time" {{ $sortBy === 'input_time' ? 'selected' : '' }}>Waktu Diinput (Terbaru)</option>
                        <option value="amount" {{ $sortBy === 'amount' ? 'selected' : '' }}>Nominal (Terbesar)</option>
                    </select>
                </div>

                <!-- Filter 3: Tanggal Mulai -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Tanggal Mulai</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ $startDate }}" 
                           class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-3 text-xs font-semibold text-slate-800 focus:bg-white focus:border-emerald-600 transition-all outline-none" />
                </div>

                <!-- Filter 4: Tanggal Akhir -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Tanggal Akhir</label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ $endDate }}" 
                           class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl p-3 text-xs font-semibold text-slate-800 focus:bg-white focus:border-emerald-600 transition-all outline-none" />
                </div>
            </div>
        </form>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
        
        <!-- Filter Tabs Header -->
        <div class="px-8 py-6 border-b border-slate-100 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <h4 class="text-lg font-black text-slate-900 font-manrope">Log Transaksi Terbaru</h4>
                <p class="text-xs text-slate-400">Log operasional terpadu yang memvisualisasikan seluruh arus kas masuk, keluar, dan produksi.</p>
            </div>
            
            <!-- Quick Filter Badges -->
            <div class="flex flex-wrap items-center gap-2 bg-slate-100/60 p-1.5 rounded-2xl border border-slate-200/40 w-fit">
                <a href="{{ route('history.index', array_merge(request()->query(), ['type' => 'all'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentType === 'all' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-white/50' }}">
                    Semua
                </a>
                <a href="{{ route('history.index', array_merge(request()->query(), ['type' => 'sale_cash'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentType === 'sale_cash' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-white/50' }}">
                    Penjualan Tunai
                </a>
                <a href="{{ route('history.index', array_merge(request()->query(), ['type' => 'sale_debt'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentType === 'sale_debt' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-white/50' }}">
                    Penjualan Piutang
                </a>
                <a href="{{ route('history.index', array_merge(request()->query(), ['type' => 'payment'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentType === 'payment' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-white/50' }}">
                    Cicilan Piutang
                </a>
                <a href="{{ route('history.index', array_merge(request()->query(), ['type' => 'production'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentType === 'production' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-white/50' }}">
                    Produksi
                </a>
            </div>
        </div>

        <!-- Table Area -->
        <div class="overflow-x-auto min-w-full">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="px-8 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Waktu Transaksi</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Tipe</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Detail Transaksi</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Jumlah / Nominal</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/40 transition-colors group">
                            <!-- Column 1: Waktu -->
                            <td class="px-8 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-800">
                                        {{ $log['time']->translatedFormat('d M Y') }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-xs">schedule</span>
                                        {{ $log['time']->format('H:i') }} ({{ $log['time']->diffForHumans() }})
                                    </span>
                                </div>
                            </td>
                            <!-- Column 2: Tipe -->
                            <td class="px-6 py-5">
                                @if($log['type'] === 'sale')
                                    @if($log['subtype'] === 'sale_debt')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-600 border border-amber-100/50">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Piutang Tempo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-600 border border-emerald-100/50">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Penjualan Tunai
                                        </span>
                                    @endif
                                @elseif($log['type'] === 'production')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-indigo-50 text-indigo-600 border border-indigo-100/50">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                        Produksi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-blue-50 text-blue-600 border border-blue-100/50">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                        Cicilan Piutang
                                    </span>
                                @endif
                            </td>
                            <!-- Column 3: Detail Transaksi -->
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-{{ $log['color'] }}-50 text-{{ $log['color'] }}-600 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                                        <span class="material-symbols-outlined text-lg">{{ $log['icon'] }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900">{{ $log['title'] }}</p>
                                        <p class="text-xs text-slate-400 font-semibold mt-0.5">{{ $log['details'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <!-- Column 4: Jumlah / Nominal -->
                            <td class="px-6 py-5">
                                <span class="text-sm font-black text-slate-900 bg-slate-100/50 px-3 py-1.5 rounded-xl border border-slate-200/20 font-mono">
                                    {{ $log['amount'] }}
                                </span>
                            </td>
                            <!-- Column 5: Status -->
                            <td class="px-6 py-5">
                                <span class="inline-flex px-2.5 py-1 rounded-xl text-xs font-bold {{ $log['status_color'] }}">
                                    {{ $log['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-slate-400">
                                <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <span class="material-symbols-outlined text-3xl">hourglass_empty</span>
                                </div>
                                <p class="text-sm font-black text-slate-800">Tidak ada riwayat transaksi</p>
                                <p class="text-xs text-slate-400 mt-1">Belum ada aktivitas tercatat pada kategori atau filter ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($logs->hasPages())
            <div class="px-8 py-6 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap');
    body { font-family: 'Manrope', sans-serif; }
</style>
@endsection

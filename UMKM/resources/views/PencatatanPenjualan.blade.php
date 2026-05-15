@extends('layouts.app')
@section('title', 'Penjualan')
@section('page_title', 'Pencatatan Penjualan')
@section('search_placeholder', 'Cari transaksi...')

@section('content')
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-10">
<!-- Page Heading & Overview -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="space-y-1 w-full">
        <h1 class="text-3xl font-extrabold font-headline text-on-surface tracking-tight break-words">Pencatatan Penjualan</h1>
        <p class="text-on-surface-variant font-body text-sm md:text-base">Kelola transaksi harian dan pantau performa bisnis Anda secara real-time.</p>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
        <div class="flex flex-row gap-2 w-full sm:w-auto">
            <a href="{{ route('sales.export-pdf') }}" target="_blank" class="flex-1 sm:flex-none px-4 py-2 rounded-lg bg-surface-container-highest text-on-surface font-semibold text-xs hover:bg-surface-container-high transition-colors flex items-center justify-center gap-2" title="Ekspor ke PDF">
                <span class="material-symbols-outlined text-[16px] flex-shrink-0">picture_as_pdf</span> PDF
            </a>
            <a href="{{ route('sales.export-sheets') }}" class="flex-1 sm:flex-none px-4 py-2 rounded-lg bg-teal-50 text-teal-700 font-semibold text-xs hover:bg-teal-100 transition-colors flex items-center justify-center gap-2 border border-teal-200" title="Unduh Excel (XLSX)">
                <span class="material-symbols-outlined text-[16px] flex-shrink-0">table</span> Spreadsheet
            </a>
        </div>
        <div class="px-6 py-4 bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 w-full sm:w-auto hover:shadow-md transition-all duration-300">
            <div class="p-3 bg-teal-50 rounded-lg text-teal-700 flex-shrink-0">
                <span class="material-symbols-outlined flex-shrink-0" data-icon="payments">payments</span>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Omzet Hari Ini</p>
                <p class="text-xl font-bold font-headline text-teal-900" data-analytics="total-revenue">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>
<!-- Bento Layout for POS Interface -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
<!-- Input Panel (POS Form) -->
<section class="lg:col-span-7 bg-surface-container-low p-1 rounded-xl">
<div class="bg-surface-container-lowest p-8 rounded-xl ambient-shadow">
<div class="mb-8">
<h3 class="text-lg font-bold font-headline text-primary">Input Transaksi Baru</h3>
<p class="text-sm text-on-surface-variant">Detail pesanan pelanggan</p>
</div>
<form class="space-y-6" action="{{ route('sales.store') }}" method="POST" data-realtime-submit="true" data-reset-on-success="true">
@csrf
<div class="grid grid-cols-2 gap-6">
<div class="col-span-2 md:col-span-1 space-y-2">
<label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Produk / Layanan</label>
<select name="product_id" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20 transition-all outline-none">
<option>Pilih Produk</option>
@foreach ($products as $product)
<option value="{{ $product->id }}" data-product-option="{{ $product->id }}" data-product-name="{{ $product->name }}" data-stock-value="{{ (int) ($product->stock ?? 0) }}">{{ $product->name }} (Stok: {{ number_format((int) ($product->stock ?? 0), 0, ',', '.') }})</option>
@endforeach
</select>
@if ($products->isEmpty())
<a class="text-xs text-[#005050] font-bold hover:underline" href="{{ route('products.index') }}">Tambah produk dulu</a>
@endif
</div>
<div class="col-span-2 md:col-span-1 space-y-2">
<label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Jumlah (Quantity)</label>
<input name="quantity" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="0" type="number"/>
</div>
</div>
<div class="space-y-2">
<label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Nama Pelanggan (Opsional)</label>
<input name="customer" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Contoh: Budi Santoso" type="text"/>
</div>
<div class="space-y-4">
<label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Metode Pembayaran</label>
<div class="grid grid-cols-3 gap-3">
<label class="relative flex flex-col items-center justify-center p-4 bg-surface-container-low rounded-xl cursor-pointer hover:bg-teal-50 transition-colors border-2 border-transparent has-[:checked]:border-primary has-[:checked]:bg-teal-50">
<input checked="" class="hidden" name="payment_method" type="radio" value="cash"/>
<span class="material-symbols-outlined text-primary mb-2" data-icon="account_balance_wallet">account_balance_wallet</span>
<span class="text-xs font-semibold">Tunai</span>
</label>
<label class="relative flex flex-col items-center justify-center p-4 bg-surface-container-low rounded-xl cursor-pointer hover:bg-teal-50 transition-colors border-2 border-transparent has-[:checked]:border-primary has-[:checked]:bg-teal-50">
<input class="hidden" name="payment_method" type="radio" value="transfer"/>
<span class="material-symbols-outlined text-primary mb-2" data-icon="sync_alt">sync_alt</span>
<span class="text-xs font-semibold">Transfer</span>
</label>
<label class="relative flex flex-col items-center justify-center p-4 bg-surface-container-low rounded-xl cursor-pointer hover:bg-teal-50 transition-colors border-2 border-transparent has-[:checked]:border-primary has-[:checked]:bg-teal-50">
<input class="hidden" name="payment_method" type="radio" value="qris"/>
<span class="material-symbols-outlined text-primary mb-2" data-icon="qr_code_2">qr_code_2</span>
<span class="text-xs font-semibold">QRIS</span>
</label>
</div>
</div>
<div class="pt-6 border-t border-outline-variant/10">
<button class="w-full py-4 rounded-xl shadow-lg shadow-teal-900/30 flex items-center justify-center gap-2 active:scale-[0.98] transition-all" 
        style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
        type="submit">
<span class="material-symbols-outlined" data-icon="check_circle">check_circle</span>
<span>Catat Transaksi</span>
</button>
</div>
</form>
</div>
</section>
<!-- Summary Panel -->
<aside class="lg:col-span-5 space-y-8">
<!-- Today's Stats Cards -->
<div class="grid grid-cols-2 gap-4">
<div class="p-6 rounded-xl hover:shadow-md transition-all duration-300" style="background-color: #005050 !important; color: #ffffff !important;">
<span class="material-symbols-outlined text-3xl opacity-50 mb-4 flex-shrink-0" data-icon="shopping_cart">shopping_cart</span>
<p class="text-xs font-bold uppercase tracking-widest text-primary-fixed/60 mb-1">Total Unit</p>
<p class="text-2xl font-black font-headline" data-analytics="total-sales">{{ number_format($todayUnits, 0, ',', '.') }} Pcs</p>
</div>
<div class="bg-tertiary-container p-6 rounded-xl text-on-tertiary-container hover:shadow-md transition-all duration-300">
<span class="material-symbols-outlined text-3xl opacity-50 mb-4 flex-shrink-0" data-icon="group">group</span>
<p class="text-xs font-bold uppercase tracking-widest text-on-tertiary-container/60 mb-1">Transaksi</p>
<p class="text-2xl font-black font-headline" data-analytics="total-transactions">{{ number_format($todayTransactions, 0, ',', '.') }} Nota</p>
</div>
</div>
<!-- Quick View Today's Sales -->
<div class="bg-surface-container-low rounded-xl overflow-hidden">
<div class="px-6 py-4 bg-surface-container-high flex justify-between items-center">
<h4 class="text-sm font-bold font-headline text-teal-900 uppercase tracking-widest">Produk Terlaris Hari Ini</h4>
<a class="text-[10px] font-bold text-primary hover:underline" href="{{ route('reports.index') }}">Lihat Semua</a>
</div>
<ul class="p-2 space-y-1 list-group" data-analytics="top-products">
@forelse ($topProducts as $topProduct)
<li class="list-group-item d-flex justify-content-between align-items-center p-4 bg-surface-container-lowest rounded-lg">
<span class="text-sm font-medium">{{ $topProduct['name'] }}</span>
<span class="px-3 py-1 bg-primary/10 text-primary text-[10px] font-bold rounded-full">{{ number_format((int) $topProduct['qty'], 0, ',', '.') }} unit</span>
</li>
@empty
<li class="list-group-item d-flex justify-content-between align-items-center p-4 bg-surface-container-lowest rounded-lg">
<span class="text-sm">Belum ada transaksi hari ini</span>
<span class="badge bg-primary rounded-pill">0 unit</span>
</li>
@endforelse
</ul>
</div>
</div>
<!-- Marketing Banner / Tip -->
<div class="relative h-40 rounded-xl overflow-hidden group">
<img alt="Point of Sale context" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" data-alt="Close-up of a wooden counter with a digital payment terminal and modern interior shop lighting in the background" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBC4U0wF_xyXdurCsHFHkS0jz3GAIoEFF0MmFILpzeRrsZyscGMMve6QSRYvMcWLd-AOPBjPzZWIWAgSFzebFBueYK6xkL9nbPSKVsY5OzHx8w-Yr1qN1HdUdmvpfIWxAFf3fozonRvwfGAZkqS9J0XBLNWLQ0RWCL8XsWiGtmXPsHz9qaqpXUrtP6JHomWORtHRzHaYcfbHF6wxTAEkvxUiJ7rvL5zOTrA7NzqvteSA9fJ_JWshB7rT2vnQxj-GHhf-l3Lel6qbfI"/>
<div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                <p class="text-xs font-bold text-teal-400 uppercase tracking-widest mb-1">
                    <span class="material-symbols-outlined text-[10px] align-middle">{{ $businessTip['icon'] ?? 'lightbulb' }}</span>
                    Insight: {{ $businessTip['title'] ?? 'Tips Bisnis' }}
                </p>
                <h4 class="text-white text-sm font-bold leading-tight">{{ $businessTip['content'] ?? 'Catat penjualan Anda setiap hari.' }}</h4>
</div>
</div>
</aside>
</div>
<!-- Detailed Sales Table Section -->
<section class="space-y-4">
{{-- Search, Sort & Filter Controls --}}
<x-table-controls
    :action="route('sales.index')"
    searchPlaceholder="Cari pelanggan, produk, metode bayar..."
    :sortOptions="[
        ['value' => 'created_at_desc', 'label' => 'Terbaru'],
        ['value' => 'created_at_asc', 'label' => 'Terlama'],
        ['value' => 'total_desc', 'label' => 'Total Tertinggi'],
        ['value' => 'total_asc', 'label' => 'Total Terendah'],
    ]"
    :filterOptions="[
        ['name' => 'payment_method', 'label' => 'Metode Bayar', 'choices' => ['cash' => 'Cash', 'transfer' => 'Transfer', 'qris' => 'QRIS']],
        ['name' => 'status', 'label' => 'Status', 'choices' => ['paid' => 'Lunas', 'unpaid' => 'Belum Lunas']],
    ]"
/>

<div class="flex justify-between items-center">
<h3 class="text-xl font-extrabold font-headline text-teal-950">History Transaksi</h3>
<span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-1.5 rounded-full border border-outline-variant/5">
    {{ $salesHistory->total() }} transaksi ditemukan
</span>
</div>
<div class="w-full overflow-x-auto border border-gray-100 rounded-lg mb-4 bg-surface-container-lowest shadow-sm" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
<table class="min-w-[800px] w-full text-xs text-left border-collapse whitespace-nowrap">
<thead>
<tr class="bg-surface-container-high border-b border-outline-variant/10">
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Waktu</th>
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Produk</th>
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant text-center">Qty</th>
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Pelanggan</th>
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Metode</th>
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant text-right">Total Tagihan</th>
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Status</th>
<th class="px-8 py-5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant text-right">Aksi</th>
</tr>
</thead>
<tbody class="text-sm font-body divide-y divide-slate-50" data-sales-history-table="true">
@forelse ($salesHistory as $sale)
<tr class="hover:bg-primary/5 transition-colors group" data-timestamp="{{ $sale->created_at->timestamp }}">
<td class="px-8 py-6 text-on-surface-variant">{{ $sale->created_at->format('d M Y H:i') }}</td>
<td class="px-8 py-6 font-bold text-teal-900">{{ $sale->items->first()?->product?->name ?? '-' }}</td>
<td class="px-8 py-6 text-center">{{ (int) $sale->items->sum('quantity') }}</td>
<td class="px-8 py-6">{{ $sale->customer ?: 'Walk-in' }}</td>
<td class="px-8 py-6">
<span class="flex items-center gap-2">
<span class="w-2 h-2 rounded-full {{ $sale->payment_method === 'cash' ? 'bg-amber-500' : ($sale->payment_method === 'qris' ? 'bg-teal-500' : 'bg-blue-500') }}"></span>
{{ strtoupper($sale->payment_method) }}
</span>
</td>
<td class="px-8 py-6 text-right font-black">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
<td class="px-8 py-6">
<span class="px-3 py-1 {{ $sale->status === 'paid' ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700' }} rounded-full text-[10px] font-bold">{{ $sale->status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</span>
</td>
<td class="px-8 py-6 text-right">
@if(auth()->user()->isAdmin())
<form action="{{ route('sales.destroy', $sale) }}" method="POST" onsubmit="return confirm('Hapus transaksi ini?')">
@csrf
@method('DELETE')
<button class="px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 transition-colors flex items-center gap-1" type="submit">
<span class="material-symbols-outlined text-sm">delete</span>
<span>Hapus</span>
</button>
</form>
@endif
</td>
</tr>
@empty
<tr data-sales-history-empty="true">
<td class="px-8 py-6 text-sm text-on-surface-variant" colspan="8">Belum ada riwayat transaksi.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-8 py-4 bg-surface-container-low border-t border-outline-variant/5 rounded-b-xl">
    {{ $salesHistory->appends(request()->query())->links() }}
</div>
</section>
</div>
@endsection

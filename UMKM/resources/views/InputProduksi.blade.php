@extends('layouts.app')
@section('title', 'Produksi')
@section('page_title', 'Input Produksi')
@section('search_placeholder', 'Cari batch...')

@section('content')
<!-- Canvas -->
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-10">
<!-- Page Header -->
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="w-full">
        <h2 class="text-3xl font-extrabold text-teal-900 tracking-tight break-words">Input Produksi</h2>
        <p class="text-on-surface-variant font-body mt-1 text-sm md:text-base">Catat batch produksi baru dan pantau penggunaan bahan baku secara real-time untuk akurasi HPP.</p>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
        <div class="flex flex-row gap-2 w-full sm:w-auto">
            <a href="{{ route('productions.export-pdf') }}" target="_blank" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-surface-container-highest text-on-surface font-semibold text-sm hover:bg-surface-container-high transition-colors flex items-center justify-center gap-2" title="Ekspor ke PDF">
                <span class="material-symbols-outlined text-sm flex-shrink-0">picture_as_pdf</span> PDF
            </a>
            <a href="{{ route('productions.export-sheets') }}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-teal-50 text-teal-700 font-semibold text-sm hover:bg-teal-100 transition-colors flex items-center justify-center gap-2 border border-teal-200" title="Unduh Excel (XLSX)">
                <span class="material-symbols-outlined text-sm flex-shrink-0">table</span> Spreadsheet
            </a>
        </div>
        @if(auth()->user()->isAdmin())
            <a class="flex-1 sm:flex-none px-6 py-2.5 rounded-xl shadow-lg shadow-teal-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
               style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
               href="#production-form">
                <span class="material-symbols-outlined text-base flex-shrink-0">add_circle</span>
                <span>Batch Baru</span>
            </a>
        @else
            <button class="flex-1 sm:flex-none px-6 py-2.5 bg-slate-200 text-slate-400 text-sm font-semibold rounded-xl cursor-not-allowed flex items-center justify-center gap-2" type="button" disabled title="Hanya admin yang dapat membuat batch produksi">
                <span class="material-symbols-outlined text-base flex-shrink-0">lock</span>
                <span>Batch Baru</span>
            </button>
        @endif
    </div>
</div>
@if (session('success'))
<div class="rounded-xl bg-teal-50 text-teal-800 border border-teal-100 px-4 py-3 text-sm font-medium">
{{ session('success') }}
</div>
@endif
@if ($errors->any())
<div class="rounded-xl bg-red-50 text-red-800 border border-red-100 px-4 py-3 text-sm font-medium space-y-1">
@foreach ($errors->all() as $error)
<div>{{ $error }}</div>
@endforeach
</div>
@endif
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Batch Hari Ini</p>
<h3 class="mt-2 text-3xl font-extrabold text-teal-900">{{ $batchesToday }}</h3>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Selesai Hari Ini</p>
<h3 class="mt-2 text-3xl font-extrabold text-teal-900">{{ $doneBatchesToday }}</h3>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Yield Rata-rata</p>
<h3 class="mt-2 text-3xl font-extrabold text-teal-900">{{ number_format($avgYieldToday, 1, ',', '.') }}%</h3>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Rata-rata HPP</p>
<h3 class="mt-2 text-3xl font-extrabold text-teal-900">Rp {{ number_format($avgHpp, 0, ',', '.') }}</h3>
</article>
</div>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
@if(auth()->user()->isAdmin())
<!-- Production Form Card -->
<section class="lg:col-span-8 bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden" id="production-form">
<form action="{{ route('productions.store') }}" method="POST">
@csrf
<div class="px-8 py-6 bg-surface-container-low">
<h3 class="text-lg font-bold text-primary flex items-center">
<span class="material-symbols-outlined mr-2 text-primary">precision_manufacturing</span> Mulai Batch Produksi Baru
                        </h3>
</div>
<div class="p-8 space-y-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tanggal Produksi</label>
<input name="production_date" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" type="date" value="{{ now()->format('Y-m-d') }}"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nama Produk Jadi</label>
<select name="product_id" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all">
<option value="">Pilih produk</option>
@forelse ($products as $product)
<option value="{{ $product->id }}">{{ $product->name }}</option>
@empty
<option>Tidak ada produk</option>
@endforelse
</select>
@if ($products->isEmpty())
<a class="text-xs text-primary font-semibold hover:underline" href="{{ route('products.index') }}">Tambah produk dulu</a>
@endif
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Target Kuantitas (Unit)</label>
<input name="quantity" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" placeholder="0" type="number"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Penanggung Jawab</label>
<input name="supervisor_name" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" placeholder="Nama Supervisor" type="text"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Produk Reject (Unit)</label>
<input name="reject_quantity" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" placeholder="0" type="number" min="0" value="0"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Status Batch</label>
<select name="status" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all">
<option value="process">Dalam Proses</option>
<option value="done">Selesai</option>
<option value="cancelled">Dibatalkan</option>
</select>
</div>
</div>
<!-- Raw Materials List -->
<div class="space-y-4">
<div class="flex justify-between items-center">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Daftar Bahan Baku Digunakan</label>
<button class="px-4 py-2 bg-[#005050]/10 text-[#005050] text-xs font-black rounded-lg flex items-center hover:bg-[#005050]/20 transition-all" type="button" id="add-production-material-row">
<span class="material-symbols-outlined text-sm mr-1">add_circle</span>
<span>Tambah Bahan Baku</span>
</button>
</div>
<div class="space-y-3" id="production-material-rows">
<!-- Row 1 -->
<div class="flex items-center space-x-4 bg-surface-container-low p-3 rounded-lg group production-material-row">
<div class="flex-1">
                                            <select name="materials[0][material_id]" class="w-full bg-transparent border-none text-sm font-medium focus:ring-0 material-select">
                                                @forelse ($materials as $material)
                                                    <option value="{{ $material->id }}" data-unit="{{ $material->unit }}">{{ $material->name }}</option>
                                                @empty
                                                    <option>Tidak ada bahan baku</option>
                                                @endforelse
                                            </select>
                                        </div>
                                        <div class="w-32 flex items-center space-x-2">
                                            <input name="materials[0][quantity]" class="w-full bg-white border-none rounded text-sm p-1 focus:ring-1 focus:ring-primary/20" placeholder="Qty" type="number"/>
                                            <span class="text-[10px] font-bold text-slate-400 unit-label">SATUAN</span>
                                        </div>
                                        <button class="text-slate-300 hover:text-error transition-colors" type="button">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </div>
                                    <!-- Row 2 -->
                                    <div class="flex items-center space-x-4 bg-surface-container-low p-3 rounded-lg group production-material-row">
                                        <div class="flex-1">
                                            <select name="materials[1][material_id]" class="w-full bg-transparent border-none text-sm font-medium focus:ring-0 material-select">
                                                @forelse ($materials as $material)
                                                    <option value="{{ $material->id }}" data-unit="{{ $material->unit }}">{{ $material->name }}</option>
                                                @empty
                                                    <option>Tidak ada bahan baku</option>
                                                @endforelse
                                            </select>
                                        </div>
                                        <div class="w-32 flex items-center space-x-2">
                                            <input name="materials[1][quantity]" class="w-full bg-white border-none rounded text-sm p-1 focus:ring-1 focus:ring-primary/20" placeholder="Qty" type="number"/>
                                            <span class="text-[10px] font-bold text-slate-400 unit-label">SATUAN</span>
                                        </div>
<button class="text-slate-300 hover:text-error transition-colors" type="button">
<span class="material-symbols-outlined text-lg">delete</span>
</button>
</div>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Biaya Tenaga Kerja (Rp)</label>
<input name="labor_cost" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" placeholder="0" type="number" min="0" step="0.01" value="0"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Biaya Overhead Batch (Rp)</label>
<input name="overhead_cost_snapshot" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" placeholder="0" type="number" min="0" step="0.01" value="0"/>
</div>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Catatan Batch</label>
<textarea name="notes" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" rows="3" placeholder="Contoh: perubahan formula, kendala mesin, dll."></textarea>
</div>
<div class="pt-4 flex justify-end space-x-4">
<button class="text-on-surface-variant text-sm font-bold px-6 py-2 hover:bg-slate-50 rounded-lg transition-colors" type="button">Batal</button>
<button class="px-10 py-3 rounded-xl shadow-lg shadow-teal-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2" 
        style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
        type="submit">
    <span class="material-symbols-outlined text-base">save</span>
    <span>Simpan Batch Produksi</span>
</button>
</div>
</div>
</section>
  </form>
@else
<!-- Staff View - Production List Only -->
<section class="lg:col-span-8 space-y-6">
<div class="bg-blue-50 border border-blue-200 rounded-xl p-6 flex items-start gap-4">
<div class="p-3 bg-blue-100 rounded-lg text-blue-600">
<span class="material-symbols-outlined text-xl">info</span>
</div>
<div>
<p class="font-semibold text-blue-900">Akses View-Only</p>
<p class="text-sm text-blue-800 mt-1">Sebagai staff, Anda dapat melihat status produksi namun hanya admin yang dapat membuat batch produksi baru.</p>
</div>
</div>
</section>
@endif
<!-- Status & Insights Sidebar -->
<aside class="lg:col-span-4 space-y-6">
<div class="bg-teal-900 text-white rounded-xl p-8 relative overflow-hidden shadow-xl">
<div class="relative z-10">
<p class="text-teal-300 text-[10px] font-bold uppercase tracking-widest mb-1">Stock Alert</p>
<h4 class="text-xl font-bold mb-4">{{ $stockAlertMaterial?->name ?? 'Belum ada data stok' }}</h4>
<p class="text-teal-100 text-sm leading-relaxed mb-6">
{{ $stockAlertMaterial ? 'Tersisa ' . number_format($stockAlertMaterial->stock, 0, ',', '.') . ' ' . $stockAlertMaterial->unit . '. Segera lakukan restock.' : 'Data bahan baku belum tersedia.' }}
</p>
<a href="{{ route('materials.index') }}" class="block text-center w-full bg-teal-800 text-teal-100 text-xs font-bold py-3 rounded-lg hover:bg-teal-700 transition-colors">Pesan Sekarang</a>
</div>
<div class="absolute -bottom-10 -right-10 opacity-10">
<span class="material-symbols-outlined text-[160px]" style="font-variation-settings: 'FILL' 1;">inventory_2</span>
</div>
</div>
<div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm space-y-4">
<h4 class="text-sm font-bold text-teal-900 border-b border-slate-50 pb-3">Estimasi HPP Sementara</h4>
<div class="space-y-3">
<div class="flex justify-between items-center">
<span class="text-xs text-on-surface-variant font-medium">Biaya Bahan Baku</span>
<span class="text-xs font-bold text-teal-900">Rp {{ number_format($materialCostEstimate, 0, ',', '.') }}</span>
</div>
<div class="flex justify-between items-center">
<span class="text-xs text-on-surface-variant font-medium">Overhead Workshop</span>
<span class="text-xs font-bold text-teal-900">Rp {{ number_format($overheadCostEstimate, 0, ',', '.') }}</span>
</div>
<div class="pt-3 border-t border-dashed border-slate-200 flex justify-between items-center">
<span class="text-sm font-bold text-teal-900">Total Produksi</span>
<span class="text-sm font-black text-teal-600">Rp {{ number_format($totalProductionEstimate, 0, ',', '.') }}</span>
</div>
</div>
</div>
</aside>
</div>
{{-- Search, Sort & Filter Controls --}}
<x-table-controls
    :action="route('productions.index')"
    searchPlaceholder="Cari batch code, produk, supervisor..."
    :sortOptions="[
        ['value' => 'production_date_desc', 'label' => 'Tanggal Terbaru'],
        ['value' => 'production_date_asc', 'label' => 'Tanggal Terlama'],
        ['value' => 'quantity_desc', 'label' => 'Kuantitas Tertinggi'],
        ['value' => 'quantity_asc', 'label' => 'Kuantitas Terendah'],
        ['value' => 'total_cost_snapshot_desc', 'label' => 'Biaya Tertinggi'],
        ['value' => 'unit_hpp_snapshot_asc', 'label' => 'HPP Terendah'],
        ['value' => 'unit_hpp_snapshot_desc', 'label' => 'HPP Tertinggi'],
    ]"
    :filterOptions="[
        ['name' => 'status', 'label' => 'Status', 'choices' => ['process' => 'Dalam Proses', 'done' => 'Selesai', 'cancelled' => 'Dibatalkan']],
    ]"
/>

<section class="space-y-6">
<div class="flex items-center justify-between">
<h3 class="text-xl font-extrabold text-teal-900 tracking-tight">Batch Produksi</h3>
<span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-1.5 rounded-full border border-outline-variant/5">
    {{ $runningProductions->total() }} batch ditemukan
</span>
</div>
<div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4 bg-surface-container-lowest shadow-sm" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
<table class="min-w-[800px] w-full text-xs text-left border-collapse whitespace-nowrap">
<thead>
<tr class="bg-surface-container-high">
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">ID Batch</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Produk</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest text-center">Qty</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest text-center">Yield</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">HPP/Unit</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Mulai</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Status</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest text-right">Aksi</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-50">
@forelse ($runningProductions as $production)
<tr class="hover:bg-primary-fixed/10 transition-all cursor-pointer group">
<td class="px-8 py-5">
<span class="text-sm font-bold text-teal-900">{{ $production->batch_code ?: '#PRD-'.str_pad((string) $production->id, 4, '0', STR_PAD_LEFT) }}</span>
</td>
<td class="px-8 py-5">
<div class="flex items-center">
<div>
<p class="text-sm font-bold text-teal-900">{{ $production->product?->name ?? '-' }}</p>
<p class="text-[10px] font-semibold text-slate-400">{{ $production->supervisor_name ?: 'Tanpa supervisor' }}</p>
</div>
</div>
</td>
<td class="px-8 py-5 text-center">
<span class="text-sm font-bold text-teal-900">{{ number_format($production->quantity, 0, ',', '.') }} Unit</span>
</td>
<td class="px-8 py-5 text-center">
<span class="text-sm font-bold text-teal-900" data-production-qty="{{ $production->id }}">{{ number_format((int) ($production->good_quantity ?? 0), 0, ',', '.') }}</span>
</td>
<td class="px-8 py-5">
<span class="text-sm font-bold text-teal-900">Rp {{ number_format($production->unit_hpp_snapshot ?? 0, 0, ',', '.') }}</span>
</td>
<td class="px-8 py-5">
<span class="text-sm font-medium text-on-surface-variant">{{ \Carbon\Carbon::parse($production->production_date)->translatedFormat('d M Y') }}</span>
</td>
<td class="px-8 py-5">
<span class="badge" data-production-status="{{ $production->id }}" data-status="{{ $production->status }}" style="display: inline-flex; align-items: center; padding: 0.5rem 0.75rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; {{ $production->status === 'done' ? 'background-color: #f3f4f6; color: #4b5563;' : ($production->status === 'cancelled' ? 'background-color: #fef2f2; color: #b91c1c;' : 'background-color: #f0fdfa; color: #0d9488;') }}">
{{ $production->status === 'done' ? 'Selesai' : ($production->status === 'cancelled' ? 'Dibatalkan' : 'Dalam Proses') }}
</span>
</td>
<td class="px-8 py-5 text-right">
<div class="inline-flex items-center gap-1">
@if ($production->status !== 'done')
    @if(auth()->user()->isAdmin())
    <form action="{{ route('productions.update-status', $production) }}" method="POST" data-realtime-submit="true" data-production-action="done" data-production-id="{{ $production->id }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="production_id" value="{{ $production->id }}"/>
    <input type="hidden" name="status" value="done"/>
    <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 transition-colors flex items-center gap-1" type="submit" title="Tandai selesai">
    <span class="material-symbols-outlined text-sm">check_circle</span>
    <span>Selesai</span>
    </button>
    </form>
    @endif
@endif

@if ($production->status !== 'cancelled')
    @if(auth()->user()->isAdmin())
    <form action="{{ route('productions.update-status', $production) }}" method="POST" data-realtime-submit="true" data-production-action="cancelled" data-production-id="{{ $production->id }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="production_id" value="{{ $production->id }}"/>
    <input type="hidden" name="status" value="cancelled"/>
    <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors flex items-center gap-1" type="submit" title="Batalkan batch">
    <span class="material-symbols-outlined text-sm">cancel</span>
    <span>Batal</span>
    </button>
    </form>
    @endif
@endif

@if(auth()->user()->isAdmin())
<form action="{{ route('productions.destroy', $production) }}" method="POST" onsubmit="return confirm('Hapus batch produksi ini?')">
@csrf
@method('DELETE')
<button class="px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 transition-colors flex items-center gap-1" type="submit" title="Hapus batch">
<span class="material-symbols-outlined text-sm">delete</span>
<span>Hapus</span>
</button>
</form>
@endif
</div>
</td>
</tr>
@empty
<tr>
<td class="px-8 py-6 text-sm text-on-surface-variant" colspan="8">Belum ada batch produksi.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-8 py-4 bg-surface-container-low border-t border-outline-variant/5 rounded-b-xl">
    {{ $runningProductions->appends(request()->query())->links() }}
</div>
</section>
</div>
@endsection

@section('scripts')
<script>
(() => {
  const addButton = document.getElementById('add-production-material-row');
  const rowsContainer = document.getElementById('production-material-rows');

  if (!addButton || !rowsContainer) {
    return;
  }

    const bindUnitChange = (select) => {
    const updateUnit = () => {
      const selectedOption = select.options[select.selectedIndex];
      const unit = selectedOption ? selectedOption.dataset.unit : '-';
      const row = select.closest('.production-material-row');
      const unitLabel = row.querySelector('.unit-label');
      if (unitLabel) unitLabel.textContent = unit;
    };
    select.addEventListener('change', updateUnit);
    updateUnit(); // Initial call
  };

  rowsContainer.querySelectorAll('.material-select').forEach(bindUnitChange);

  const bindDeleteButton = (button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      const row = button.closest('.production-material-row');
      if (row && rowsContainer.querySelectorAll('.production-material-row').length > 1) {
        row.remove();
      }
    });
  };

  rowsContainer.querySelectorAll('.production-material-row button').forEach(bindDeleteButton);

  addButton.addEventListener('click', () => {
    const rows = rowsContainer.querySelectorAll('.production-material-row');
    const nextIndex = rows.length;
    const template = rows[0].cloneNode(true);

    template.querySelectorAll('select, input').forEach((element) => {
      if (element.tagName === 'SELECT') {
        element.name = `materials[${nextIndex}][material_id]`;
        element.selectedIndex = 0;
      }

      if (element.tagName === 'INPUT') {
        element.name = `materials[${nextIndex}][quantity]`;
        element.value = '';
      }
    });

    const select = template.querySelector('select');
    if (select) bindUnitChange(select);

    const deleteButton = template.querySelector('button');
    if (deleteButton) {
      bindDeleteButton(deleteButton);
    }

    rowsContainer.appendChild(template);
  });
})();
</script>
@endsection

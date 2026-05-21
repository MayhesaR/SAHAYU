@extends('layouts.app')
@section('title', 'Data Pelanggan')
@section('page_title', 'Manajemen Customer & CRM')
@section('search_placeholder', 'Cari pelanggan...')

@section('content')
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="w-full">
            <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 tracking-tight break-words">Manajemen Customer & CRM</h2>
            <p class="text-on-surface-variant font-body mt-1 max-w-xl text-sm sm:text-base">Kelola basis data pelanggan, lacak histori transaksi total belanja, dan kontrol sisa kasbon piutang secara digital.</p>
        </div>
        <a class="w-full sm:w-auto px-6 py-2.5 rounded-xl shadow-lg shadow-emerald-900/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
           style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;" 
           href="#form-customer">
            <span class="material-symbols-outlined text-base flex-shrink-0">person_add</span>
            <span>Tambah Customer</span>
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 dark:text-emerald-300 border border-emerald-100 px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-950/40 text-red-800 dark:text-red-300 border border-red-100 px-4 py-3 text-sm font-medium space-y-1">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- Mini Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Total Customer</p>
            <h3 class="mt-2 text-3xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ $customers->total() }}</h3>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">mitra terdaftar di CRM</p>
        </article>
        <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Total Kasbon Aktif</p>
            <h3 class="mt-2 text-3xl font-extrabold text-amber-700 dark:text-amber-400">
                Rp {{ number_format((float) \App\Models\Debt::where('status', '!=', 'paid')->sum('remaining_amount'), 0, ',', '.') }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">akumulasi piutang yang belum terbayar</p>
        </article>
        <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Customer Loyal</p>
            <h3 class="mt-2 text-3xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">
                {{ $customers->filter(fn($c) => $c->sales->sum('total') > 500000)->count() }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">belanja di atas Rp 500k</p>
        </article>
    </div>

    <!-- Main CRM split screen -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Add Customer Panel -->
        <section class="lg:col-span-4 bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 overflow-hidden hover:shadow-md transition-all duration-300" id="form-customer">
            <div class="px-6 py-5 bg-surface-container-low border-b border-gray-100 dark:border-zinc-800/50">
                <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 flex items-center">
                    <span class="material-symbols-outlined mr-2 text-emerald-600 dark:text-emerald-400 flex-shrink-0">person_add</span> Tambah Customer
                </h3>
            </div>
            <form action="{{ route('customers.store') }}" method="POST" class="p-6 space-y-5">
                @csrf
                <div class="space-y-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nama Pelanggan / Toko</label>
                    <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all font-semibold" name="name" placeholder="Contoh: Bpk. Mayhesa" required type="text"/>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">No. WhatsApp / HP</label>
                    <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all font-semibold" name="phone" placeholder="Contoh: 081234567890" type="text"/>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Alamat Lengkap</label>
                    <textarea class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all font-semibold" name="address" rows="3" placeholder="Alamat lengkap toko atau rumah..."></textarea>
                </div>
                <button class="w-full px-6 py-3 rounded-full shadow-lg shadow-emerald-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
                        style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;" 
                        type="submit">
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>Simpan Customer</span>
                </button>
            </form>
        </section>

        <!-- Customer List Panel -->
        <section class="lg:col-span-8 space-y-4">
            
            <x-table-controls
                :action="route('customers.index')"
                searchPlaceholder="Cari nama, no HP, alamat..."
                :sortOptions="[
                    ['value' => 'name_asc', 'label' => 'Nama (A-Z)'],
                    ['value' => 'name_desc', 'label' => 'Nama (Z-A)'],
                    ['value' => 'created_at_desc', 'label' => 'Terbaru'],
                ]"
            />

            <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 overflow-hidden hover:shadow-md transition-all duration-300">
                <div class="px-6 py-5 bg-surface-container-low border-b border-gray-100 dark:border-zinc-800/50 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center justify-between w-full sm:w-auto">
                        <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 flex items-center">
                            <span class="material-symbols-outlined mr-2 text-emerald-600 dark:text-emerald-400 flex-shrink-0">group</span> Database CRM Customer
                        </h3>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto justify-start sm:justify-end">
                        <span class="flex-1 sm:flex-none text-center text-[10px] font-bold text-slate-400 dark:text-zinc-400 bg-white dark:bg-zinc-900 px-3 py-2 rounded-xl border border-outline-variant/5">{{ $customers->total() }} customer terdaftar</span>
                        
                        <!-- Status Filter Dropdown -->
                        <form action="{{ route('customers.index') }}" method="GET" class="flex-1 sm:flex-none flex items-center gap-2">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}" />
                            @endif
                            @if(request('sort_by'))
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}" />
                            @endif
                            <select name="status" 
                                    onchange="this.form.submit()" 
                                    class="w-full bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-700 dark:text-zinc-50 dark:text-zinc-200 outline-none focus:border-emerald-600 transition-all cursor-pointer shadow-sm">
                                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Status: Aktif</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Status: Inaktif</option>
                            </select>
                        </form>

                        <!-- Export Excel Button -->
                        <a href="{{ route('customers.export', request()->all()) }}" 
                           class="flex-1 sm:flex-none justify-center px-4 py-2.5 bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] text-white rounded-xl text-xs font-extrabold transition-all flex items-center gap-1.5 shadow-sm">
                            <span class="material-symbols-outlined text-[16px]">download</span>
                            <span>Ekspor Excel</span>
                        </a>
                    </div>
                </div>
                
                <div class="w-full overflow-x-auto border border-gray-100 dark:border-zinc-800/50 rounded-lg mb-4">
                    <table class="min-w-[800px] w-full text-xs text-left whitespace-nowrap">
                        <thead class="bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 text-left text-slate-500 dark:text-white uppercase text-xs tracking-widest">
                            <tr>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4">Kontak / Alamat</th>
                                <th class="px-6 py-4">Total Belanja</th>
                                <th class="px-6 py-4">Sisa Kasbon</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $c)
                                @php
                                    $totalBelanja = $c->sales->sum('total');
                                    $sisaKasbon = $c->debts->where('status', '!=', 'paid')->sum('remaining_amount');
                                @endphp
                                <tr class="border-t border-slate-100 dark:border-zinc-800/60 hover:bg-slate-50/70 dark:hover:bg-zinc-850/70 dark:hover:bg-zinc-800/70 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 text-emerald-700 dark:text-emerald-400 font-black text-sm flex items-center justify-center shadow-sm">
                                                {{ strtoupper(substr($c->name, 0, 2)) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 dark:text-white text-sm">{{ $c->name }}</span>
                                                <span class="text-[10px] font-bold text-slate-400 dark:text-zinc-400 uppercase tracking-widest mt-0.5">ID: #{{ str_pad($c->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-bold text-slate-700 dark:text-zinc-50 dark:text-zinc-200">{{ $c->phone ?: 'No HP Kosong' }}</span>
                                            <span class="text-slate-400 dark:text-zinc-400 font-semibold truncate max-w-xs">{{ $c->address ?: 'Alamat belum diinput' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 dark:text-emerald-300 rounded-lg border border-emerald-100 font-extrabold text-xs">
                                            Rp {{ number_format($totalBelanja, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($sisaKasbon > 0)
                                            <span class="px-2.5 py-1 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 rounded-lg border border-rose-100 font-extrabold text-xs">
                                                Rp {{ number_format($sisaKasbon, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 text-slate-400 dark:text-white rounded-lg border border-slate-100 dark:border-zinc-800/60 font-extrabold text-xs">
                                                Lunas
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <!-- Edit customer (Accessible by both Staff and Admin) -->
                                            <button class="px-4 py-2 rounded-lg text-xs font-black text-[#0b6e4f] dark:text-emerald-400 bg-[#0b6e4f]/10 hover:bg-[#0b6e4f]/20 transition-colors flex items-center gap-1 edit-customer-btn"
                                                    data-id="{{ $c->id }}"
                                                    data-name="{{ $c->name }}"
                                                    data-phone="{{ $c->phone }}"
                                                    data-address="{{ $c->address }}">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                                <span>Edit</span>
                                            </button>
                                            
                                            <!-- Delete customer (Admin Only) -->
                                            @if(auth()->user()->isAdmin())
                                                <form action="{{ route('customers.destroy', $c) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data customer ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="px-4 py-2 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 hover:bg-red-100 transition-colors flex items-center gap-1" type="submit">
                                                        <span class="material-symbols-outlined text-sm">delete</span>
                                                        <span>Hapus</span>
                                                    </button>
                                                </form>
                                            @else
                                                <button class="px-4 py-2 bg-slate-100 dark:bg-zinc-800 text-slate-400 dark:text-white text-xs font-bold rounded-lg cursor-not-allowed flex items-center gap-1" disabled title="Hapus hanya diizinkan untuk Admin">
                                                    <span class="material-symbols-outlined text-sm">lock</span>
                                                    <span>Hapus</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-10 text-slate-500 dark:text-white text-center" colspan="5">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <span class="material-symbols-outlined text-4xl text-slate-400 dark:text-zinc-400 font-light">group_off</span>
                                            <p class="font-semibold">Belum ada customer terdaftar.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-t border-slate-100 dark:border-zinc-800/60">
                    {{ $customers->appends(request()->query())->links() }}
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Edit Customer Modal (Standard Center Overlay) -->
<div id="editCustomerModal" class="fixed inset-0 bg-slate-900/60 dark:bg-zinc-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white dark:bg-zinc-900 rounded-3xl p-8 w-full max-w-md shadow-2xl scale-95 opacity-0 transition-all duration-300 modal-content mx-4">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Edit Data Customer</h3>
            <button onclick="closeEditModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 transition-colors">
                <span class="material-symbols-outlined text-slate-400 dark:text-zinc-400">close</span>
            </button>
        </div>
        <form id="editCustomerForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Nama Customer</label>
                    <input name="name" id="edit_name" required class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-none rounded-xl focus:ring-2 focus:ring-primary/20 text-slate-700 dark:text-zinc-50 dark:text-zinc-200 font-bold" type="text"/>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 dark:text-zinc-400 uppercase tracking-widest">No. WhatsApp / HP</label>
                    <input name="phone" id="edit_phone" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-none rounded-xl focus:ring-2 focus:ring-primary/20 text-slate-700 dark:text-zinc-50 dark:text-zinc-200 font-bold" type="text"/>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 dark:text-zinc-400 uppercase tracking-widest">Alamat Lengkap</label>
                    <textarea name="address" id="edit_address" rows="3" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-none rounded-xl focus:ring-2 focus:ring-primary/20 text-slate-700 dark:text-zinc-50 dark:text-zinc-200 font-bold"></textarea>
                </div>
                <button class="w-full py-4 rounded-xl shadow-lg shadow-emerald-900/30 transition-all" 
                        style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;" 
                        type="submit">
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const editModal = document.getElementById('editCustomerModal');
    const editContent = editModal.querySelector('.modal-content');
    const editForm = document.getElementById('editCustomerForm');

    document.querySelectorAll('.edit-customer-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const phone = btn.dataset.phone;
            const address = btn.dataset.address;

            document.getElementById('edit_name').value = name;
            document.getElementById('edit_phone').value = phone || '';
            document.getElementById('edit_address').value = address || '';
            editForm.action = `/pelanggan/${id}`;

            editModal.classList.remove('hidden');
            setTimeout(() => {
                editContent.classList.remove('scale-95', 'opacity-0');
                editContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        });
    });

    function closeEditModal() {
        editContent.classList.add('scale-95', 'opacity-0');
        editContent.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => editModal.classList.add('hidden'), 300);
    }
</script>
@endsection

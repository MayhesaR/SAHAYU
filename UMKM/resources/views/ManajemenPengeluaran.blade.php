@extends('layouts.app')

@section('content')
<div class="px-4 py-6 sm:px-8 space-y-6 bg-slate-50/50 min-h-screen">
    
    <!-- HEADER -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <h1 class="text-3xl font-black text-slate-900 tracking-tight font-manrope">
                Catat Pengeluaran <span class="text-rose-600">(Petty Cash)</span>
            </h1>
            <p class="text-slate-500 font-medium text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">receipt_long</span>
                Pencatatan kas keluar harian operasional UMKM secara cepat, disiplin, dan real-time.
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
        <!-- Card 1: Hari Ini -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">calendar_today</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pengeluaran Hari Ini</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($todayExpensesSum, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-slate-400 font-medium mt-4">Kas keluar operasional yang dicatat hari ini</p>
        </div>

        <!-- Card 2: Bulan Ini -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">summarize</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Bulan Ini</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($monthExpensesSum, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-slate-400 font-medium mt-4">Akumulasi pengeluaran pada bulan ini</p>
        </div>

        <!-- Card 3: Total Logs -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-xl">receipt</span>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Catatan Kas Keluar</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalExpensesCount, 0, ',', '.') }} <span class="text-sm text-slate-400">Entri</span></h3>
            <p class="text-[10px] text-slate-400 font-medium mt-4">Jumlah nota petty cash yang telah dibukukan</p>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN COCKPIT LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Side: Form to Record Expense (5 columns) -->
        <div class="lg:col-span-5 bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-6">
            <div class="space-y-1">
                <h4 class="text-lg font-black text-slate-900 font-manrope">Catat Kas Keluar Baru</h4>
                <p class="text-xs text-slate-400">Masukkan detail pengeluaran operasional di bawah ini secara lengkap.</p>
            </div>

            <!-- Flash Alert -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold rounded-2xl flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('expenses.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- Tanggal -->
                <div class="space-y-2">
                    <label for="expense_date" class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Tanggal Pengeluaran</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-3.5 text-slate-400 text-sm">calendar_today</span>
                        <input type="date" 
                               id="expense_date" 
                               name="expense_date" 
                               value="{{ old('expense_date', date('Y-m-d')) }}" 
                               required 
                               class="w-full bg-slate-50/70 border-2 border-slate-100 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all outline-none" />
                    </div>
                    @error('expense_date')
                        <p class="text-rose-600 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori -->
                <div class="space-y-2">
                    <label for="category" class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Kategori Pengeluaran</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-3.5 text-slate-400 text-sm">category</span>
                        <select id="category" 
                                name="category" 
                                required
                                class="w-full bg-slate-50/70 border-2 border-slate-100 rounded-2xl pl-12 pr-10 py-3.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all outline-none appearance-none cursor-pointer">
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <option value="Listrik/Air" {{ old('category') === 'Listrik/Air' ? 'selected' : '' }}>Listrik/Air</option>
                            <option value="Transportasi" {{ old('category') === 'Transportasi' ? 'selected' : '' }}>Transportasi (BBM, Parkir, Tol)</option>
                            <option value="Perlengkapan" {{ old('category') === 'Perlengkapan' ? 'selected' : '' }}>Perlengkapan Toko</option>
                            <option value="Gaji/Honor" {{ old('category') === 'Gaji/Honor' ? 'selected' : '' }}>Gaji / Honor Harian</option>
                            <option value="Lain-lain" {{ old('category') === 'Lain-lain' ? 'selected' : '' }}>Lain-lain</option>
                        </select>
                    </div>
                    @error('category')
                        <p class="text-rose-600 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nominal -->
                <div class="space-y-2">
                    <label for="amount" class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Nominal Pengeluaran (Rupiah)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-slate-400 text-sm font-black">Rp</span>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               placeholder="Contoh: 15000" 
                               value="{{ old('amount') }}" 
                               required 
                               min="0"
                               step="0.01"
                               class="w-full bg-slate-50/70 border-2 border-slate-100 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all outline-none" />
                    </div>
                    @error('amount')
                        <p class="text-rose-600 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div class="space-y-2">
                    <label for="description" class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Keterangan / Deskripsi (Opsional)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-3.5 text-slate-400 text-sm">description</span>
                        <textarea id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="Tulis detail keperluan pengeluaran..." 
                                  class="w-full bg-slate-50/70 border-2 border-slate-100 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all outline-none">{{ old('description') }}</textarea>
                    </div>
                    @error('description')
                        <p class="text-rose-600 text-[10px] font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full py-4 text-white hover:opacity-95 font-bold text-sm rounded-2xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-rose-950/20 bg-rose-600">
                    <span class="material-symbols-outlined text-lg">save</span>
                    <span>Simpan Pengeluaran Baru</span>
                </button>
            </form>
        </div>

        <!-- Right Side: Recent Expenditures Table (7 columns) -->
        <div class="lg:col-span-7 bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col">
            
            <!-- Table Header -->
            <div class="px-8 py-6 border-b border-slate-100">
                <h4 class="text-lg font-black text-slate-900 font-manrope">Log Kas Keluar Terbaru</h4>
                <p class="text-xs text-slate-400">Daftar transaksi pengeluaran operasional terdaftar.</p>
            </div>

            <!-- Table Body -->
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70">
                            <th class="px-8 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Waktu / Tanggal</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Kategori</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Deskripsi</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Nominal</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-slate-50/40 transition-colors group">
                                <!-- Column 1: Tanggal -->
                                <td class="px-8 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-800">
                                            {{ $expense->expense_date->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1 mt-0.5">
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
                                            'Perlengkapan' => 'bg-teal-50 text-teal-600 border-teal-100/50',
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
                                <td class="px-6 py-5 text-xs text-slate-500 font-semibold max-w-[150px] truncate">
                                    {{ $expense->description ?: '-' }}
                                </td>

                                <!-- Column 4: Nominal -->
                                <td class="px-6 py-5">
                                    <span class="text-sm font-black text-rose-600 font-mono">
                                        - Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- Column 5: Aksi -->
                                <td class="px-6 py-5 text-center">
                                    <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan pengeluaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center mx-auto border border-rose-100">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                        <span class="material-symbols-outlined text-3xl">receipt_long</span>
                                    </div>
                                    <p class="text-sm font-black text-slate-800">Belum Ada Pengeluaran</p>
                                    <p class="text-xs text-slate-400 mt-1">Silakan gunakan form di samping untuk mencatat kas keluar harian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            @if($expenses->hasPages())
                <div class="px-8 py-6 border-t border-slate-100 bg-slate-50/50 mt-auto">
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
@endsection

@extends('layouts.app')
@section('title', 'Pengaturan Toko')
@section('page_title', 'Pengaturan Toko & Akun')

@section('content')
<div class="bg-stone-50 dark:bg-zinc-800 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-8">
        
        <!-- Flash Message Alerts -->
        @if (session('success'))
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-100 px-4 py-3.5 text-sm font-medium shadow-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border border-rose-100 px-4 py-3.5 text-sm font-medium shadow-sm space-y-1.5">
            <div class="flex items-center gap-2 font-bold text-rose-900 mb-1">
                <span class="material-symbols-outlined">error</span>
                Terjadi kesalahan input:
            </div>
            @foreach ($errors->all() as $error)
                <div class="pl-7 text-xs">{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left & Middle: Store Config & Printer Config -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Section 1: Informasi Toko -->
                <section class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-xl shadow-emerald-900/5 border border-stone-100/85 dark:border-zinc-800/85">
                    <div class="flex items-center justify-between border-b border-stone-100 dark:border-zinc-800/60 pb-4 mb-6">
                        <h3 class="text-base font-bold text-stone-850 dark:text-white flex items-center">
                            <span class="material-symbols-outlined mr-2.5 text-[#0b6e4f] dark:text-emerald-400 text-2xl">storefront</span> 
                            Informasi Toko (UMKM)
                        </h3>
                        <span class="text-[10px] uppercase font-bold text-stone-400 dark:text-white tracking-wider">Konfigurasi Toko</span>
                    </div>

                    <form action="{{ route('settings.company') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <!-- Logo Upload Section -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4 rounded-2xl bg-stone-50 dark:bg-zinc-800 border border-stone-200/50 dark:border-zinc-850">
                            <div class="shrink-0">
                                @if($company->logo)
                                    <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo Toko" class="w-20 h-20 object-contain rounded-2xl border border-stone-200 dark:border-zinc-800 p-1.5 bg-white dark:bg-zinc-900 shadow-sm">
                                @else
                                    <div class="w-20 h-20 rounded-2xl bg-stone-200 dark:bg-zinc-800 flex items-center justify-center text-stone-400 dark:text-white">
                                        <span class="material-symbols-outlined text-3xl">image</span>
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-stone-700 dark:text-zinc-50 dark:text-white">Logo Toko / PDF Header</label>
                                <input type="file" name="logo" class="block w-full text-xs text-stone-500 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0b6e4f]/10 file:text-[#0b6e4f] dark:file:text-emerald-400 hover:file:bg-[#0b6e4f]/20 file:cursor-pointer">
                                <p class="text-[10px] text-stone-400 dark:text-white">Format JPEG, PNG, JPG atau WebP (Maks. 2MB). Logo ini akan ditampilkan di header Laporan PDF.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-stone-600 dark:text-white uppercase tracking-wider">Nama UMKM</label>
                                <input class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-3 text-sm focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200 text-stone-800 dark:text-white font-medium" 
                                       name="name" value="{{ old('name', $company->name) }}" required type="text" placeholder="Masukkan nama UMKM"/>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-stone-600 dark:text-white uppercase tracking-wider">Nomor Telepon</label>
                                <input class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-3 text-sm focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200 text-stone-800 dark:text-white font-medium" 
                                       name="phone" value="{{ old('phone', $company->phone) }}" type="text" placeholder="Contoh: 08123456789"/>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-stone-600 dark:text-white uppercase tracking-wider">Email Toko</label>
                            <input class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-3 text-sm focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200 text-stone-800 dark:text-white font-medium" 
                                   name="email" value="{{ old('email', $company->email) }}" type="email" placeholder="Contoh: bakery@sahayu.com"/>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-stone-600 dark:text-white uppercase tracking-wider">Alamat Toko</label>
                            <textarea class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-3 text-sm focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200 text-stone-800 dark:text-white font-medium h-24 resize-none" 
                                      name="address" placeholder="Tuliskan alamat lengkap UMKM...">{{ old('address', $company->address) }}</textarea>
                        </div>

                        <div class="pt-2">
                            <button class="w-full sm:w-auto px-6 py-3 rounded-full shadow-lg shadow-[#0b6e4f]/15 dark:shadow-emerald-950/15 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 hover:bg-[#09573e] text-white" 
                                    style="background-color: #0b6e4f; font-weight: 800;" 
                                    type="submit">
                                <span class="material-symbols-outlined text-lg">save</span> Simpan Perubahan Toko
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Section 2: Konfigurasi Printer POS -->
                <section class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-xl shadow-emerald-900/5 border border-stone-100/85 dark:border-zinc-800/85">
                    <div class="flex items-center justify-between border-b border-stone-100 dark:border-zinc-800/60 pb-4 mb-6">
                        <h3 class="text-base font-bold text-stone-850 dark:text-white flex items-center">
                            <span class="material-symbols-outlined mr-2.5 text-[#0b6e4f] dark:text-emerald-400 text-2xl">print</span> 
                            Konfigurasi Printer Thermal POS
                        </h3>
                        <span class="text-[10px] uppercase font-bold text-stone-400 dark:text-white tracking-wider">Printer</span>
                    </div>

                    <form action="{{ route('settings.printer') }}" method="POST" class="space-y-5">
                        @csrf
                        
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-stone-600 dark:text-white uppercase tracking-wider">Ukuran Kertas Printer</label>
                            <div class="relative">
                                <select name="printer_paper_width" class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-3 text-sm focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200 text-stone-800 dark:text-white font-medium appearance-none">
                                    <option value="58mm" {{ $company->printer_paper_width === '58mm' ? 'selected' : '' }}>58mm (Printer Kasir Kecil / Bluetooth)</option>
                                    <option value="80mm" {{ $company->printer_paper_width === '80mm' ? 'selected' : '' }}>80mm (Printer Kasir Lebar / Desktop USB)</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-stone-400 dark:text-white pointer-events-none">unfold_more</span>
                            </div>
                            <p class="text-[10.5px] text-stone-400 dark:text-white leading-normal">Pilih lebar kertas yang digunakan oleh printer thermal Anda. Ini akan secara otomatis menyesuaikan format cetakan struk belanja transaksi POS agar tidak terpotong.</p>
                        </div>

                        <div class="pt-1">
                            <button class="w-full sm:w-auto px-6 py-3 rounded-full shadow-lg shadow-[#0b6e4f]/15 dark:shadow-emerald-950/15 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 hover:bg-[#09573e] text-white" 
                                    style="background-color: #0b6e4f; font-weight: 800;" 
                                    type="submit">
                                <span class="material-symbols-outlined text-lg">check</span> Terapkan Ukuran Printer
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- Right Column: Account Password Management -->
            <div class="space-y-8">
                
                <!-- Section 3: Ganti Password Pengguna -->
                <section class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-xl shadow-emerald-900/5 border border-stone-100/85 dark:border-zinc-800/85">
                    <div class="border-b border-stone-100 dark:border-zinc-800/60 pb-4 mb-6">
                        <h3 class="text-base font-bold text-stone-850 dark:text-white flex items-center">
                            <span class="material-symbols-outlined mr-2.5 text-[#0b6e4f] dark:text-emerald-400 text-2xl">lock</span> 
                            Ganti Kata Sandi Anda
                        </h3>
                    </div>

                    <form action="{{ route('settings.password') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-stone-600 dark:text-white uppercase tracking-wider">Kata Sandi Saat Ini</label>
                            <input class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-2.5 text-xs focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200" 
                                   name="current_password" required type="password"/>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-stone-600 dark:text-white uppercase tracking-wider">Kata Sandi Baru</label>
                            <input class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-2.5 text-xs focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200" 
                                   name="password" required type="password"/>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-stone-600 dark:text-white uppercase tracking-wider">Konfirmasi Kata Sandi Baru</label>
                            <input class="w-full bg-stone-50 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-2xl px-4 py-2.5 text-xs focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200" 
                                   name="password_confirmation" required type="password"/>
                        </div>

                        <div class="pt-2">
                            <button class="w-full px-5 py-2.5 rounded-full shadow-md hover:bg-[#09573e] text-white active:scale-95 transition-all text-xs font-extrabold flex items-center justify-center gap-1.5" 
                                    style="background-color: #0b6e4f;" 
                                    type="submit">
                                <span class="material-symbols-outlined text-sm">key</span> Perbarui Sandi Saya
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Section 4: Akun Toko & Reset (Owner/Staff) -->
                <section class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-xl shadow-emerald-900/5 border border-stone-100/85 dark:border-zinc-800/85">
                    <div class="border-b border-stone-100 dark:border-zinc-800/60 pb-4 mb-5">
                        <h3 class="text-base font-bold text-stone-850 dark:text-white flex items-center">
                            <span class="material-symbols-outlined mr-2.5 text-[#0b6e4f] dark:text-emerald-400 text-2xl">group</span> 
                            Manajemen Password Akun
                        </h3>
                    </div>

                    <div class="space-y-4">
                        @foreach($companyUsers as $u)
                        <div x-data="{ open: false }" class="p-3.5 rounded-2xl bg-stone-50 dark:bg-zinc-800 border border-stone-200/40 dark:border-zinc-800/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-[#0b6e4f]/10 text-[#0b6e4f] dark:text-emerald-400 flex items-center justify-center font-extrabold text-xs">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-stone-800 dark:text-white flex items-center gap-1">
                                            {{ $u->name }}
                                            @if($u->id === auth()->id())
                                                <span class="text-[9px] px-1.5 py-0.2 bg-stone-200 dark:bg-zinc-800 text-stone-600 dark:text-white rounded">Anda</span>
                                            @endif
                                        </p>
                                        <p class="text-[9px] text-stone-400 dark:text-white uppercase font-bold tracking-wider leading-none">{{ $u->role }} &bull; {{ $u->email }}</p>
                                    </div>
                                </div>
                                @if($u->id !== auth()->id())
                                <button @click="open = !open" 
                                        type="button" 
                                        class="px-2.5 py-1 rounded-lg border border-stone-200 dark:border-zinc-800 text-[10px] font-bold text-stone-700 dark:text-zinc-50 dark:text-white bg-white dark:bg-zinc-900 hover:bg-stone-50 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 shadow-sm active:scale-95 transition-all flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[10px]">key</span>
                                    Reset
                                </button>
                                @endif
                            </div>
                            
                            <!-- Reset Password Form for this user -->
                            <div x-show="open" x-collapse style="display: none;" class="pt-3.5 border-t border-stone-200/40 dark:border-zinc-800/40">
                                <form action="{{ route('settings.password') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                                    <div class="space-y-2">
                                        <div class="space-y-1">
                                            <label class="text-[9px] font-bold text-stone-500 dark:text-white uppercase tracking-wider">Sandi Baru</label>
                                            <input type="password" name="password" required class="w-full bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[9px] font-bold text-stone-500 dark:text-white uppercase tracking-wider">Konfirmasi Sandi Baru</label>
                                            <input type="password" name="password_confirmation" required class="w-full bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 outline-none transition-all duration-200">
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-1">
                                        <button @click="open = false" type="button" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold text-stone-500 dark:text-white hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80">Batal</button>
                                        <button type="submit" class="px-3.5 py-1 rounded-lg bg-[#0b6e4f] dark:bg-emerald-600 text-white text-[10px] font-bold shadow-sm shadow-[#0b6e4f]/25 dark:shadow-emerald-950/15 hover:bg-[#09573e] transition-all">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>

            </div>
            
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Manajemen Akun')
@section('page_title', 'Manajemen Akun')

@section('content')
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-8">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="w-full">
                <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-teal-900 tracking-tight break-words">Manajemen Pengguna</h2>
                <p class="text-on-surface-variant font-body mt-1 max-w-xl text-sm sm:text-base">Kelola akun pegawai dan staf yang dapat mengakses sistem ini.</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <section class="lg:col-span-4 bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
                <div class="px-6 py-5 bg-surface-container-low border-b border-gray-100">
                    <h3 class="text-lg font-bold text-primary flex items-center">
                        <span class="material-symbols-outlined mr-2 text-primary flex-shrink-0">person_add</span> Tambah Pengguna
                    </h3>
                </div>
                @if(auth()->user()->isAdmin())
                <form action="{{ route('accounts.store') }}" method="POST" class="p-6 space-y-5">
                    @csrf
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nama Lengkap</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="name" required type="text"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Alamat Email</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="email" required type="email"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Hak Akses</label>
                        <select class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="role" required>
                            <option value="staff">Staff Biasa</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Kata Sandi</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="password" required type="password" minlength="8"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Konfirmasi Sandi</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="password_confirmation" required type="password" minlength="8"/>
                    </div>
                    <button class="w-full px-6 py-4 rounded-full shadow-lg shadow-teal-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
                            style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
                            type="submit">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>Buat Akun</span>
                    </button>
                </form>
                @else
                <div class="p-6 text-center text-slate-500">
                    <p class="text-sm font-semibold">Hanya admin yang dapat menambah pengguna baru.</p>
                </div>
                @endif
            </section>

            <section class="lg:col-span-8 bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
                <div class="px-6 py-5 bg-surface-container-low border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-teal-900 flex items-center">
                        <span class="material-symbols-outlined mr-2 text-teal-600 flex-shrink-0">group</span> Daftar Pengguna
                    </h3>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-widest">{{ $users->count() }} Akun</span>
                </div>
                <div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
                    <table class="min-w-[800px] w-full text-xs text-left whitespace-nowrap">
                        <thead class="bg-slate-50 text-left text-slate-500 uppercase text-xs tracking-widest">
                            <tr>
                                <th class="px-6 py-4">Nama Lengkap</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $u)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4 font-semibold text-teal-900">{{ $u->name }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $u->email }}</td>
                                <td class="px-6 py-4">
                                    @if($u->isAdmin())
                                        <span class="px-3 py-1 bg-amber-50 text-amber-700 text-xs font-bold rounded-full">ADMIN</span>
                                    @else
                                        <span class="px-3 py-1 bg-teal-50 text-teal-700 text-xs font-bold rounded-full">STAFF</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if($u->id !== auth()->id())
                                        <form action="{{ route('accounts.destroy', $u) }}" method="POST" onsubmit="return confirm('Hapus akun ini secara permanen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-4 py-2 rounded-lg text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 transition-colors flex items-center gap-1" type="submit">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                        @else
                                        <span class="px-4 py-2 text-xs font-semibold text-slate-400">Anda</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection

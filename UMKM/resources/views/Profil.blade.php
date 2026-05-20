@extends('layouts.app')
@section('title', 'Profil Saya')
@section('page_title', 'Profil Saya')

@section('content')
<div class="p-10 max-w-4xl mx-auto space-y-8">
        
        @if (session('success'))
        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-100 px-4 py-3 text-sm font-medium">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <section class="bg-surface-container-lowest rounded-xl shadow-sm border border-slate-100 dark:border-zinc-800/60 overflow-hidden">
                <div class="px-6 py-5 bg-surface-container-low border-b border-slate-100 dark:border-zinc-800/60">
                    <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-200 dark:text-emerald-250 flex items-center">
                        <span class="material-symbols-outlined mr-2 text-emerald-600 dark:text-emerald-400">person</span> Informasi Dasar
                    </h3>
                </div>
                <form action="{{ route('profile.update') }}" method="POST" class="p-6 space-y-5">
                    @csrf
                    @method('PUT')
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nama Lengkap</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="name" value="{{ old('name', $user->name) }}" required type="text"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Alamat Email</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="email" value="{{ old('email', $user->email) }}" required type="email"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Hak Akses</label>
                        <input class="w-full bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-white border-none rounded-lg p-3 text-sm" value="{{ strtoupper($user->role) }}" disabled type="text"/>
                    </div>
                    <button class="w-full px-6 py-3 rounded-full shadow-lg shadow-emerald-900/10 active:scale-95 transition-all flex items-center justify-center" 
                            style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;" 
                            type="submit">
                        <span class="material-symbols-outlined text-sm mr-2">save</span> Simpan Perubahan
                    </button>
                </form>
            </section>

            <section class="bg-surface-container-lowest rounded-xl shadow-sm border border-slate-100 dark:border-zinc-800/60 overflow-hidden h-fit">
                <div class="px-6 py-5 bg-surface-container-low border-b border-slate-100 dark:border-zinc-800/60">
                    <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-200 dark:text-emerald-250 flex items-center">
                        <span class="material-symbols-outlined mr-2 text-emerald-600 dark:text-emerald-400">lock</span> Ganti Kata Sandi
                    </h3>
                </div>
                <form action="{{ route('profile.password') }}" method="POST" class="p-6 space-y-5">
                    @csrf
                    @method('PUT')
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Kata Sandi Saat Ini</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="current_password" required type="password"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Kata Sandi Baru</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="password" required type="password"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Konfirmasi Kata Sandi Baru</label>
                        <input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="password_confirmation" required type="password"/>
                    </div>
                    <button class="w-full px-6 py-3 rounded-full shadow-lg active:scale-95 transition-all flex items-center justify-center" 
                            style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;" 
                            type="submit">
                        <span class="material-symbols-outlined text-sm mr-2">key</span> Perbarui Kata Sandi
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection

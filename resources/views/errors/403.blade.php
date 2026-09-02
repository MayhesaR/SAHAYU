<!DOCTYPE html>
<html lang="id" class="h-full" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Terbatas (403) - SAHAYU</title>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        h1, h2 {
            font-family: 'Manrope', sans-serif;
        }
    </style>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-stone-50 dark:bg-zinc-900 text-stone-850 dark:text-zinc-100 transition-colors duration-200 h-full flex items-center justify-center">
    <div class="flex flex-col items-center justify-center min-h-screen text-center p-6">
        <!-- SVG Graphic -->
        <div class="w-44 h-44 mx-auto mb-6 relative flex items-center justify-center bg-emerald-500/5 dark:bg-emerald-500/10 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <div class="absolute inset-0 rounded-full border-2 border-emerald-500/10 dark:border-emerald-500/20 animate-pulse"></div>
        </div>

        <!-- Text Hierarchy -->
        <div class="text-emerald-500 font-extrabold text-7xl mb-4">403</div>
        <h2 class="text-stone-800 dark:text-white font-bold text-2xl mb-2">Akses Terbatas</h2>
        <p class="text-stone-500 dark:text-zinc-400 max-w-md mx-auto mb-8">
            Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator jika Anda merasa ini adalah kesalahan.
        </p>

        <!-- Action Button -->
        <a href="{{ route('dashboard') }}" class="bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl py-3 px-6 shadow-md shadow-emerald-500/20 font-semibold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-emerald-500/30 active:scale-95">
            Kembali ke Dashboard Utama
        </a>
    </div>
</body>
</html>

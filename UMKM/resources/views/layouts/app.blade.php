<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden max-w-full" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'ArchitectLedger')</title>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- AlpineJS for responsive sidebar toggling -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        html, body { 
            overflow-x: hidden !important; 
            position: relative; 
            max-width: 100%; 
            min-height: 100vh;
        }
        * { box-sizing: border-box; }
        
        /* Premium Background Mesh Glow */
        body { 
            font-family: 'Inter', sans-serif; 
            background: 
                radial-gradient(at 0% 0%, rgba(11, 110, 79, 0.04) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(11, 110, 79, 0.02) 0px, transparent 50%),
                #fafbfa !important;
            background-attachment: fixed !important;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        html.dark body {
            background: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.03) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(16, 185, 129, 0.01) 0px, transparent 50%),
                #09090b !important;
        }
        
        h1, h2, h3, h4, h5, h6 { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        
        /* Glassmorphism Styles */
        .glass-card {
            background: rgba(255, 255, 255, 0.65) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 10px 30px -10px rgba(4, 120, 87, 0.04), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.8) !important;
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        html.dark .glass-card {
            background: rgba(24, 24, 27, 0.75) !important;
            border: 1px solid rgba(63, 63, 70, 0.4) !important;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.03) !important;
        }
        
        .glass-card-dark {
            background: rgba(6, 78, 59, 0.85) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 20px 40px -15px rgba(2, 44, 34, 0.3),
                        inset 0 1px 0px rgba(255, 255, 255, 0.15) !important;
        }
        
        /* Interactive Utilities */
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .hover-lift:hover {
            transform: translateY(-5px) scale(1.01) !important;
            box-shadow: 0 20px 40px -15px rgba(4, 120, 87, 0.1),
                        inset 0 1px 1px rgba(255, 255, 255, 0.8) !important;
        }
        
        .hover-lift-sm {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .hover-lift-sm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px -6px rgba(4, 120, 87, 0.08) !important;
        }
        
        /* Smooth Scrollbars */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(11, 110, 79, 0.15);
            border-radius: 99px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(11, 110, 79, 0.35);
        }

        @yield('styles')
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 dark:bg-zinc-800 text-stone-800 dark:text-white antialiased selection:bg-emerald-500/20 selection:text-emerald-900 dark:selection:text-emerald-200 overflow-x-hidden max-w-full" x-data="{ sidebarOpen: false }">

    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen" 
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm xl:hidden transition-opacity"
         x-transition.opacity
         @click="sidebarOpen = false"></div>

    @include('components.sidebar')

    <main class="min-h-screen flex flex-col min-w-0 transition-all duration-300 xl:pl-[288px]">
        @include('components.topbar')

        <div class="w-full px-6 md:px-8 py-8 space-y-8 flex-1 min-w-0">
            @yield('content')
        </div>
    </main>

    @yield('scripts')
</body>
</html>

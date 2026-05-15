<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden max-w-full">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'ArchitectLedger')</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- AlpineJS for responsive sidebar toggling -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        background: "#f9f9f9",
                        "outline-variant": "#bec9c8",
                        "surface-container-highest": "#e2e3e2",
                        "on-secondary-fixed-variant": "#324b4b",
                        "tertiary-fixed-dim": "#ffb694",
                        "surface-container": "#edeeee",
                        "surface-bright": "#f9f9f9",
                        "on-tertiary": "#ffffff",
                        "on-tertiary-fixed": "#351000",
                        "on-primary-fixed": "#002020",
                        secondary: "#4a6363",
                        "on-secondary-fixed": "#051f20",
                        "surface-container-low": "#f3f4f3",
                        "surface-container-lowest": "#ffffff",
                        "inverse-primary": "#84d4d3",
                        "primary-fixed-dim": "#84d4d3",
                        "tertiary-container": "#8d4e2f",
                        "surface-container-high": "#e7e8e8",
                        "on-surface": "#1a1c1c",
                        "on-error-container": "#93000a",
                        "surface-tint": "#006a6a",
                        "secondary-fixed-dim": "#b1cccb",
                        "secondary-container": "#cce8e7",
                        "error-container": "#ffdad6",
                        primary: "#005050",
                        surface: "#f9f9f9",
                        "on-background": "#1a1c1c",
                        "on-secondary": "#ffffff",
                        "on-tertiary-fixed-variant": "#70371a",
                        "surface-dim": "#d9dada",
                        "primary-container": "#006a6a",
                        "inverse-on-surface": "#f0f1f0",
                        "tertiary-fixed": "#ffdbcc",
                        outline: "#6e7979",
                        "inverse-surface": "#2e3131",
                        "on-primary-fixed-variant": "#004f4f",
                        "surface-variant": "#e2e3e2",
                        "on-tertiary-container": "#ffcfba",
                        "on-surface-variant": "#3e4948",
                        "on-secondary-container": "#506969",
                        error: "#ba1a1a",
                        "primary-fixed": "#a0f0f0",
                        "on-error": "#ffffff",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#97e7e6",
                        "secondary-fixed": "#cce8e7",
                        tertiary: "#70371a"
                    },
                    borderRadius: {
                        DEFAULT: "0.125rem",
                        lg: "0.25rem",
                        xl: "0.5rem",
                        full: "0.75rem"
                    },
                    fontFamily: {
                        headline: ["Manrope"],
                        body: ["Inter"],
                        label: ["Inter"]
                    }
                },
            }
        }
    </script>
    <style>
        html, body { overflow-x: hidden !important; position: relative; max-width: 100%; }
        * { box-sizing: border-box; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Manrope', sans-serif; }
        @yield('styles')
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-on-surface antialiased selection:bg-primary-container selection:text-on-primary-container overflow-x-hidden max-w-full" x-data="{ sidebarOpen: false }">

    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen" 
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden transition-opacity"
         x-transition.opacity
         @click="sidebarOpen = false"></div>

    @include('components.sidebar')

    <!-- Main Content expands when sidebar is hidden on small screens -->
    <main class="ml-0 lg:ml-64 min-h-screen flex flex-col w-full min-w-0 lg:w-auto transition-all duration-300">
        @include('components.topbar')

        <div class="w-full px-4 md:px-8 pt-20 pb-8 mx-auto space-y-8 flex-1 max-w-full">
            @yield('content')
        </div>
    </main>

    @yield('scripts')
</body>
</html>

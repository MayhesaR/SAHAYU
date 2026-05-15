<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Login | ArchitectLedger</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "background": "#f9f9f9",
                        "outline-variant": "#bec9c8",
                        "surface-container-highest": "#e2e3e2",
                        "on-secondary-fixed-variant": "#324b4b",
                        "tertiary-fixed-dim": "#ffb694",
                        "surface-container": "#edeeee",
                        "surface-bright": "#f9f9f9",
                        "on-tertiary": "#ffffff",
                        "on-tertiary-fixed": "#351000",
                        "on-primary-fixed": "#002020",
                        "secondary": "#4a6363",
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
                        "primary": "#005050",
                        "surface": "#f9f9f9",
                        "on-background": "#1a1c1c",
                        "on-secondary": "#ffffff",
                        "on-tertiary-fixed-variant": "#70371a",
                        "surface-dim": "#d9dada",
                        "primary-container": "#006a6a",
                        "inverse-on-surface": "#f0f1f0",
                        "tertiary-fixed": "#ffdbcc",
                        "outline": "#6e7979",
                        "inverse-surface": "#2e3131",
                        "on-primary-fixed-variant": "#004f4f",
                        "surface-variant": "#e2e3e2",
                        "on-tertiary-container": "#ffcfba",
                        "on-surface-variant": "#3e4948",
                        "on-secondary-container": "#506969",
                        "error": "#ba1a1a",
                        "primary-fixed": "#a0f0f0",
                        "on-error": "#ffffff",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#97e7e6",
                        "secondary-fixed": "#cce8e7",
                        "tertiary": "#70371a"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    }
                },
            },
        }
    </script>
<style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .editorial-shadow {
            box-shadow: 0 12px 40px rgba(0, 80, 80, 0.06);
        }
        .primary-gradient {
            background: linear-gradient(135deg, #005050 0%, #006a6a 100%);
        }
    </style>
</head>
<body class="bg-background text-on-surface min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
<div class="absolute inset-0 z-0 opacity-40 pointer-events-none">
<div class="absolute top-[-10%] left-[-5%] w-[40%] h-[40%] rounded-full bg-secondary-container blur-[120px]"></div>
<div class="absolute bottom-[-10%] right-[-5%] w-[30%] h-[30%] rounded-full bg-primary-fixed-dim blur-[120px]"></div>
</div>
<main class="relative z-10 w-full max-w-md">
<div class="flex flex-col items-center mb-10 space-y-2">
<div class="p-3 bg-surface-container-lowest rounded-full editorial-shadow mb-4">
<span class="material-symbols-outlined text-primary text-4xl" data-icon="architecture">architecture</span>
</div>
<h1 class="text-3xl font-extrabold tracking-tight text-primary">Ledger</h1>
<p class="text-on-surface-variant font-medium tracking-wide font-headline text-sm uppercase opacity-70">SAHAYU</p>
</div>
<div class="bg-surface-container-lowest p-8 md:p-10 rounded-xl editorial-shadow">
<header class="mb-8">
<h2 class="text-2xl font-bold text-on-surface mb-2">Welcome Back</h2>
<p class="text-on-surface-variant text-sm leading-relaxed">Enter your credentials to access your architectural ledger and business workspace.</p>
</header>
<form action="{{ route('login') }}" class="space-y-6" method="POST">
@csrf
<div class="space-y-2">
<label class="block text-sm font-semibold text-on-surface-variant ml-1" for="email">Email Address</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline transition-colors group-focus-within:text-primary" data-icon="mail">mail</span>
<input class="w-full pl-12 pr-4 py-3.5 bg-surface-container-highest border-none rounded-xl focus:ring-2 focus:ring-primary/20 focus:bg-surface-container-lowest transition-all text-on-surface placeholder:text-outline/60" id="email" name="email" placeholder="name@business.com" required="" type="email" value="{{ old('email') }}"/>
</div>
@error('email')
<p class="text-error text-xs font-semibold ml-1">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<div class="flex justify-between items-center ml-1">
<label class="block text-sm font-semibold text-on-surface-variant" for="password">Password</label>
<a class="text-xs font-semibold text-primary hover:text-primary-container transition-colors" href="#">Forgot Password?</a>
</div>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline transition-colors group-focus-within:text-primary" data-icon="lock">lock</span>
<input class="w-full pl-12 pr-12 py-3.5 bg-surface-container-highest border-none rounded-xl focus:ring-2 focus:ring-primary/20 focus:bg-surface-container-lowest transition-all text-on-surface placeholder:text-outline/60" id="password" name="password" placeholder="••••••••" required="" type="password"/>
<button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors" type="button">
<span class="material-symbols-outlined text-xl" data-icon="visibility">visibility</span>
</button>
</div>
</div>
<div class="flex items-center space-x-3 ml-1">
<div class="relative flex items-center">
<input class="w-5 h-5 rounded-md border-outline-variant text-primary focus:ring-primary/20 bg-surface-container-low transition-all" id="remember" name="remember" type="checkbox"/>
</div>
<label class="text-sm font-medium text-on-surface-variant cursor-pointer select-none" for="remember">Keep me signed in for 30 days</label>
</div>
<div class="pt-4">
<button class="w-full bg-[#005050] text-white py-4 px-6 rounded-xl font-black tracking-wide shadow-lg shadow-teal-900/30 hover:bg-[#006a6a] hover:shadow-xl active:scale-[0.98] transition-all flex items-center justify-center space-x-2" type="submit">
<span>Sign In to Dashboard</span>
<span class="material-symbols-outlined text-lg" data-icon="arrow_forward">arrow_forward</span>
</button>
</div>
</form>
<footer class="mt-10 pt-8 border-t border-surface-container-low flex flex-col items-center">
<p class="text-on-surface-variant text-sm mb-4">Don't have an account yet?</p>
<a href="{{ route('register') }}" class="w-full py-3.5 px-6 rounded-xl bg-surface-container-highest text-on-surface font-semibold hover:bg-surface-container-high active:scale-[0.98] transition-all text-center">
                    Create SAHAYU Workspace
                </a>
</footer>
</div>
<div class="mt-8 flex justify-center space-x-6">
<a class="text-xs font-semibold text-outline hover:text-primary transition-colors uppercase tracking-widest" href="#">Privacy Policy</a>
<span class="w-1 h-1 bg-outline-variant rounded-full mt-1.5 opacity-30"></span>
<a class="text-xs font-semibold text-outline hover:text-primary transition-colors uppercase tracking-widest" href="#">Terms of Service</a>
<span class="w-1 h-1 bg-outline-variant rounded-full mt-1.5 opacity-30"></span>
<a class="text-xs font-semibold text-outline hover:text-primary transition-colors uppercase tracking-widest" href="#">Support</a>
</div>
</main>
<div class="fixed bottom-8 right-8 hidden lg:block opacity-20 hover:opacity-100 transition-opacity">

</div>
</body></html>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Register | SAHAYU</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "background": "#f9f9f9",
                        "primary": "#005050",
                        "on-surface": "#1a1c1c",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-highest": "#e2e3e2",
                        "outline": "#6e7979",
                        "on-surface-variant": "#3e4948",
                        "error": "#ba1a1a"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Manrope', sans-serif; }
        .editorial-shadow {
            box-shadow: 0 12px 40px rgba(0, 80, 80, 0.06);
        }
    </style>
</head>
<body class="bg-background text-on-surface min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <div class="absolute inset-0 z-0 opacity-40 pointer-events-none">
        <div class="absolute top-[-10%] left-[-5%] w-[40%] h-[40%] rounded-full bg-teal-100 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-[30%] h-[30%] rounded-full bg-teal-200 blur-[120px]"></div>
    </div>
    <main class="relative z-10 w-full max-w-md">
        <div class="flex flex-col items-center mb-10 space-y-2">
            <div class="p-3 bg-surface-container-lowest rounded-full editorial-shadow mb-4">
                <span class="material-symbols-outlined text-primary text-4xl">architecture</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-primary">Ledger</h1>
            <p class="text-on-surface-variant font-medium tracking-wide font-headline text-sm uppercase opacity-70">SAHAYU</p>
        </div>
        <div class="bg-surface-container-lowest p-8 md:p-10 rounded-xl editorial-shadow">
            <header class="mb-8">
                <h2 class="text-2xl font-bold text-on-surface mb-2">Create Account</h2>
                <p class="text-on-surface-variant text-sm leading-relaxed">Join SAHAYU to manage your architectural and business records.</p>
            </header>
            <form action="{{ route('register') }}" class="space-y-4" method="POST">
                @csrf
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant ml-1 uppercase" for="name">Full Name</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline text-sm" data-icon="person">person</span>
                        <input class="w-full pl-10 pr-4 py-3 bg-surface-container-highest border-none rounded-xl focus:ring-2 focus:ring-primary/20 transition-all text-sm" id="name" name="name" placeholder="John Doe" required type="text" value="{{ old('name') }}"/>
                    </div>
                    @error('name')
                        <p class="text-error text-xs font-semibold ml-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant ml-1 uppercase" for="email">Email Address</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline text-sm" data-icon="mail">mail</span>
                        <input class="w-full pl-10 pr-4 py-3 bg-surface-container-highest border-none rounded-xl focus:ring-2 focus:ring-primary/20 transition-all text-sm" id="email" name="email" placeholder="name@business.com" required type="email" value="{{ old('email') }}"/>
                    </div>
                    @error('email')
                        <p class="text-error text-xs font-semibold ml-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant ml-1 uppercase" for="password">Password</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline text-sm" data-icon="lock">lock</span>
                        <input class="w-full pl-10 pr-4 py-3 bg-surface-container-highest border-none rounded-xl focus:ring-2 focus:ring-primary/20 transition-all text-sm" id="password" name="password" placeholder="••••••••" required type="password"/>
                    </div>
                    @error('password')
                        <p class="text-error text-xs font-semibold ml-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-on-surface-variant ml-1 uppercase" for="password_confirmation">Confirm Password</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline text-sm" data-icon="lock_reset">lock_reset</span>
                        <input class="w-full pl-10 pr-4 py-3 bg-surface-container-highest border-none rounded-xl focus:ring-2 focus:ring-primary/20 transition-all text-sm" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required type="password"/>
                    </div>
                </div>
                <div class="pt-4">
                    <button class="w-full bg-[#005050] text-white py-4 px-6 rounded-xl font-black tracking-wide shadow-lg shadow-teal-900/30 hover:bg-[#006a6a] hover:shadow-xl active:scale-[0.98] transition-all flex items-center justify-center space-x-2" type="submit">
                        <span>Register Now</span>
                        <span class="material-symbols-outlined text-lg">app_registration</span>
                    </button>
                </div>
            </form>
            <footer class="mt-8 pt-6 border-t border-surface-container-low text-center">
                <p class="text-on-surface-variant text-sm">Already have an account? 
                    <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">Login here</a>
                </p>
            </footer>
        </div>
    </main>
</body>
</html>

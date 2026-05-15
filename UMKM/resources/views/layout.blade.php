<!DOCTYPE html>
<html>
<head>
    <title>SAHAYU</title>
    @vite('resources/css/app.css')
</head>
<body class="flex bg-gray-100">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg h-screen">
        <div class="p-4 font-bold">SAHAYU</div>
        <nav class="space-y-2">
            <a href="{{ route('dashboard') }}" class="block p-2">Dashboard</a>
            <a href="{{ route('materials.index') }}" class="block p-2">Bahan Baku</a>
            <a href="{{ route('products.index') }}" class="block p-2">Produk Jadi</a>
            <a href="{{ route('productions.index') }}" class="block p-2">Produksi</a>
            <a href="{{ route('sales.index') }}" class="block p-2">Penjualan</a>
            <a href="{{ route('reports.index') }}" class="block p-2">Laporan</a>
        </nav>
    </aside>

    <!-- Content -->
    <main class="flex-1 p-6">
        @yield('content')
    </main>

</body>
</html>

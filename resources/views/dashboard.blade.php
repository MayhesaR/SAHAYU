@extends('layout')

@section('content')

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-zinc-900 p-4 rounded shadow">
        <h4>Total Penjualan</h4>
        <p class="text-xl font-bold">Rp {{ number_format($totalSales) }}</p>
    </div>

    <div class="bg-white dark:bg-zinc-900 p-4 rounded shadow">
        <h4>Total Produksi</h4>
        <p class="text-xl font-bold">{{ $totalProduction }} Unit</p>
    </div>

    <div class="bg-white dark:bg-zinc-900 p-4 rounded shadow">
        <h4>Stok Aman</h4>
        <p class="text-xl font-bold">{{ $lowStock }} Warning</p>
    </div>
</div>

<canvas id="salesChart"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($salesChart->pluck('date')) !!},
        datasets: [{
            label: 'Sales',
            data: {!! json_encode($salesChart->pluck('total')) !!}
        }]
    }
});
</script>

@endsection

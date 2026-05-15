<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Finansial</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f766e; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #0f766e; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f1f5f9; color: #0f766e; font-size: 10px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .summary-box { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; background: #fafafa; }
        .summary-box h3 { margin-top: 0; color: #0f766e; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UMKM PANCASILA</h1>
        <p>Laporan Finansial & Pertumbuhan</p>
    </div>

    <div class="summary-box">
        <h3>Ringkasan Keseluruhan</h3>
        <p><strong>Total Pendapatan:</strong> Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        <p><strong>Total Pengeluaran (HPP + Overhead):</strong> Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
        <p><strong>Laba Bersih:</strong> Rp {{ number_format($netProfit, 0, ',', '.') }}</p>
    </div>

    <h3>Rincian Pertumbuhan Bulanan</h3>
    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th class="text-right">Target Penjualan</th>
                <th class="text-right">Realisasi</th>
                <th class="text-right">Pertumbuhan (%)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyGrowth as $row)
            <tr>
                <td>{{ $row['month'] }}</td>
                <td class="text-right">Rp {{ number_format($row['target'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($row['realization'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($row['growth'], 2, ',', '.') }}%</td>
                <td>{{ $row['status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 10px; color: #999;">
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }}
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Produksi</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f766e; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #0f766e; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f1f5f9; color: #0f766e; font-size: 9px; text-transform: uppercase; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UMKM PANCASILA</h1>
        <p>Riwayat Batch Produksi</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Batch</th>
                <th>Produk</th>
                <th>Tanggal</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Good</th>
                <th class="text-right">Reject</th>
                <th>Status</th>
                <th class="text-right">Total Biaya</th>
                <th class="text-right">HPP/Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productions as $index => $p)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $p->batch_code }}</td>
                <td>{{ $p->product ? $p->product->name : '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($p->production_date)->format('d M Y') }}</td>
                <td class="text-right">{{ $p->quantity }}</td>
                <td class="text-right">{{ $p->good_quantity }}</td>
                <td class="text-right">{{ $p->reject_quantity }}</td>
                <td>{{ strtoupper($p->status) }}</td>
                <td class="text-right">Rp {{ number_format($p->total_cost_snapshot, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($p->unit_hpp_snapshot, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 9px; color: #999;">
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }}
    </div>
</body>
</html>

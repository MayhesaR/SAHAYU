<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f766e; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #0f766e; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f1f5f9; color: #0f766e; font-size: 10px; text-transform: uppercase; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header" style="text-align: left; min-height: 55px;">
        @if(auth()->user()->company && auth()->user()->company->logo)
            <img src="{{ public_path('storage/' . auth()->user()->company->logo) }}" style="float: left; max-height: 48px; max-width: 120px; margin-right: 15px; margin-bottom: 5px;">
        @endif
        <div style="float: left;">
            <h1 style="margin: 0; color: #0f766e; font-size: 20px; text-transform: uppercase;">{{ auth()->user()->company->name ?? 'SAHAYU UMKM' }}</h1>
            <p style="margin: 3px 0 0; color: #666; font-size: 11px;">Riwayat Penjualan Harian</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Metode Bayar</th>
                <th>Produk & Jumlah</th>
                <th class="text-right">Total Transaksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $index => $s)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $s->created_at->format('d M Y, H:i') }}</td>
                <td>{{ $s->customer ?: 'Umum' }}</td>
                <td>{{ strtoupper($s->payment_method) }}</td>
                <td>
                    @foreach($s->items as $item)
                        {{ $item->product ? $item->product->name : '-' }} ({{ $item->quantity }} Pcs)<br>
                    @endforeach
                </td>
                <td class="text-right">Rp {{ number_format($s->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 9px; color: #999;">
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }}
    </div>
</body>
</html>

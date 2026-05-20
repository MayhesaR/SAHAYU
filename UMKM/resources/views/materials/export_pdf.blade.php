<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Bahan Baku</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #0f766e;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        .info {
            margin-bottom: 20px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #0f766e;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .critical {
            color: #dc2626;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>

    <div class="header" style="text-align: left; min-height: 55px;">
        @if(auth()->user()->company && auth()->user()->company->logo)
            <img src="{{ public_path('storage/' . auth()->user()->company->logo) }}" style="float: left; max-height: 48px; max-width: 120px; margin-right: 15px; margin-bottom: 5px;">
        @endif
        <div style="float: left;">
            <h1 style="margin: 0; color: #0f766e; font-size: 20px; text-transform: uppercase;">{{ auth()->user()->company->name ?? 'SAHAYU UMKM' }}</h1>
            <p style="margin: 3px 0 0; color: #666; font-size: 12px;">Laporan Inventaris Bahan Baku</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="info">
        <table style="border: none; margin-bottom: 0;">
            <tr>
                <td style="border: none; padding: 0;"><strong>Tanggal Cetak:</strong> {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }}</td>
                <td style="border: none; padding: 0;" class="text-right"><strong>Total Valuasi:</strong> Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Nama Material</th>
                <th width="15%">Kategori</th>
                <th width="15%" class="text-right">Stok Aktual</th>
                <th width="10%" class="text-right">Min. Stok</th>
                <th width="15%" class="text-right">Harga Satuan</th>
                <th width="15%">Supplier</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materials as $index => $material)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $material->name }}</strong></td>
                <td>{{ $material->category }}</td>
                <td class="text-right {{ $material->stock <= $material->minimum_stock ? 'critical' : '' }}">
                    {{ number_format($material->stock, 0, ',', '.') }} {{ $material->unit }}
                    @if($material->stock <= $material->minimum_stock)
                        <br><span style="font-size: 9px;">(Restock!)</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format($material->minimum_stock, 0, ',', '.') }} {{ $material->unit }}</td>
                <td class="text-right">Rp {{ number_format($material->price, 0, ',', '.') }}</td>
                <td>{{ $material->default_supplier ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak oleh Sistem MSME Manager &copy; {{ date('Y') }}
    </div>

</body>
</html>

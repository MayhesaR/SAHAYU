<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Belanja #{{ $sale->id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #ffffff;
            color: #000000;
            margin: 0;
            padding: 10px;
            font-size: 12px;
            line-height: 1.4;
        }

        .receipt-container {
            width: 100%;
            max-width: 80mm; /* Suitable for 58mm/80mm paper widths */
            margin: 0 auto;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .dashed-line {
            border-top: 1px dashed #000000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        
        <!-- Header -->
        <div class="center">
            <span class="bold" style="font-size: 16px;">{{ strtoupper($company->name ?? 'SAHAYU BAKERY') }}</span><br>
            <span>UMKM Mitra Pancasila</span><br>
            <span>Telp: 081234567890</span><br>
            <div class="dashed-line"></div>
            <span>Nota: #{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</span><br>
            <span>Tanggal: {{ $sale->created_at->format('d/m/Y H:i') }}</span><br>
            <span>Kasir: {{ auth()->user()->name }}</span>
        </div>

        <div class="dashed-line"></div>

        <!-- Customer info -->
        <div>
            <span>Pelanggan: {{ $sale->customer ?: 'Walk-in' }}</span>
        </div>

        <div class="dashed-line"></div>

        <!-- Items Table -->
        <table>
            @foreach($sale->items as $item)
                <tr>
                    <td colspan="2" class="bold">{{ $item->product ? $item->product->name : 'Produk Jadi' }}</td>
                </tr>
                <tr>
                    <td>{{ $item->quantity }} pcs x Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <div class="dashed-line"></div>

        <!-- Summary -->
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Diskon</td>
                <td class="right">Rp 0</td>
            </tr>
            <tr class="bold">
                <td>Total Tagihan</td>
                <td class="right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="dashed-line" colspan="2"></td>
            </tr>
            <tr>
                <td>Metode Bayar</td>
                <td class="right bold">{{ strtoupper($sale->payment_method) }}</td>
            </tr>
            <tr>
                <td>Status Bayar</td>
                <td class="right bold">
                    {{ $sale->status === 'paid' ? 'LUNAS' : 'PIUTANG/BELUM LUNAS' }}
                </td>
            </tr>
        </table>

        <div class="dashed-line"></div>

        <!-- Footer -->
        <div class="center footer">
            <span class="bold">TERIMA KASIH ATAS KUNJUNGAN ANDA</span><br>
            <span>Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.</span><br>
            <span>SAHAYU - Luxury POS System</span>
        </div>

    </div>

    <!-- Auto Print Script -->
    <script>
        window.onload = function() {
            window.print();
            // Automatically close receipt print tab if needed
            // window.onafterprint = function() { window.close(); }
        }
    </script>
</body>
</html>

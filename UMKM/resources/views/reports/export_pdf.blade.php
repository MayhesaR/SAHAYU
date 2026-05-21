<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Performa Bisnis</title>
    <style>
        @page {
            margin: 1.2cm 1.2cm 1.2cm 1.2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-container {
            border-bottom: 3px double #0f766e;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .shop-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f766e;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
            margin: 5px 0 0 0;
        }
        .report-period {
            font-size: 10px;
            color: #64748b;
            margin: 3px 0 0 0;
            font-style: italic;
        }
        .print-date {
            float: right;
            text-align: right;
            font-size: 8px;
            color: #94a3b8;
            margin-top: -40px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f766e;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin: 18px 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Bento Grid Style Metrics Table */
        .metrics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 0 -10px 15px -10px;
        }
        .metrics-cell {
            width: 33.33%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            vertical-align: top;
        }
        .metric-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
            letter-spacing: 0.3px;
        }
        .metric-desc {
            font-size: 7px;
            color: #94a3b8;
            margin-bottom: 6px;
            line-height: 1.2;
        }
        .metric-value {
            font-size: 14px;
            font-weight: 800;
            color: #0f766e;
        }
        
        /* Standard Data Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f766e;
            text-align: left;
        }
        table.data-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 9px;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }
        
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-success { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-warning { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-danger { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        
        .footer-note {
            margin-top: 10px;
            font-size: 7px;
            color: #64748b;
            background-color: #f1f5f9;
            padding: 6px 10px;
            border-radius: 4px;
            border-left: 3px solid #0f766e;
        }
        
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 7px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header-container" style="min-height: 55px; width: 100%;">
        @if(auth()->user()->company && auth()->user()->company->logo)
            <img src="{{ public_path('storage/' . auth()->user()->company->logo) }}" style="float: left; max-height: 48px; max-width: 120px; margin-right: 15px; margin-bottom: 8px;">
        @endif
        <div style="float: left;">
            <h1 class="shop-name">{{ auth()->user()->company->name ?? 'SAHAYU UMKM' }}</h1>
            <div class="report-title">Laporan Performa Bisnis (Accrual Basis)</div>
            <div class="report-period">Periode Laporan: {{ $periodLabel }}</div>
        </div>
        
        <div class="print-date">
            Dicetak oleh: {{ auth()->user()->name }}<br>
            Waktu Cetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }}
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- SECTION 1 (Ringkasan Finansial) -->
    <div class="section-title">I. Ringkasan Finansial</div>
    <table class="metrics-table">
        <tr>
            <td class="metrics-cell">
                <div class="metric-label">Nilai Barang Terjual</div>
                <div class="metric-desc">Total omzet dari barang yang laku pada periode terpilih, baik tunai maupun utang.</div>
                <div class="metric-value">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            </td>
            <td class="metrics-cell">
                <div class="metric-label">Total Modal &amp; Operasional</div>
                <div class="metric-desc">Nilai modal dari barang yang laku ditambah total biaya operasional periode terpilih.</div>
                <div class="metric-value">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
            </td>
            <td class="metrics-cell">
                <div class="metric-label">Margin Laba Penjualan</div>
                <div class="metric-desc">Perkiraan keuntungan bersih &amp; rasio margin performa pada periode terpilih.</div>
                <div class="metric-value">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                    <span style="font-size: 9px; font-weight: normal; color: #64748b; block">({{ number_format($profitMargin, 1) }}%)</span>
                </div>
            </td>
        </tr>
    </table>
    
    <div class="footer-note">
        <strong>Catatan Penting:</strong> Angka di atas mencerminkan nilai performa barang terjual (Accrual Basis), BUKAN jumlah uang kas fisik di laci kasir. Untuk melihat pergerakan/mutasi uang kas riil (Cash Basis), silakan rujuk menu Dashboard Utama.
    </div>

    <!-- 2 Column Layout for HPP Breakdown & Top Products -->
    <table style="width: 100%; border: none; margin-top: 15px; border-collapse: collapse;">
        <tr>
            <!-- Left Column: Struktur Biaya HPP -->
            <td style="width: 48%; border: none; padding-right: 2%; vertical-align: top;">
                <div class="section-title">II. Struktur Biaya Modal (HPP)</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Komponen Biaya</th>
                            <th class="text-right">Estimasi Porsi (%)</th>
                            <th class="text-right">Nilai Rupiah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalHPP = array_sum($expenseBreakdown);
                        @endphp
                        @foreach($expenseBreakdown as $komponen => $nilai)
                        <tr>
                            <td class="font-bold">{{ $komponen }}</td>
                            <td class="text-right">
                                @if($totalHPP > 0)
                                    {{ number_format(($nilai / $totalHPP) * 100, 0) }}%
                                @else
                                    0%
                                @endif
                            </td>
                            <td class="text-right">Rp {{ number_format($nilai, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #f1f5f9; font-weight: bold;">
                            <td>Total HPP (Modal Pokok)</td>
                            <td class="text-right">100%</td>
                            <td class="text-right">Rp {{ number_format($totalHPP, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            
            <!-- Right Column: Produk Terpopuler -->
            <td style="width: 50%; border: none; vertical-align: top;">
                <div class="section-title">III. Produk Terpopuler</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th class="text-right">Volume Terjual</th>
                            <th class="text-right">Total Nilai (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($popularProducts as $product)
                        <tr>
                            <td class="font-bold">{{ $product->name }}</td>
                            <td class="text-right">{{ number_format($product->total_qty, 0, ',', '.') }} Unit</td>
                            <td class="text-right" style="color: #0f766e; font-weight: bold;">Rp {{ number_format($product->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center" style="color: #64748b; font-style: italic;">Belum ada data produk terpopuler pada periode terpilih.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- SECTION 4 (Rincian Performa Tren) -->
    <div class="section-title">IV. Rincian Performa &amp; Tren (Granularitas: {{ $activePeriod === 'harian' ? 'Per 4 Jam' : 'Berkala' }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Waktu / Interval</th>
                <th class="text-right">Target Penjualan</th>
                <th class="text-right">Realisasi Penjualan</th>
                <th class="text-right">Rasio Capaian</th>
                <th class="text-center">Status Evaluasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse(array_reverse($trendData) as $row)
            <tr>
                <td class="font-bold">{{ $row['label'] }}</td>
                <td class="text-right">Rp {{ number_format($row['target'], 0, ',', '.') }}</td>
                <td class="text-right" style="color: #0f766e; font-weight: bold;">Rp {{ number_format($row['realization'], 0, ',', '.') }}</td>
                <td class="text-right font-bold {{ $row['growth'] < 0 ? 'text-danger' : '' }}" style="color: #047857;">
                    {{ $row['growth'] >= 0 ? '+' : '' }}{{ number_format($row['growth'], 1) }}%
                </td>
                <td class="text-center">
                    <span class="badge {{ $row['status'] === 'Exceeded' ? 'badge-success' : ($row['status'] === 'Near Target' ? 'badge-warning' : 'badge-danger') }}">
                        {{ $row['status'] }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="color: #64748b; font-style: italic;">Tidak ada data tren performa untuk periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Laporan Performa Bisnis ini dibuat secara otomatis oleh Sistem Pendukung Keputusan Finansial SAHAYU.<br>
        &copy; {{ date('Y') }} {{ auth()->user()->company->name ?? 'SAHAYU UMKM' }}. Hak Cipta Dilindungi Undang-Undang.
    </div>
</body>
</html>

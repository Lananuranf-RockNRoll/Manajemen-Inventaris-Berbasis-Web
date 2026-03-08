<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Dashboard Inventaris</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            background-color: #ffffff;
            color: #1a1a1a;
        }

        /* ── Header ── */
        .header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 20px 30px;
        }
        .header-table { width: 100%; }
        .header-title {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
        }
        .header-sub {
            font-size: 10px;
            color: #b0c4de;
            margin-top: 4px;
        }
        .header-meta {
            text-align: right;
            font-size: 10px;
            color: #b0c4de;
            line-height: 1.8;
        }

        .divider {
            background-color: #2e86c1;
            height: 4px;
            margin-bottom: 20px;
        }

        /* ── Section ── */
        .section {
            padding: 0 30px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a5f;
            border-left: 4px solid #2e86c1;
            padding-left: 8px;
            margin-bottom: 10px;
        }

        /* ── KPI ── */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .kpi-card {
            background-color: #f0f4f8;
            border: 1px solid #d0dce8;
            border-top: 3px solid #2e86c1;
            padding: 12px;
            text-align: center;
            width: 25%;
        }
        .kpi-card.green  { border-top-color: #27ae60; }
        .kpi-card.orange { border-top-color: #e67e22; }
        .kpi-card.purple { border-top-color: #8e44ad; }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #666666;
            margin-bottom: 6px;
        }
        .kpi-value { font-size: 16px; font-weight: 700; color: #1e3a5f; }
        .kpi-value.small { font-size: 12px; }

        /* ── Status ── */
        .status-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .status-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: center;
            width: 25%;
        }
        .status-num { font-size: 20px; font-weight: 700; }
        .status-label { font-size: 9px; color: #666666; text-transform: uppercase; margin-top: 3px; }
        .color-pending    { color: #e67e22; }
        .color-processing { color: #2e86c1; }
        .color-delivered  { color: #27ae60; }
        .color-canceled   { color: #e74c3c; }

        /* ── Table ── */
        .data-table { width: 100%; border-collapse: collapse; border: 1px solid #d0dce8; }
        .data-table thead tr { background-color: #1e3a5f; color: #ffffff; }
        .data-table thead th {
            padding: 9px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table tbody tr:nth-child(even) { background-color: #f0f4f8; }
        .data-table tbody tr:nth-child(odd)  { background-color: #ffffff; }
        .data-table tbody td {
            padding: 8px 12px;
            font-size: 11px;
            color: #333333;
            border-bottom: 1px solid #e0e8f0;
        }
        .rank { font-weight: 700; color: #1e3a5f; text-align: center; }
        .product-name { font-weight: 600; }
        .product-sku  { font-size: 9px; color: #666666; margin-top: 2px; }
        .revenue-val  { font-weight: 700; color: #27ae60; }
        .text-right   { text-align: right; }
        .text-center  { text-align: center; }

        /* ── Bar ── */
        .bar-bg   { background-color: #dee2e6; height: 8px; width: 100px; }
        .bar-fill { background-color: #2e86c1; height: 8px; }

        /* ── Footer ── */
        .footer { margin-top: 24px; padding: 12px 30px; border-top: 2px solid #1e3a5f; }
        .footer-table { width: 100%; }
        .footer-left  { font-size: 9px; color: #666666; }
        .footer-right { text-align: right; font-size: 9px; color: #666666; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-title">Sistem Informasi Manajemen Inventaris</div>
                    <div class="header-sub">Laporan Dashboard &mdash; Ringkasan Kinerja</div>
                </td>
                <td class="header-meta">
                    <div>Dicetak: {{ now()->format('d F Y, H:i') }} WIB</div>
                    <div>Periode: {{ now()->format('Y') }}</div>
                    <div>lananuranf</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="divider"></div>

    {{-- KPI --}}
    <div class="section">
        <div class="section-title">Indikator Kinerja Utama</div>
        <table class="kpi-table">
            <tr>
                <td class="kpi-card">
                    <div class="kpi-label">Total Revenue</div>
                    <div class="kpi-value small">Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}</div>
                </td>
                <td class="kpi-card green">
                    <div class="kpi-label">Total Order</div>
                    <div class="kpi-value">{{ number_format($summary['total_orders'] ?? 0) }}</div>
                </td>
                <td class="kpi-card orange">
                    <div class="kpi-label">Total Produk</div>
                    <div class="kpi-value">{{ number_format($summary['total_products'] ?? 0) }}</div>
                </td>
                <td class="kpi-card purple">
                    <div class="kpi-label">Total Customer</div>
                    <div class="kpi-value">{{ number_format($summary['total_customers'] ?? 0) }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- STATUS ORDER --}}
    <div class="section">
        <div class="section-title">Status Order</div>
        <table class="status-table">
            <tr>
                <td class="status-card">
                    <div class="status-num color-pending">{{ $summary['pending_orders'] ?? 0 }}</div>
                    <div class="status-label">Pending</div>
                </td>
                <td class="status-card">
                    <div class="status-num color-processing">{{ $summary['shipped_orders'] ?? 0 }}</div>
                    <div class="status-label">Shipped</div>
                </td>
                <td class="status-card">
                    <div class="status-num color-delivered">{{ $summary['total_orders'] ?? 0 }}</div>
                    <div class="status-label">Total Order</div>
                </td>
                <td class="status-card">
                    <div class="status-num color-canceled">{{ $summary['canceled_orders'] ?? 0 }}</div>
                    <div class="status-label">Canceled</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- TOP 5 PRODUK --}}
    <div class="section">
        <div class="section-title">Top 5 Produk Terlaris</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width:36px">#</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-right">Qty Terjual</th>
                    <th class="text-right">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topProducts as $i => $p)
                <tr>
                    <td class="rank text-center">{{ $i + 1 }}</td>
                    <td>
                        <div class="product-name">{{ $p['name'] }}</div>
                        <div class="product-sku">{{ $p['sku'] }}</div>
                    </td>
                    <td style="font-size:10px; color:#555555">{{ $p['category_name'] ?? '-' }}</td>
                    <td class="text-right" style="font-weight:600">{{ number_format($p['total_qty']) }} unit</td>
                    <td class="text-right revenue-val">Rp {{ number_format($p['total_revenue'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="color:#999999; padding:16px">Belum ada data transaksi</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PENJUALAN PER BULAN --}}
    <div class="section">
        <div class="section-title">Ringkasan Penjualan Per Bulan ({{ now()->year }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:80px">Bulan</th>
                    <th class="text-right">Jumlah Order</th>
                    <th class="text-right">Total Revenue</th>
                    <th style="width:120px">Proporsi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    $maxRev = collect($monthlySales)->max('revenue') ?: 1;
                @endphp
                @foreach ($monthlySales as $row)
                <tr>
                    <td style="font-weight:600">{{ $months[($row['month'] ?? 1) - 1] }}</td>
                    <td class="text-right">{{ number_format($row['orders']) }}</td>
                    <td class="text-right" style="color:#27ae60; font-weight:600">
                        Rp {{ number_format($row['revenue'] / 1000000, 1) }}jt
                    </td>
                    <td>
                        <div class="bar-bg">
                            <div class="bar-fill" style="width: {{ round(($row['revenue'] / $maxRev) * 100) }}px"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
                @if (empty($monthlySales))
                <tr>
                    <td colspan="4" class="text-center" style="color:#999999; padding:16px">Belum ada data penjualan</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">Sistem Informasi Manajemen Inventaris</td>
                <td class="footer-right">Laporan otomatis &mdash; {{ now()->format('d/m/Y H:i') }} WIB</td>
            </tr>
        </table>
    </div>

</body>
</html>

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
            background-color: #09090b;
            color: #e4e4e7;
        }

        /* ── Cover Header ── */
        .cover {
            background: linear-gradient(135deg, #1e1b4b 0%, #18181b 50%, #1e1b4b 100%);
            border-bottom: 3px solid #4f46e5;
            padding: 32px 40px 24px;
            margin-bottom: 24px;
        }
        .cover-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .logo {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
        }
        .logo span { color: #818cf8; }
        .cover-meta {
            text-align: right;
            font-size: 10px;
            color: #a1a1aa;
            line-height: 1.8;
        }
        .cover-title {
            margin-top: 20px;
        }
        .cover-title h1 {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
        }
        .cover-title p {
            font-size: 11px;
            color: #818cf8;
            margin-top: 4px;
        }

        /* ── Section Title ── */
        .section {
            padding: 0 40px;
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #6366f1;
            border-left: 3px solid #6366f1;
            padding-left: 10px;
            margin-bottom: 12px;
        }

        /* ── KPI Cards ── */
        .kpi-grid {
            display: flex;
            gap: 12px;
            margin-bottom: 0;
        }
        .kpi-card {
            flex: 1;
            background-color: #18181b;
            border: 1px solid #3f3f46;
            border-radius: 10px;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .kpi-card.indigo::before { background-color: #6366f1; }
        .kpi-card.emerald::before { background-color: #10b981; }
        .kpi-card.amber::before { background-color: #f59e0b; }
        .kpi-card.violet::before { background-color: #8b5cf6; }
        .kpi-card.blue::before { background-color: #3b82f6; }
        .kpi-card.red::before { background-color: #ef4444; }

        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #71717a;
            margin-bottom: 6px;
        }
        .kpi-value {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1;
        }
        .kpi-value.small { font-size: 14px; }
        .kpi-sub {
            font-size: 9px;
            color: #52525b;
            margin-top: 4px;
        }

        /* ── Order Status Row ── */
        .status-row {
            display: flex;
            gap: 8px;
        }
        .status-card {
            flex: 1;
            background-color: #18181b;
            border: 1px solid #3f3f46;
            border-radius: 8px;
            padding: 10px 12px;
            text-align: center;
        }
        .status-num {
            font-size: 18px;
            font-weight: 700;
        }
        .status-label {
            font-size: 9px;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }
        .color-pending    { color: #f59e0b; }
        .color-processing { color: #3b82f6; }
        .color-shipped    { color: #8b5cf6; }
        .color-delivered  { color: #10b981; }
        .color-canceled   { color: #ef4444; }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            background-color: #27272a;
        }
        thead th {
            padding: 9px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #3f3f46;
        }
        tbody tr {
            border-bottom: 1px solid #27272a;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody td {
            padding: 9px 12px;
            font-size: 11px;
            color: #d4d4d8;
        }
        .rank {
            font-size: 10px;
            font-weight: 700;
            color: #6366f1;
            width: 28px;
            text-align: center;
        }
        .product-name { font-weight: 600; color: #ffffff; }
        .product-sku  { font-size: 9px; font-family: monospace; color: #818cf8; margin-top: 2px; }
        .revenue-val  { font-weight: 700; color: #10b981; }
        .text-right   { text-align: right; }
        .text-center  { text-align: center; }

        /* ── Low Stock ── */
        .low-stock-table tbody td { color: #d4d4d8; }
        .badge-habis { background-color: #450a0a; color: #fca5a5; padding: 2px 7px; border-radius: 4px; font-size: 9px; font-weight: 700; }
        .badge-rendah { background-color: #451a03; color: #fcd34d; padding: 2px 7px; border-radius: 4px; font-size: 9px; font-weight: 700; }

        /* ── Monthly Sales Table ── */
        .bar-cell { width: 120px; }
        .bar-bg {
            background-color: #27272a;
            border-radius: 3px;
            height: 10px;
            overflow: hidden;
        }
        .bar-fill {
            background: linear-gradient(90deg, #4f46e5, #818cf8);
            height: 10px;
            border-radius: 3px;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 32px;
            padding: 16px 40px;
            border-top: 1px solid #27272a;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #52525b;
        }

        /* ── Two column layout ── */
        .two-col {
            display: flex;
            gap: 16px;
        }
        .two-col > div { flex: 1; }

        .table-wrapper {
            background-color: #18181b;
            border: 1px solid #3f3f46;
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>

    {{-- ═══ COVER HEADER ═══ --}}
    <div class="cover">
        <div class="cover-top">
            <div class="logo">Sistem <span>Inventaris</span></div>
            <div class="cover-meta">
                <div>Dicetak: {{ now()->format('d F Y, H:i') }} WIB</div>
                <div>Periode: {{ now()->format('Y') }}</div>
                <div>lananuranf</div>
            </div>
        </div>
        <div class="cover-title">
            <h1>Laporan Dashboard</h1>
            <p>Ringkasan Kinerja Sistem Informasi Manajemen Inventaris</p>
        </div>
    </div>

    {{-- ═══ KPI UTAMA ═══ --}}
    <div class="section">
        <div class="section-title">Indikator Kinerja Utama (KPI)</div>
        <div class="kpi-grid">
            <div class="kpi-card indigo">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value small">Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">Semua transaksi selesai</div>
            </div>
            <div class="kpi-card emerald">
                <div class="kpi-label">Total Order</div>
                <div class="kpi-value">{{ number_format($summary['total_orders'] ?? 0) }}</div>
                <div class="kpi-sub">Seluruh transaksi</div>
            </div>
            <div class="kpi-card amber">
                <div class="kpi-label">Total Produk</div>
                <div class="kpi-value">{{ number_format($summary['total_products'] ?? 0) }}</div>
                <div class="kpi-sub">Produk aktif</div>
            </div>
            <div class="kpi-card violet">
                <div class="kpi-label">Total Customer</div>
                <div class="kpi-value">{{ number_format($summary['total_customers'] ?? 0) }}</div>
                <div class="kpi-sub">Customer terdaftar</div>
            </div>
        </div>
    </div>

    {{-- ═══ STATUS ORDER ═══ --}}
    <div class="section">
        <div class="section-title">Status Order</div>
        <div class="status-row">
            <div class="status-card">
                <div class="status-num color-pending">{{ $summary['pending_orders'] ?? 0 }}</div>
                <div class="status-label">Pending</div>
            </div>
            <div class="status-card">
                <div class="status-num color-processing">{{ $summary['shipped_orders'] ?? 0 }}</div>
                <div class="status-label">Shipped</div>
            </div>
            <div class="status-card">
                <div class="status-num color-delivered">{{ $summary['total_orders'] ?? 0 }}</div>
                <div class="status-label">Total Order</div>
            </div>
            <div class="status-card">
                <div class="status-num color-canceled">{{ $summary['canceled_orders'] ?? 0 }}</div>
                <div class="status-label">Canceled</div>
            </div>
        </div>
    </div>

    {{-- ═══ TOP 5 PRODUK TERLARIS ═══ --}}
    <div class="section">
        <div class="section-title">Top 5 Produk Terlaris</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px">#</th>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th class="text-right">Total Qty Terjual</th>
                        <th class="text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topProducts as $i => $p)
                    <tr>
                        <td class="rank">{{ $i + 1 }}</td>
                        <td>
                            <div class="product-name">{{ $p['name'] }}</div>
                            <div class="product-sku">{{ $p['sku'] }}</div>
                        </td>
                        <td style="color:#a1a1aa; font-size:10px">{{ $p['category_name'] ?? '-' }}</td>
                        <td class="text-right" style="font-weight:700; color:#e4e4e7">{{ number_format($p['total_qty']) }} unit</td>
                        <td class="text-right revenue-val">Rp {{ number_format($p['total_revenue'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center" style="color:#52525b; padding:20px">Belum ada data transaksi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ RINGKASAN PENJUALAN PER BULAN ═══ --}}
    <div class="section">
        <div class="section-title">Ringkasan Penjualan Per Bulan ({{ now()->year }})</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th class="text-right">Jumlah Order</th>
                        <th class="text-right">Total Revenue</th>
                        <th class="bar-cell">Proporsi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        $maxRev = collect($monthlySales)->max('revenue') ?: 1;
                    @endphp
                    @foreach ($monthlySales as $row)
                    <tr>
                        <td style="font-weight:600; color:#e4e4e7">{{ $months[($row['month'] ?? 1) - 1] }}</td>
                        <td class="text-right">{{ number_format($row['orders']) }}</td>
                        <td class="text-right" style="color:#10b981; font-weight:600">
                            Rp {{ number_format($row['revenue'] / 1000000, 1) }}jt
                        </td>
                        <td class="bar-cell">
                            <div class="bar-bg">
                                <div class="bar-fill" style="width: {{ round(($row['revenue'] / $maxRev) * 100) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if (empty($monthlySales))
                    <tr><td colspan="4" class="text-center" style="color:#52525b; padding:16px">Belum ada data penjualan</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ FOOTER ═══ --}}
    <div class="footer">
        <span>Sistem Informasi Manajemen Inventaris</span>
        <span>Laporan otomatis — {{ now()->format('d/m/Y H:i') }} WIB</span>
    </div>

</body>
</html>

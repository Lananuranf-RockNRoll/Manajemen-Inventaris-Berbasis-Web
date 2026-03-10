<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Report — InvenSys</title>
<style>
/* ── Reset & Base ─────────────────────────────────────────── */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10px;
    color: #1a1a2e;
    background: #ffffff;
    line-height: 1.4;
}

/* ── Page Setup ───────────────────────────────────────────── */
@page {
    margin: 0;
    size: A4 portrait;
}

/* ── Header (fixed, repeated every page) ─────────────────── */
.page-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
    padding: 22px 32px 16px;
    position: relative;
}
.brand-row { display: table; width: 100%; }
.brand-left { display: table-cell; vertical-align: middle; }
.brand-right { display: table-cell; vertical-align: middle; text-align: right; }
.brand-name {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 1px;
}
.brand-name span { color: #e94560; }
.brand-tagline { font-size: 9px; color: #94a3b8; margin-top: 2px; letter-spacing: 0.5px; }
.report-title {
    font-size: 13px;
    font-weight: 700;
    color: #ffffff;
    text-align: right;
}
.report-meta { font-size: 8.5px; color: #94a3b8; margin-top: 3px; text-align: right; line-height: 1.7; }
.header-accent {
    height: 3px;
    background: linear-gradient(90deg, #e94560, #0f3460);
    margin-top: 14px;
}

/* ── Page Footer ──────────────────────────────────────────── */
.page-footer {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: 28px;
    background: #f1f5f9;
    border-top: 2px solid #e94560;
    padding: 0 32px;
    display: table;
    width: 100%;
}
.footer-left  { display: table-cell; vertical-align: middle; font-size: 8px; color: #64748b; }
.footer-right { display: table-cell; vertical-align: middle; text-align: right; font-size: 8px; color: #64748b; }

/* ── Content wrapper ──────────────────────────────────────── */
.content { padding: 20px 32px 40px; }

/* ── Section Title ────────────────────────────────────────── */
.section-title {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #0f3460;
    border-left: 4px solid #e94560;
    padding-left: 9px;
    margin-bottom: 10px;
    margin-top: 18px;
}

/* ── KPI Cards ────────────────────────────────────────────── */
.kpi-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 4px; }
.kpi-card {
    display: table-cell;
    width: 25%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    border-top: 4px solid #0f3460;
    padding: 13px 14px;
    vertical-align: top;
}
.kpi-card.accent-red    { border-top-color: #e94560; }
.kpi-card.accent-green  { border-top-color: #10b981; }
.kpi-card.accent-amber  { border-top-color: #f59e0b; }
.kpi-card.accent-purple { border-top-color: #8b5cf6; }
.kpi-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 7px; }
.kpi-value { font-size: 18px; font-weight: 700; color: #1a1a2e; }
.kpi-value.sm { font-size: 13px; }
.kpi-sub { font-size: 8px; color: #94a3b8; margin-top: 4px; }

/* ── Status Cards ─────────────────────────────────────────── */
.status-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 8px 0; }
.status-card {
    display: table-cell;
    width: 25%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px;
    text-align: center;
    vertical-align: middle;
}
.status-num  { font-size: 26px; font-weight: 700; }
.status-lbl  { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-top: 4px; }
.c-amber  { color: #d97706; }
.c-blue   { color: #2563eb; }
.c-green  { color: #059669; }
.c-red    { color: #dc2626; }

/* ── Data Table ───────────────────────────────────────────── */
.data-table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.data-table thead tr {
    background: #1a1a2e;
    color: #ffffff;
}
.data-table thead th {
    padding: 9px 11px;
    text-align: left;
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    white-space: nowrap;
}
.data-table tbody tr { page-break-inside: avoid; }
.data-table tbody tr:nth-child(even) { background: #f8fafc; }
.data-table tbody tr:nth-child(odd)  { background: #ffffff; }
.data-table tbody td {
    padding: 8px 11px;
    font-size: 9.5px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.data-table tbody tr:last-child td { border-bottom: none; }

/* ── Monthly bar chart ────────────────────────────────────── */
.bar-track { background: #e2e8f0; border-radius: 3px; height: 8px; width: 100%; min-width: 60px; }
.bar-fill  { background: linear-gradient(90deg, #0f3460, #e94560); border-radius: 3px; height: 8px; }

/* ── Misc ─────────────────────────────────────────────────── */
.t-right  { text-align: right; }
.t-center { text-align: center; }
.mono     { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.5px; color: #0f3460; }
.bold     { font-weight: 700; }
.muted    { color: #94a3b8; font-size: 8.5px; }
.rank-badge {
    display: inline-block;
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #0f3460;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    text-align: center;
    line-height: 20px;
}
.rank-badge.gold   { background: #d97706; }
.rank-badge.silver { background: #94a3b8; }
.rank-badge.bronze { background: #b45309; }

.revenue-val { font-weight: 700; color: #059669; }
.empty-row td { text-align: center; color: #94a3b8; padding: 20px; font-style: italic; }

.divider-section { border: none; border-top: 1px solid #f1f5f9; margin: 16px 0; }
</style>
</head>
<body>

{{-- ══ FIXED FOOTER ══ --}}
<div class="page-footer">
    <span class="footer-left">InvenSys &mdash; Inventory Management System &copy; {{ now()->year }}</span>
    <span class="footer-right">Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB &nbsp;|&nbsp; Laporan Otomatis</span>
</div>

{{-- ══ HEADER ══ --}}
<div class="page-header">
    <div class="brand-row">
        <div class="brand-left">
            <div class="brand-name">Inven<span>Sys</span></div>
            <div class="brand-tagline">INVENTORY MANAGEMENT SYSTEM</div>
        </div>
        <div class="brand-right">
            <div class="report-title">LAPORAN DASHBOARD</div>
            <div class="report-meta">
                Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB<br>
                Periode: {{ now()->format('Y') }}<br>
                Currency: USD ($)
            </div>
        </div>
    </div>
    <div class="header-accent"></div>
</div>

{{-- ══ CONTENT ══ --}}
<div class="content">

    {{-- KPI Cards --}}
    <div class="section-title">Indikator Kinerja Utama</div>
    <table class="kpi-grid">
        <tr>
            <td class="kpi-card accent-red">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value sm">${{ number_format($summary['total_revenue'] ?? 0, 2) }}</div>
                <div class="kpi-sub">Semua transaksi selesai</div>
            </td>
            <td class="kpi-card accent-green">
                <div class="kpi-label">Total Order</div>
                <div class="kpi-value">{{ number_format($summary['total_orders'] ?? 0) }}</div>
                <div class="kpi-sub">{{ $summary['pending_orders'] ?? 0 }} pending</div>
            </td>
            <td class="kpi-card accent-amber">
                <div class="kpi-label">Total Produk</div>
                <div class="kpi-value">{{ number_format($summary['total_products'] ?? 0) }}</div>
                <div class="kpi-sub">Produk aktif</div>
            </td>
            <td class="kpi-card accent-purple">
                <div class="kpi-label">Total Customer</div>
                <div class="kpi-value">{{ number_format($summary['total_customers'] ?? 0) }}</div>
                <div class="kpi-sub">Terdaftar di sistem</div>
            </td>
        </tr>
    </table>

    {{-- Status Order --}}
    <div class="section-title">Status Order</div>
    <table class="status-grid">
        <tr>
            <td class="status-card">
                <div class="status-num c-amber">{{ number_format($summary['pending_orders'] ?? 0) }}</div>
                <div class="status-lbl">Pending</div>
            </td>
            <td class="status-card">
                <div class="status-num c-blue">{{ number_format($summary['shipped_orders'] ?? 0) }}</div>
                <div class="status-lbl">Shipped</div>
            </td>
            <td class="status-card">
                <div class="status-num c-green">{{ number_format(($summary['total_orders'] ?? 0) - ($summary['pending_orders'] ?? 0) - ($summary['shipped_orders'] ?? 0) - ($summary['canceled_orders'] ?? 0)) }}</div>
                <div class="status-lbl">Delivered</div>
            </td>
            <td class="status-card">
                <div class="status-num c-red">{{ number_format($summary['canceled_orders'] ?? 0) }}</div>
                <div class="status-lbl">Canceled</div>
            </td>
        </tr>
    </table>

    {{-- Top 5 Products --}}
    <div class="section-title">Top 5 Produk Terlaris</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="t-center" style="width:36px">#</th>
                <th>Produk</th>
                <th>Kategori</th>
                <th class="t-right" style="width:70px">Qty</th>
                <th class="t-right" style="width:110px">Revenue (USD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topProducts as $i => $p)
            <tr>
                <td class="t-center">
                    @php
                        $cls = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                    @endphp
                    <span class="rank-badge {{ $cls }}">{{ $i + 1 }}</span>
                </td>
                <td>
                    <div class="bold">{{ $p['name'] }}</div>
                    <div class="mono">{{ $p['sku'] }}</div>
                </td>
                <td class="muted">{{ $p['category_name'] ?? '-' }}</td>
                <td class="t-right bold">{{ number_format($p['total_qty']) }}</td>
                <td class="t-right revenue-val">${{ number_format($p['total_revenue'], 2) }}</td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="5">Belum ada data transaksi</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Monthly Sales --}}
    <div class="section-title">Penjualan Per Bulan — {{ now()->year }}</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:60px">Bulan</th>
                <th class="t-right" style="width:80px">Jumlah Order</th>
                <th class="t-right" style="width:120px">Revenue (USD)</th>
                <th style="padding-left:16px">Proporsi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $months  = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $maxRev  = collect($monthlySales)->max('revenue') ?: 1;
                $totalRev = collect($monthlySales)->sum('revenue');
            @endphp
            @forelse ($monthlySales as $row)
            @php $pct = $maxRev > 0 ? round(($row['revenue'] / $maxRev) * 140) : 0; @endphp
            <tr>
                <td class="bold">{{ $months[($row['month'] ?? 1) - 1] }}</td>
                <td class="t-right">{{ number_format($row['orders']) }}</td>
                <td class="t-right revenue-val">${{ number_format($row['revenue'], 2) }}</td>
                <td style="padding-left:16px; vertical-align:middle;">
                    <div class="bar-track">
                        <div class="bar-fill" style="width:{{ $pct }}px"></div>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="4">Belum ada data penjualan tahun ini</td></tr>
            @endforelse
            @if (!empty($monthlySales))
            <tr style="background:#1a1a2e !important;">
                <td colspan="2" style="color:#fff; font-weight:700; font-size:9px; padding:8px 11px; text-align:right;">TOTAL {{ now()->year }}</td>
                <td class="t-right" style="color:#34d399; font-weight:700; padding:8px 11px;">${{ number_format($totalRev, 2) }}</td>
                <td></td>
            </tr>
            @endif
        </tbody>
    </table>

</div>{{-- end content --}}
</body>
</html>

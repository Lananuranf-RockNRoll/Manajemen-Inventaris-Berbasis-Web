<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Inventaris — InvenSys</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10px;
    color: #1a1a2e;
    background: #ffffff;
    line-height: 1.4;
}
@page { margin: 0; size: A4 portrait; }

/* ── Header ── */
.page-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
    padding: 22px 32px 16px;
}
.brand-row    { display: table; width: 100%; }
.brand-left   { display: table-cell; vertical-align: middle; }
.brand-right  { display: table-cell; vertical-align: middle; text-align: right; }
.brand-name   { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 1px; }
.brand-name span { color: #e94560; }
.brand-tagline { font-size: 9px; color: #94a3b8; margin-top: 2px; }
.report-title { font-size: 13px; font-weight: 700; color: #fff; }
.report-meta  { font-size: 8.5px; color: #94a3b8; margin-top: 3px; line-height: 1.7; }
.header-accent { height: 3px; background: linear-gradient(90deg, #e94560, #0f3460); margin-top: 14px; }

/* ── Footer ── */
.page-footer {
    position: fixed;
    bottom: 0; left: 0; right: 0; height: 28px;
    background: #f1f5f9;
    border-top: 2px solid #e94560;
    padding: 0 32px;
    display: table; width: 100%;
}
.footer-left  { display: table-cell; vertical-align: middle; font-size: 8px; color: #64748b; }
.footer-right { display: table-cell; vertical-align: middle; text-align: right; font-size: 8px; color: #64748b; }

/* ── Content ── */
.content { padding: 20px 32px 44px; }

/* ── Summary strip ── */
.summary-strip { display: table; width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 16px; }
.summary-card {
    display: table-cell; width: 33%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    border-top: 4px solid #0f3460;
    padding: 12px 14px;
    text-align: center;
    vertical-align: middle;
}
.summary-card.orange { border-top-color: #f59e0b; }
.summary-card.green  { border-top-color: #10b981; }
.summary-num { font-size: 22px; font-weight: 700; color: #1a1a2e; }
.summary-lbl { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-top: 4px; }

/* ── Section Title ── */
.section-title {
    font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: #0f3460;
    border-left: 4px solid #e94560;
    padding-left: 9px;
    margin-bottom: 10px; margin-top: 4px;
}

/* ── Data Table ── */
.data-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; }
.data-table thead tr { background: #1a1a2e; color: #fff; }
.data-table thead th {
    padding: 9px 10px;
    text-align: left; font-size: 9px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px;
    white-space: nowrap;
}
/* CRITICAL: prevent row from splitting across pages */
.data-table tbody tr { page-break-inside: avoid; }
.data-table tbody tr:nth-child(even) { background: #f8fafc; }
.data-table tbody tr:nth-child(odd)  { background: #ffffff; }
.data-table tbody td {
    padding: 7px 10px;
    font-size: 9.5px; color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.data-table tbody tr:last-child td { border-bottom: none; }

/* Status badges */
.badge {
    display: inline-block;
    padding: 2px 8px; border-radius: 10px;
    font-size: 8.5px; font-weight: 700;
}
.badge-low    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.badge-normal { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }

/* Misc */
.t-right  { text-align: right; }
.t-center { text-align: center; }
.mono     { font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; color: #0f3460; }
.bold     { font-weight: 700; }
.muted    { color: #94a3b8; font-size: 8.5px; }
.no-wrap  { white-space: nowrap; }
.empty-row td { text-align: center; color: #94a3b8; padding: 20px; font-style: italic; }

/* Stock value colors */
.stock-low  { color: #d97706; font-weight: 700; }
.stock-ok   { color: #059669; font-weight: 700; }
</style>
</head>
<body>

{{-- FIXED FOOTER --}}
<div class="page-footer">
    <span class="footer-left">InvenSys &mdash; Inventory Management System &copy; {{ now()->year }}</span>
    <span class="footer-right">Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB &nbsp;|&nbsp; Gudang: {{ $warehouseName ?? 'Semua Gudang' }}</span>
</div>

{{-- HEADER --}}
<div class="page-header">
    <div class="brand-row">
        <div class="brand-left">
            <div class="brand-name">Inven<span>Sys</span></div>
            <div class="brand-tagline">INVENTORY MANAGEMENT SYSTEM</div>
        </div>
        <div class="brand-right">
            <div class="report-title">LAPORAN INVENTARIS</div>
            <div class="report-meta">
                Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB<br>
                Gudang: {{ $warehouseName ?? 'Semua Gudang' }}<br>
                Currency: USD ($)
            </div>
        </div>
    </div>
    <div class="header-accent"></div>
</div>

{{-- CONTENT --}}
<div class="content">

    {{-- Summary Cards --}}
    @php
        $lowCount    = $items->filter(fn($i) => ($i->qty_on_hand - $i->qty_reserved) <= $i->min_stock)->count();
        $totalOnHand = $items->sum('qty_on_hand');
    @endphp
    <table class="summary-strip">
        <tr>
            <td class="summary-card">
                <div class="summary-num">{{ number_format($items->count()) }}</div>
                <div class="summary-lbl">Total Item</div>
            </td>
            <td class="summary-card orange">
                <div class="summary-num" style="color:#d97706">{{ number_format($lowCount) }}</div>
                <div class="summary-lbl">Stok Rendah</div>
            </td>
            <td class="summary-card green">
                <div class="summary-num" style="color:#059669">{{ number_format($totalOnHand) }}</div>
                <div class="summary-lbl">Total Unit Di Tangan</div>
            </td>
        </tr>
    </table>

    {{-- Detail Table --}}
    <div class="section-title">Detail Inventaris</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="t-center" style="width:28px">No</th>
                <th style="width:80px">SKU</th>
                <th>Produk</th>
                <th style="width:80px">Kategori</th>
                <th>Gudang</th>
                <th class="t-center" style="width:60px">Di Tangan</th>
                <th class="t-center" style="width:60px">Tersedia</th>
                <th class="t-center" style="width:38px">Min</th>
                <th class="t-center" style="width:65px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
            @php
                $available = $item->qty_on_hand - $item->qty_reserved;
                $isLow     = $available <= $item->min_stock;
            @endphp
            <tr>
                <td class="t-center muted">{{ $i + 1 }}</td>
                <td><span class="mono">{{ $item->product->sku }}</span></td>
                <td class="bold">{{ $item->product->name }}</td>
                <td class="muted">{{ $item->product->category->name ?? '-' }}</td>
                <td>{{ $item->warehouse->name }}</td>
                <td class="t-center bold">{{ number_format($item->qty_on_hand) }}</td>
                <td class="t-center {{ $isLow ? 'stock-low' : 'stock-ok' }}">{{ number_format($available) }}</td>
                <td class="t-center muted">{{ $item->min_stock }}</td>
                <td class="t-center">
                    @if ($isLow)
                        <span class="badge badge-low">Rendah</span>
                    @else
                        <span class="badge badge-normal">Normal</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="9">Tidak ada data inventaris</td></tr>
            @endforelse
        </tbody>
    </table>

</div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Penjualan — InvenSys</title>
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

/* ── Summary Cards ── */
.summary-strip { display: table; width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 16px; }
.summary-card {
    display: table-cell; width: 25%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    border-top: 4px solid #0f3460;
    padding: 12px 14px;
    text-align: center;
    vertical-align: middle;
}
.summary-card.green  { border-top-color: #10b981; }
.summary-card.amber  { border-top-color: #f59e0b; }
.summary-card.red    { border-top-color: #ef4444; }
.summary-num     { font-size: 20px; font-weight: 700; color: #1a1a2e; }
.summary-num.sm  { font-size: 13px; }
.summary-lbl     { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-top: 4px; }

/* ── Section Title ── */
.section-title {
    font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: #0f3460;
    border-left: 4px solid #e94560;
    padding-left: 9px;
    margin-bottom: 10px; margin-top: 4px;
}

/* ── Filter Info ── */
.filter-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 5px;
    padding: 8px 12px;
    margin-bottom: 14px;
    font-size: 9px;
    color: #1e40af;
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
/* CRITICAL: prevent rows from being cut across page breaks */
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

/* Total row */
.total-row { page-break-inside: avoid; }
.total-row td {
    background: #1a1a2e !important;
    color: #fff !important;
    font-weight: 700;
    font-size: 9.5px;
    padding: 9px 10px;
    border: none !important;
}

/* Status badges */
.badge {
    display: inline-block;
    padding: 2px 8px; border-radius: 10px;
    font-size: 8.5px; font-weight: 700;
    white-space: nowrap;
}
.badge-pending    { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.badge-processing { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.badge-shipped    { background:#ede9fe; color:#5b21b6; border:1px solid #ddd6fe; }
.badge-delivered  { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
.badge-canceled   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

/* Misc */
.t-right  { text-align: right; }
.t-center { text-align: center; }
.mono     { font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; color: #0f3460; }
.bold     { font-weight: 700; }
.muted    { color: #94a3b8; font-size: 8.5px; }
.revenue  { font-weight: 700; color: #059669; }
.empty-row td { text-align: center; color: #94a3b8; padding: 20px; font-style: italic; }
</style>
</head>
<body>

{{-- FIXED FOOTER --}}
<div class="page-footer">
    <span class="footer-left">InvenSys &mdash; Inventory Management System &copy; {{ now()->year }}</span>
    <span class="footer-right">Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB &nbsp;|&nbsp; Laporan Otomatis</span>
</div>

{{-- HEADER --}}
<div class="page-header">
    <div class="brand-row">
        <div class="brand-left">
            <div class="brand-name">Inven<span>Sys</span></div>
            <div class="brand-tagline">INVENTORY MANAGEMENT SYSTEM</div>
        </div>
        <div class="brand-right">
            <div class="report-title">LAPORAN PENJUALAN</div>
            <div class="report-meta">
                Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB<br>
                Periode: {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : 'Semua' }}
                         &nbsp;&mdash;&nbsp;
                         {{ $to   ? \Carbon\Carbon::parse($to)->format('d/m/Y')   : 'Semua' }}<br>
                Currency: USD ($)
            </div>
        </div>
    </div>
    <div class="header-accent"></div>
</div>

{{-- CONTENT --}}
<div class="content">

    @php
        $delivered = $transactions->where('status','delivered');
        $canceled  = $transactions->where('status','canceled');
        $revenue   = $transactions->whereIn('status',['delivered','shipped'])->sum('total_amount');
        $grandTotal = $transactions->sum('total_amount');
    @endphp

    {{-- Summary --}}
    <table class="summary-strip">
        <tr>
            <td class="summary-card">
                <div class="summary-num">{{ number_format($transactions->count()) }}</div>
                <div class="summary-lbl">Total Order</div>
            </td>
            <td class="summary-card green">
                <div class="summary-num">{{ number_format($delivered->count()) }}</div>
                <div class="summary-lbl">Selesai</div>
            </td>
            <td class="summary-card amber">
                <div class="summary-num sm">${{ number_format($revenue, 2) }}</div>
                <div class="summary-lbl">Revenue (Selesai+Kirim)</div>
            </td>
            <td class="summary-card red">
                <div class="summary-num">{{ number_format($canceled->count()) }}</div>
                <div class="summary-lbl">Dibatalkan</div>
            </td>
        </tr>
    </table>

    {{-- Filter info --}}
    @if ($from || $to)
    <div class="filter-info">
        🔍 Filter aktif — Periode:
        <strong>{{ $from ? \Carbon\Carbon::parse($from)->format('d F Y') : 'Awal' }}</strong>
        s/d
        <strong>{{ $to ? \Carbon\Carbon::parse($to)->format('d F Y') : 'Sekarang' }}</strong>
        &nbsp;|&nbsp; {{ $transactions->count() }} transaksi ditampilkan
    </div>
    @endif

    {{-- Transaction Table --}}
    <div class="section-title">Detail Transaksi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="t-center" style="width:28px">No</th>
                <th style="width:105px">No. Order</th>
                <th style="width:62px">Tanggal</th>
                <th>Customer</th>
                <th>Gudang</th>
                <th class="t-center" style="width:72px">Status</th>
                <th class="t-right" style="width:100px">Total (USD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $i => $trx)
            <tr>
                <td class="t-center muted">{{ $i + 1 }}</td>
                <td><span class="mono">{{ $trx->order_number }}</span></td>
                <td class="muted">{{ \Carbon\Carbon::parse($trx->order_date)->format('d/m/Y') }}</td>
                <td class="bold">{{ $trx->customer->name ?? '-' }}</td>
                <td>{{ $trx->warehouse->name ?? '-' }}</td>
                <td class="t-center">
                    <span class="badge badge-{{ $trx->status }}">{{ ucfirst($trx->status) }}</span>
                </td>
                <td class="t-right revenue">${{ number_format($trx->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="7">Tidak ada transaksi dalam periode ini</td></tr>
            @endforelse
            @if ($transactions->count() > 0)
            <tr class="total-row">
                <td colspan="6" class="t-right">TOTAL KESELURUHAN ({{ $transactions->count() }} transaksi)</td>
                <td class="t-right">${{ number_format($grandTotal, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

</div>
</body>
</html>

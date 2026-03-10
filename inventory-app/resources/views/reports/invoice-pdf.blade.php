<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Invoice {{ $transaction->order_number }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10px;
    color: #1e293b;
    background: #ffffff;
    line-height: 1.5;
}
@page { margin: 0; size: A4 portrait; }

/* ── Fixed footer every page ── */
.page-footer {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: 30px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 0 36px;
    display: table;
    width: 100%;
}
.footer-l { display: table-cell; vertical-align: middle; font-size: 7.5px; color: #94a3b8; }
.footer-r { display: table-cell; vertical-align: middle; text-align: right; font-size: 7.5px; color: #94a3b8; }

/* ── Page wrapper ── */
.page { padding: 36px 36px 52px; }

/* ── Header ── */
.inv-header { display: table; width: 100%; margin-bottom: 28px; }
.inv-header-left  { display: table-cell; vertical-align: top; width: 55%; }
.inv-header-right { display: table-cell; vertical-align: top; text-align: right; }

.brand { font-size: 22px; font-weight: 700; color: #0f172a; letter-spacing: 0.5px; }
.brand span { color: #e94560; }
.brand-sub { font-size: 8px; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-top: 1px; }

.inv-label {
    display: inline-block;
    background: #0f172a;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 2px;
    padding: 5px 14px;
    border-radius: 4px;
    margin-bottom: 6px;
}
.inv-number { font-size: 11px; font-weight: 700; color: #0f172a; font-family: 'DejaVu Sans Mono', monospace; }

/* ── Status badge ── */
.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-top: 5px;
}
.s-pending    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.s-processing { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
.s-shipped    { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
.s-delivered  { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.s-canceled   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* ── Divider ── */
.divider { border: none; border-top: 2px solid #0f172a; margin: 0 0 20px; }
.divider-light { border: none; border-top: 1px solid #e2e8f0; margin: 16px 0; }

/* ── Info grid ── */
.info-grid { display: table; width: 100%; margin-bottom: 22px; }
.info-col  { display: table-cell; width: 33%; vertical-align: top; padding-right: 12px; }
.info-col:last-child { padding-right: 0; }
.info-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700; margin-bottom: 4px; }
.info-value { font-size: 10.5px; font-weight: 600; color: #0f172a; }
.info-value-sub { font-size: 9px; color: #64748b; margin-top: 2px; }

/* ── Items table ── */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.items-table thead tr { background: #0f172a; }
.items-table thead th {
    padding: 9px 12px;
    text-align: left;
    font-size: 8.5px;
    font-weight: 600;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    white-space: nowrap;
}
.items-table thead th.r { text-align: right; }
.items-table tbody tr { page-break-inside: avoid; }
.items-table tbody tr:nth-child(even) { background: #f8fafc; }
.items-table tbody tr:nth-child(odd)  { background: #ffffff; }
.items-table tbody td {
    padding: 9px 12px;
    font-size: 10px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.items-table tbody tr:last-child td { border-bottom: 2px solid #0f172a; }
.td-r { text-align: right; }
.td-c { text-align: center; }
.product-name { font-weight: 600; color: #0f172a; }
.product-sku  { font-size: 8px; color: #94a3b8; font-family: 'DejaVu Sans Mono', monospace; margin-top: 1px; }

/* ── Totals ── */
.totals-wrap { display: table; width: 100%; margin-top: 0; }
.totals-left  { display: table-cell; width: 55%; vertical-align: top; padding-right: 20px; }
.totals-right { display: table-cell; width: 45%; vertical-align: top; }

.notes-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px;
    margin-top: 12px;
}
.notes-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700; margin-bottom: 5px; }
.notes-text  { font-size: 9.5px; color: #475569; }

.totals-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.totals-table td { padding: 5px 0; font-size: 10px; vertical-align: middle; }
.totals-table .lbl { color: #64748b; }
.totals-table .val { text-align: right; font-weight: 600; color: #0f172a; }
.totals-table .grand-lbl {
    font-size: 11px; font-weight: 700; color: #0f172a;
    border-top: 2px solid #0f172a; padding-top: 8px; padding-bottom: 0;
}
.totals-table .grand-val {
    text-align: right;
    font-size: 14px; font-weight: 700; color: #e94560;
    border-top: 2px solid #0f172a; padding-top: 8px; padding-bottom: 0;
}

/* ── Credit info ── */
.credit-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    padding: 10px 12px;
    margin-top: 10px;
}
.credit-row { display: table; width: 100%; }
.credit-lbl { display: table-cell; font-size: 8.5px; color: #1e40af; }
.credit-val { display: table-cell; text-align: right; font-size: 8.5px; font-weight: 700; color: #1e40af; }

/* ── Stamp / watermark for canceled ── */
.stamp-canceled {
    position: fixed;
    top: 180px; left: 60px;
    width: 280px;
    border: 6px solid #dc2626;
    border-radius: 8px;
    padding: 10px 20px;
    text-align: center;
    color: #dc2626;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 4px;
    opacity: 0.18;
    transform: rotate(-20deg);
    text-transform: uppercase;
    pointer-events: none;
}

/* ── Thank you footer ── */
.thankyou {
    margin-top: 28px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}
.thankyou-main { font-size: 10px; font-weight: 600; color: #475569; }
.thankyou-sub  { font-size: 8.5px; color: #94a3b8; margin-top: 3px; }
</style>
</head>
<body>

{{-- WATERMARK if canceled --}}
@if ($transaction->status === 'canceled')
<div class="stamp-canceled">DIBATALKAN</div>
@endif

{{-- FIXED FOOTER --}}
<div class="page-footer">
    <span class="footer-l">InvenSys &mdash; {{ $transaction->order_number }}</span>
    <span class="footer-r">Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</span>
</div>

<div class="page">

    {{-- ══ HEADER ══ --}}
    <div class="inv-header">
        <div class="inv-header-left">
            <div class="brand">Inven<span>Sys</span></div>
            <div class="brand-sub">Inventory Management System</div>
            <div style="margin-top: 10px; font-size:9px; color:#64748b; line-height:1.7;">
                Jl. Contoh No. 123, Jakarta<br>
                info@invensys.app
            </div>
        </div>
        <div class="inv-header-right">
            <div class="inv-label">INVOICE</div><br>
            <div class="inv-number">{{ $transaction->order_number }}</div>
            <div style="margin-top:6px;">
                <span class="status-badge s-{{ $transaction->status }}">
                    {{ strtoupper($transaction->status) }}
                </span>
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- ══ INFO GRID ══ --}}
    <div class="info-grid">
        {{-- Customer --}}
        <div class="info-col">
            <div class="info-label">Tagihan Kepada</div>
            <div class="info-value">{{ $transaction->customer->name ?? '-' }}</div>
            @if ($transaction->customer?->phone)
            <div class="info-value-sub">{{ $transaction->customer->phone }}</div>
            @endif
            @if ($transaction->customer?->email)
            <div class="info-value-sub">{{ $transaction->customer->email }}</div>
            @endif
            @if ($transaction->customer?->address)
            <div class="info-value-sub">{{ $transaction->customer->address }}</div>
            @endif
        </div>

        {{-- Warehouse --}}
        <div class="info-col">
            <div class="info-label">Dikirim Dari</div>
            <div class="info-value">{{ $transaction->warehouse->name ?? '-' }}</div>
            @if ($transaction->warehouse?->location)
            <div class="info-value-sub">{{ $transaction->warehouse->location }}</div>
            @endif
        </div>

        {{-- Dates --}}
        <div class="info-col">
            <div class="info-label">Tanggal Invoice</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($transaction->order_date)->format('d F Y') }}</div>
            @if ($transaction->shipped_date)
            <div style="margin-top:8px;">
                <div class="info-label">Tanggal Kirim</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($transaction->shipped_date)->format('d F Y') }}</div>
            </div>
            @endif
            <div style="margin-top:8px;">
                <div class="info-label">Mata Uang</div>
                <div class="info-value">USD ($)</div>
            </div>
        </div>
    </div>

    {{-- ══ ITEMS TABLE ══ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th>Produk / Deskripsi</th>
                <th class="r" style="width:55px">Qty</th>
                <th class="r" style="width:100px">Harga Satuan</th>
                <th class="r" style="width:110px">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaction->items as $i => $item)
            <tr>
                <td class="td-c" style="color:#94a3b8; font-size:9px;">{{ $i + 1 }}</td>
                <td>
                    <div class="product-name">{{ $item->product->name ?? '-' }}</div>
                    <div class="product-sku">SKU: {{ $item->product->sku ?? '-' }}</div>
                </td>
                <td class="td-r" style="font-weight:600;">{{ number_format($item->quantity) }}</td>
                <td class="td-r">${{ number_format($item->unit_price, 2) }}</td>
                <td class="td-r" style="font-weight:700; color:#0f172a;">${{ number_format($item->subtotal ?? ($item->quantity * $item->unit_price), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ══ TOTALS + NOTES ══ --}}
    <div class="totals-wrap">

        {{-- Left: notes + credit --}}
        <div class="totals-left">
            @if ($transaction->notes)
            <div class="notes-box">
                <div class="notes-label">Catatan</div>
                <div class="notes-text">{{ $transaction->notes }}</div>
            </div>
            @endif

            @if ($transaction->customer)
            <div class="credit-box">
                <div class="credit-row">
                    <span class="credit-lbl">Credit Limit Customer</span>
                    <span class="credit-val">${{ number_format($transaction->customer->credit_limit ?? 0, 2) }}</span>
                </div>
                <div class="credit-row" style="margin-top:3px;">
                    <span class="credit-lbl">Credit Terpakai</span>
                    <span class="credit-val">${{ number_format($transaction->customer->credit_used ?? 0, 2) }}</span>
                </div>
                <div class="credit-row" style="margin-top:3px;">
                    <span class="credit-lbl">Sisa Credit</span>
                    <span class="credit-val" style="color:#059669;">
                        ${{ number_format(($transaction->customer->credit_limit ?? 0) - ($transaction->customer->credit_used ?? 0), 2) }}
                    </span>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: totals --}}
        <div class="totals-right">
            @php
                $subtotal = $transaction->items->sum(fn($i) => $i->quantity * $i->unit_price);
                $total    = $transaction->total_amount;
                $discount = $subtotal - $total;
            @endphp
            <table class="totals-table">
                <tr>
                    <td class="lbl">Subtotal</td>
                    <td class="val">${{ number_format($subtotal, 2) }}</td>
                </tr>
                @if ($discount > 0)
                <tr>
                    <td class="lbl">Diskon</td>
                    <td class="val" style="color:#e94560;">&minus;${{ number_format($discount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="lbl">Pajak (0%)</td>
                    <td class="val">$0.00</td>
                </tr>
                <tr>
                    <td class="grand-lbl">TOTAL</td>
                    <td class="grand-val">${{ number_format($total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ══ THANK YOU ══ --}}
    <div class="thankyou">
        <div class="thankyou-main">Terima kasih atas kepercayaan Anda!</div>
        <div class="thankyou-sub">Dokumen ini digenerate otomatis oleh InvenSys &mdash; {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</div>
    </div>

</div>
</body>
</html>

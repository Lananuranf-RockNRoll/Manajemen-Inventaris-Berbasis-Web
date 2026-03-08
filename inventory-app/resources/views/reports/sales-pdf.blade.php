<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #ffffff; }
        .header { background-color: #1e3a5f; padding: 18px 30px; }
        .header-table { width: 100%; }
        .header-title { font-size: 17px; font-weight: 700; color: #ffffff; }
        .header-sub   { font-size: 10px; color: #b0c4de; margin-top: 3px; }
        .header-meta  { text-align: right; font-size: 10px; color: #b0c4de; line-height: 1.8; }
        .divider { background-color: #2e86c1; height: 4px; margin-bottom: 20px; }
        .summary { padding: 0 30px; margin-bottom: 16px; }
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
        .summary-card { background-color: #f0f4f8; border: 1px solid #d0dce8; border-top: 3px solid #2e86c1; padding: 10px; text-align: center; width: 33%; }
        .summary-card.green  { border-top-color: #27ae60; }
        .summary-card.orange { border-top-color: #e67e22; }
        .summary-num { font-size: 18px; font-weight: 700; color: #1e3a5f; }
        .summary-num.small { font-size: 13px; }
        .summary-lbl { font-size: 9px; color: #666666; margin-top: 3px; text-transform: uppercase; }
        .section { padding: 0 30px; margin-bottom: 16px; }
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #1e3a5f; border-left: 4px solid #2e86c1; padding-left: 8px; margin-bottom: 10px; }
        .data-table { width: 100%; border-collapse: collapse; border: 1px solid #d0dce8; }
        .data-table thead tr { background-color: #1e3a5f; color: #ffffff; }
        .data-table thead th { padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table tbody tr:nth-child(even) { background-color: #f0f4f8; }
        .data-table tbody tr:nth-child(odd)  { background-color: #ffffff; }
        .data-table tbody td { padding: 7px 10px; font-size: 10px; color: #333333; border-bottom: 1px solid #e0e8f0; }
        .total-row td { background-color: #1e3a5f !important; color: #ffffff !important; font-weight: 700; padding: 8px 10px; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: 700; }
        .badge-pending    { background:#fef3c7; color:#92400e; }
        .badge-processing { background:#dbeafe; color:#1e40af; }
        .badge-shipped    { background:#ede9fe; color:#5b21b6; }
        .badge-delivered  { background:#d1fae5; color:#065f46; }
        .badge-canceled   { background:#fee2e2; color:#991b1b; }
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
                    <div class="header-title">Laporan Penjualan</div>
                    <div class="header-sub">Sistem Informasi Manajemen Inventaris</div>
                </td>
                <td class="header-meta">
                    <div>Dicetak: {{ now()->format('d F Y, H:i') }} WIB</div>
                    <div>Periode: {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : '-' }} s/d {{ $to ? \Carbon\Carbon::parse($to)->format('d/m/Y') : '-' }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="divider"></div>

    {{-- SUMMARY --}}
    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="summary-card">
                    <div class="summary-num">{{ $transactions->count() }}</div>
                    <div class="summary-lbl">Total Order</div>
                </td>
                <td class="summary-card green">
                    <div class="summary-num">{{ $transactions->where('status', 'delivered')->count() }}</div>
                    <div class="summary-lbl">Selesai</div>
                </td>
                <td class="summary-card orange">
                    <div class="summary-num small">Rp {{ number_format($transactions->whereIn('status', ['delivered','shipped'])->sum('total_amount'), 0, ',', '.') }}</div>
                    <div class="summary-lbl">Total Revenue</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- TABLE --}}
    <div class="section">
        <div class="section-title">Detail Transaksi</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:28px">No</th>
                    <th>No. Order</th>
                    <th style="width:70px">Tanggal</th>
                    <th>Customer</th>
                    <th>Gudang</th>
                    <th class="text-center" style="width:70px">Status</th>
                    <th class="text-right" style="width:90px">Total (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $i => $trx)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td style="font-family:monospace; color:#1e3a5f; font-size:9px;">{{ $trx->order_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($trx->order_date)->format('d/m/Y') }}</td>
                    <td style="font-weight:600;">{{ $trx->customer->name ?? '-' }}</td>
                    <td>{{ $trx->warehouse->name ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $trx->status }}">{{ ucfirst($trx->status) }}</span>
                    </td>
                    <td class="text-right" style="font-weight:600;">
                        {{ number_format($trx->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="text-right">TOTAL KESELURUHAN</td>
                    <td class="text-right">Rp {{ number_format($transactions->sum('total_amount'), 0, ',', '.') }}</td>
                </tr>
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

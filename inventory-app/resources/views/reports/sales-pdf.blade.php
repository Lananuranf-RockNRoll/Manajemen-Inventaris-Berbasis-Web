<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #18181b; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4F46E5; padding-bottom: 12px; }
        .header h1 { font-size: 16px; font-weight: 700; color: #4F46E5; }
        .header p { font-size: 10px; color: #71717a; margin-top: 3px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 10px; color: #52525b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        thead tr { background-color: #4F46E5; color: white; }
        thead th { padding: 7px 8px; text-align: left; font-size: 10px; font-weight: 600; }
        tbody tr:nth-child(even) { background-color: #f4f4f5; }
        tbody tr { border-bottom: 1px solid #e4e4e7; }
        tbody td { padding: 6px 8px; }
        .summary { margin-bottom: 16px; display: flex; gap: 12px; }
        .summary-card { flex: 1; background: #f4f4f5; border-radius: 6px; padding: 10px; text-align: center; }
        .summary-card .num { font-size: 18px; font-weight: 700; color: #4F46E5; }
        .summary-card .lbl { font-size: 9px; color: #71717a; margin-top: 2px; }
        .badge { padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-pending   { background:#fef3c7; color:#92400e; }
        .badge-processing{ background:#dbeafe; color:#1e40af; }
        .badge-shipped   { background:#ede9fe; color:#5b21b6; }
        .badge-delivered { background:#d1fae5; color:#065f46; }
        .badge-canceled  { background:#fee2e2; color:#991b1b; }
        .footer { margin-top: 20px; text-align: right; font-size: 9px; color: #a1a1aa; }
        .total-row { background-color: #ede9fe !important; font-weight: 700; }
    </style>
</head>
<body>

    <div class="header">
        <h1>📊 Laporan Penjualan</h1>
        <p>Sistem Informasi Manajemen Inventaris</p>
    </div>

    <div class="meta">
        <span>
            Periode:
            {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : '-' }}
            s/d
            {{ $to ? \Carbon\Carbon::parse($to)->format('d/m/Y') : '-' }}
        </span>
        <span>Dicetak: {{ now()->format('d F Y, H:i') }} WIB</span>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="num">{{ $transactions->count() }}</div>
            <div class="lbl">Total Order</div>
        </div>
        <div class="summary-card">
            <div class="num">{{ $transactions->where('status', 'delivered')->count() }}</div>
            <div class="lbl">Selesai</div>
        </div>
        <div class="summary-card">
            <div class="num">Rp {{ number_format($transactions->whereIn('status', ['delivered','shipped'])->sum('total_amount'), 0, ',', '.') }}</div>
            <div class="lbl">Total Revenue</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Order</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Gudang</th>
                <th>Status</th>
                <th style="text-align:right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $i => $trx)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td style="font-family:monospace; color:#6366f1;">{{ $trx->order_number }}</td>
                <td>{{ \Carbon\Carbon::parse($trx->order_date)->format('d/m/Y') }}</td>
                <td>{{ $trx->customer->name ?? '-' }}</td>
                <td>{{ $trx->warehouse->name ?? '-' }}</td>
                <td>
                    <span class="badge badge-{{ $trx->status }}">{{ ucfirst($trx->status) }}</span>
                </td>
                <td style="text-align:right; font-weight:600;">
                    {{ number_format($trx->total_amount, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="text-align:right; padding-right:8px;">TOTAL</td>
                <td style="text-align:right;">
                    Rp {{ number_format($transactions->sum('total_amount'), 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Laporan dibuat otomatis oleh Sistem Inventaris &mdash; {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>

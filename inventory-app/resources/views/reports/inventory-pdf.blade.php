<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Inventaris</title>
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
        .badge-low { background-color: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-normal { background-color: #d1fae5; color: #065f46; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .footer { margin-top: 20px; text-align: right; font-size: 9px; color: #a1a1aa; }
        .summary { margin-bottom: 16px; display: flex; gap: 12px; }
        .summary-card { flex: 1; background: #f4f4f5; border-radius: 6px; padding: 10px; text-align: center; }
        .summary-card .num { font-size: 20px; font-weight: 700; color: #4F46E5; }
        .summary-card .lbl { font-size: 9px; color: #71717a; margin-top: 2px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Inventaris</h1>
        <p>Sistem Informasi Manajemen Inventaris</p>
    </div>

    <div class="meta">
        <span>Dicetak: {{ now()->format('d F Y, H:i') }} WIB</span>
        <span>Gudang: {{ $warehouseName ?? 'Semua Gudang' }}</span>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="num">{{ $items->count() }}</div>
            <div class="lbl">Total Item</div>
        </div>
        <div class="summary-card">
            <div class="num">{{ $items->where('is_low_stock', true)->count() }}</div>
            <div class="lbl">Stok Rendah</div>
        </div>
        <div class="summary-card">
            <div class="num">{{ $items->sum('qty_on_hand') }}</div>
            <div class="lbl">Total Stok</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>SKU</th>
                <th>Produk</th>
                <th>Kategori</th>
                <th>Gudang</th>
                <th>Di Tangan</th>
                <th>Tersedia</th>
                <th>Min</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td style="font-family: monospace; color: #6366f1;">{{ $item->product->sku }}</td>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->category->name ?? '-' }}</td>
                <td>{{ $item->warehouse->name }}</td>
                <td style="text-align:center; font-weight:600;">{{ $item->qty_on_hand }}</td>
                <td style="text-align:center; font-weight:700; color: {{ ($item->qty_on_hand - $item->qty_reserved) <= $item->min_stock ? '#d97706' : '#059669' }}">
                    {{ $item->qty_on_hand - $item->qty_reserved }}
                </td>
                <td style="text-align:center; color:#71717a;">{{ $item->min_stock }}</td>
                <td>
                    @if (($item->qty_on_hand - $item->qty_reserved) <= $item->min_stock)
                        <span class="badge-low">Rendah</span>
                    @else
                        <span class="badge-normal">Normal</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Laporan dibuat otomatis oleh Sistem Inventaris &mdash; {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>

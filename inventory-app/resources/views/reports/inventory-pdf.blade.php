<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Inventaris</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #ffffff; }

        /* ── Header ── */
        .header {
            background-color: #1e3a5f;
            padding: 18px 30px;
        }
        .header-table { width: 100%; }
        .header-title { font-size: 17px; font-weight: 700; color: #ffffff; }
        .header-sub   { font-size: 10px; color: #b0c4de; margin-top: 3px; }
        .header-meta  { text-align: right; font-size: 10px; color: #b0c4de; line-height: 1.8; }

        .divider { background-color: #2e86c1; height: 4px; margin-bottom: 20px; }

        /* ── Meta info ── */
        .meta {
            padding: 0 30px;
            margin-bottom: 16px;
        }
        .meta-table { width: 100%; }
        .meta-left  { font-size: 10px; color: #444444; }
        .meta-right { text-align: right; font-size: 10px; color: #444444; }

        /* ── Summary Cards ── */
        .summary { padding: 0 30px; margin-bottom: 16px; }
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .summary-card {
            background-color: #f0f4f8;
            border: 1px solid #d0dce8;
            border-top: 3px solid #2e86c1;
            padding: 10px;
            text-align: center;
            width: 33%;
        }
        .summary-card.orange { border-top-color: #e67e22; }
        .summary-card.green  { border-top-color: #27ae60; }
        .summary-num { font-size: 20px; font-weight: 700; color: #1e3a5f; }
        .summary-lbl { font-size: 9px; color: #666666; margin-top: 3px; text-transform: uppercase; }

        /* ── Section Title ── */
        .section { padding: 0 30px; margin-bottom: 16px; }
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

        /* ── Data Table ── */
        .data-table { width: 100%; border-collapse: collapse; border: 1px solid #d0dce8; }
        .data-table thead tr { background-color: #1e3a5f; color: #ffffff; }
        .data-table thead th {
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table tbody tr:nth-child(even) { background-color: #f0f4f8; }
        .data-table tbody tr:nth-child(odd)  { background-color: #ffffff; }
        .data-table tbody td {
            padding: 7px 10px;
            font-size: 10px;
            color: #333333;
            border-bottom: 1px solid #e0e8f0;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }

        .badge-low    { background-color: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: 700; }
        .badge-normal { background-color: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: 700; }

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
                    <div class="header-title">Laporan Inventaris</div>
                    <div class="header-sub">Sistem Informasi Manajemen Inventaris</div>
                </td>
                <td class="header-meta">
                    <div>Dicetak: {{ now()->format('d F Y, H:i') }} WIB</div>
                    <div>Gudang: {{ $warehouseName ?? 'Semua Gudang' }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="divider"></div>

    {{-- SUMMARY CARDS --}}
    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="summary-card">
                    <div class="summary-num">{{ $items->count() }}</div>
                    <div class="summary-lbl">Total Item</div>
                </td>
                <td class="summary-card orange">
                    <div class="summary-num">{{ $items->where('is_low_stock', true)->count() }}</div>
                    <div class="summary-lbl">Stok Rendah</div>
                </td>
                <td class="summary-card green">
                    <div class="summary-num">{{ $items->sum('qty_on_hand') }}</div>
                    <div class="summary-lbl">Total Stok</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- TABLE --}}
    <div class="section">
        <div class="section-title">Detail Inventaris</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:28px">No</th>
                    <th style="width:90px">SKU</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Gudang</th>
                    <th class="text-center" style="width:55px">Di Tangan</th>
                    <th class="text-center" style="width:55px">Tersedia</th>
                    <th class="text-center" style="width:40px">Min</th>
                    <th class="text-center" style="width:60px">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $i => $item)
                @php $available = $item->qty_on_hand - $item->qty_reserved; @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td style="font-family: monospace; color: #1e3a5f; font-size:9px;">{{ $item->product->sku }}</td>
                    <td style="font-weight:600;">{{ $item->product->name }}</td>
                    <td style="color:#555555; font-size:10px;">{{ $item->product->category->name ?? '-' }}</td>
                    <td>{{ $item->warehouse->name }}</td>
                    <td class="text-center" style="font-weight:600;">{{ $item->qty_on_hand }}</td>
                    <td class="text-center" style="font-weight:700; color: {{ $available <= $item->min_stock ? '#d97706' : '#059669' }}">
                        {{ $available }}
                    </td>
                    <td class="text-center" style="color:#666666;">{{ $item->min_stock }}</td>
                    <td class="text-center">
                        @if ($available <= $item->min_stock)
                            <span class="badge-low">Rendah</span>
                        @else
                            <span class="badge-normal">Normal</span>
                        @endif
                    </td>
                </tr>
                @endforeach
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

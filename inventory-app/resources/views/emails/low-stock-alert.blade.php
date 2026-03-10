<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peringatan Stok Rendah</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f4f5; color: #18181b; padding: 32px 16px; }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .header { background-color: #18181b; border-radius: 12px 12px 0 0; padding: 32px; text-align: center; }
        .header .logo { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }
        .header .logo span { color: #6366f1; }
        .alert-badge { border-radius: 0; padding: 16px 32px; text-align: center; }
        .alert-badge.realtime { background-color: #fef2f2; border: 1px solid #f87171; }
        .alert-badge.daily    { background-color: #fef3c7; border: 1px solid #f59e0b; }
        .alert-badge .icon { font-size: 28px; }
        .alert-badge .title { font-size: 18px; font-weight: 700; margin-top: 6px; }
        .alert-badge.realtime .title { color: #b91c1c; }
        .alert-badge.daily .title    { color: #92400e; }
        .alert-badge .subtitle { font-size: 13px; margin-top: 4px; }
        .alert-badge.realtime .subtitle { color: #dc2626; }
        .alert-badge.daily .subtitle    { color: #b45309; }
        .trigger-info { padding: 10px 32px; background-color: #f0f9ff; border-bottom: 1px solid #bae6fd; text-align: center; font-size: 12px; color: #0369a1; }
        .body { background-color: #ffffff; padding: 32px; }
        .greeting { font-size: 14px; color: #52525b; margin-bottom: 16px; line-height: 1.6; }
        .greeting strong { color: #18181b; }
        .summary-box { background-color: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; display: flex; gap: 24px; }
        .summary-item { text-align: center; flex: 1; }
        .summary-item .num { font-size: 28px; font-weight: 700; color: #f59e0b; }
        .summary-item .label { font-size: 11px; color: #71717a; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-title { font-size: 12px; font-weight: 700; color: #71717a; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead tr { background-color: #f4f4f5; }
        thead th { padding: 10px 12px; text-align: left; font-weight: 600; color: #52525b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e4e4e7; }
        tbody tr { border-bottom: 1px solid #f4f4f5; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 12px; vertical-align: middle; }
        .product-name { font-weight: 600; color: #18181b; }
        .product-sku { font-size: 11px; color: #6366f1; font-family: monospace; margin-top: 2px; }
        .badge-critical { display:inline-block; background-color:#fef2f2; color:#b91c1c; border:1px solid #fecaca; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }
        .badge-low { display:inline-block; background-color:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }
        .stock-num { font-weight: 700; font-size: 14px; }
        .stock-critical { color: #dc2626; }
        .stock-low { color: #d97706; }
        .stock-min { font-size: 11px; color: #a1a1aa; }
        .cta-section { margin-top: 28px; text-align: center; }
        .cta-btn { display:inline-block; background-color:#6366f1; color:#ffffff !important; text-decoration:none; padding:12px 32px; border-radius:8px; font-weight:600; font-size:14px; }
        .cta-note { font-size: 12px; color: #a1a1aa; margin-top: 10px; }
        .info-note { background-color:#eff6ff; border-left:4px solid #6366f1; border-radius:0 6px 6px 0; padding:12px 16px; margin-top:24px; font-size:13px; color:#1e40af; line-height:1.6; }
        .footer { background-color:#f4f4f5; border-top:1px solid #e4e4e7; border-radius:0 0 12px 12px; padding:20px 32px; text-align:center; }
        .footer p { font-size: 12px; color: #a1a1aa; line-height: 1.8; }
        .footer a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="logo">Inven<span>Sys</span></div>
    </div>

    {{-- Alert Badge --}}
    <div class="alert-badge {{ $isRealtime ? 'realtime' : 'daily' }}">
        <div class="icon">{{ $isRealtime ? '🚨' : '⚠️' }}</div>
        <div class="title">{{ $isRealtime ? 'Alert Stok Rendah Real-time' : 'Laporan Harian Stok Rendah' }}</div>
        <div class="subtitle">
            Terdeteksi: {{ now()->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB
        </div>
    </div>

    @if ($isRealtime)
    <div class="trigger-info">
        ⚡ Alert ini dikirim otomatis saat stok produk baru saja turun ke bawah batas minimum
    </div>
    @endif

    {{-- Body --}}
    <div class="body">
        <p class="greeting">
            Halo, <strong>Tim Inventaris</strong>.<br>
            @if ($isRealtime)
                Stok produk berikut <strong>baru saja turun di bawah batas minimum</strong>.
                Segera lakukan restocking untuk menghindari kehabisan stok.
            @else
                Sistem mendeteksi <strong>{{ $items->count() }} produk</strong> yang stoknya
                berada di bawah batas minimum. Segera lakukan restocking.
            @endif
        </p>

        {{-- Summary --}}
        <div class="summary-box">
            <div class="summary-item">
                <div class="num">{{ $items->count() }}</div>
                <div class="label">Total Produk</div>
            </div>
            <div class="summary-item">
                <div class="num">{{ $items->where('qty_available', 0)->count() }}</div>
                <div class="label">Stok Habis</div>
            </div>
            <div class="summary-item">
                <div class="num">{{ $items->unique('warehouse_id')->count() }}</div>
                <div class="label">Gudang Terdampak</div>
            </div>
        </div>

        <div class="section-title">Detail Produk Stok Rendah</div>
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Gudang</th>
                    <th style="text-align:center">Stok Tersedia</th>
                    <th style="text-align:center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                <tr>
                    <td>
                        <div class="product-name">{{ $item->product->name }}</div>
                        <div class="product-sku">{{ $item->product->sku }}</div>
                    </td>
                    <td style="color:#52525b">{{ $item->warehouse->name }}</td>
                    <td style="text-align:center">
                        <span class="stock-num {{ $item->qty_available == 0 ? 'stock-critical' : 'stock-low' }}">
                            {{ $item->qty_available }}
                        </span>
                        <div class="stock-min">min: {{ $item->min_stock }}</div>
                    </td>
                    <td style="text-align:center">
                        @if ($item->qty_available == 0)
                            <span class="badge-critical">Habis</span>
                        @else
                            <span class="badge-low">Rendah</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#a1a1aa;padding:20px">
                        Tidak ada data stok rendah.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="info-note">
            💡 Alert ini dikirim ke <strong>lananuranf@gmail.com</strong> secara otomatis saat stok
            produk turun ke bawah batas minimum. Timezone: <strong>Asia/Jakarta (WIB)</strong>.
        </div>

        <div class="cta-section">
            <a href="{{ config('app.url') }}/inventory?low_stock=1" class="cta-btn">
                Lihat Inventaris Sekarang
            </a>
            <p class="cta-note">Klik tombol di atas untuk melihat detail stok di sistem</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>
            Email ini dikirim otomatis oleh <strong>InvenSys</strong> — Inventory Management System.<br>
            Jangan membalas email ini langsung.<br>
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </p>
    </div>

</div>
</body>
</html>

# 📄 Laporan PDF — InvenSys

Dokumentasi fitur export PDF di InvenSys. Semua laporan digenerate server-side menggunakan **DomPDF (Laravel)** dan dapat didownload langsung dari browser.

---

## 📋 Jenis Laporan PDF

| Laporan | Endpoint | Halaman |
|---|---|---|
| Dashboard | `GET /api/reports/dashboard/pdf` | Dashboard → tombol "Print PDF" |
| Inventaris | `GET /api/reports/inventory/pdf` | Inventaris → tombol "PDF" |
| Penjualan | `GET /api/reports/sales/pdf` | Transaksi → tombol "PDF" |

---

## 📊 Dashboard PDF

**Konten:**
- Header branded InvenSys dengan gradient
- **KPI Cards** — Total Revenue (USD), Total Order, Total Produk, Total Customer
- **Status Order** — Pending, Shipped, Delivered, Canceled
- **Top 5 Produk Terlaris** — dengan rank badge (🥇🥈🥉), revenue dalam USD
- **Penjualan Per Bulan** — bar chart proporsional + total tahunan

**Format:**
- Ukuran kertas: A4 Portrait
- Currency: **USD ($)**
- Timezone: **Asia/Jakarta (WIB)**
- Footer fixed di setiap halaman

---

## 📦 Inventaris PDF

**Konten:**
- Header branded dengan info gudang yang difilter
- **Summary Cards** — Total Item, Stok Rendah, Total Unit Di Tangan
- **Tabel Detail** — SKU, Produk, Kategori, Gudang, Qty Di Tangan, Qty Tersedia, Min Stock, Status (Normal/Rendah)

**Filter tersedia:**
```
GET /api/reports/inventory/pdf?warehouse_id=1
```

**Format:**
- `page-break-inside: avoid` → baris tabel tidak terpotong di tengah halaman
- Badge status: 🟡 **Rendah** / 🟢 **Normal**
- Warna stok: merah-amber (rendah) / hijau (normal)

---

## 💰 Penjualan PDF

**Konten:**
- Header dengan info periode filter
- **Summary Cards** — Total Order, Selesai, Revenue USD, Dibatalkan
- **Filter info strip** — tampil jika ada filter periode aktif
- **Tabel Transaksi** — No. Order, Tanggal, Customer, Gudang, Status badge, Total (USD)
- **Total row** di bawah tabel dengan jumlah semua transaksi

**Filter tersedia:**
```
GET /api/reports/sales/pdf?from=2026-01-01&to=2026-03-31&status=delivered
```

**Format:**
- `page-break-inside: avoid` → baris tidak terpotong saat multi-halaman
- Total row selalu muncul di bawah data terakhir, tidak terpotong
- Status badge berwarna per status

---

## 🎨 Desain

Semua PDF menggunakan design sistem yang konsisten:

```
Font        : DejaVu Sans (DomPDF compatible)
Header      : Gradient #1a1a2e → #0f3460 + aksen merah #e94560
Tabel       : Header gelap #1a1a2e, alternating rows (putih/abu muda)
Footer      : Fixed di bawah, info sistem + timestamp WIB
Currency    : USD ($X,XXX.XX)
Timezone    : Asia/Jakarta (WIB)
Page break  : page-break-inside: avoid di setiap <tr>
```

---

## ⚙️ Konfigurasi DomPDF

File: `config/dompdf.php`

```php
'options' => [
    'isPhpEnabled'     => true,
    'isRemoteEnabled'  => false,
    'defaultPaperSize' => 'a4',
    'defaultFont'      => 'DejaVu Sans',
]
```

---

## 🔐 Akses

| Role | Dashboard PDF | Inventaris PDF | Penjualan PDF |
|:---|:---:|:---:|:---:|
| Admin | ✅ | ✅ | ✅ |
| Manager | ✅ | ✅ | ✅ |
| Staff | ✅ | ✅ | ✅ |
| Viewer | ✅ | ✅ | ✅ |

Semua role bisa export PDF (read-only operation).

---

## 🛠 Cara Extend / Custom Laporan Baru

1. Buat blade view di `resources/views/reports/nama-laporan.blade.php`
2. Tambahkan method di `ReportController.php`:

```php
public function namaLaporanPdf(Request $request)
{
    $data = Model::query()->get();
    $pdf = Pdf::loadView('reports.nama-laporan', compact('data'))
        ->setPaper('a4');
    return $pdf->download('nama-laporan-' . now()->format('Ymd') . '.pdf');
}
```

3. Daftarkan route di `routes/api.php`:

```php
Route::get('/reports/nama-laporan/pdf', [ReportController::class, 'namaLaporanPdf']);
```

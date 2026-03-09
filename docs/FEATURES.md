# ✨ Features Documentation

Dokumentasi lengkap semua fitur InvenSys.

---

## 1. 📊 Dashboard

Halaman utama dengan ringkasan bisnis real-time.

### KPI Cards
- **Total Produk Aktif** — jumlah produk dengan `is_active = true`
- **Total Gudang** — jumlah warehouse aktif
- **Total Pelanggan** — jumlah customer terdaftar
- **Total Karyawan** — jumlah employee aktif
- **Transaksi Pending** — order yang belum diproses
- **Revenue Bulan Ini** — total `total_amount` transaksi `delivered` bulan ini

### Grafik Penjualan
- Line chart 12 bulan terakhir menggunakan Chart.js
- Data: jumlah transaksi `delivered` dan total revenue per bulan

### Alert Stok Rendah
- List produk dengan `quantity < min_quantity`
- Klik langsung menuju halaman inventaris

### Recent Transactions
- 5 transaksi terbaru dengan status badge berwarna
- Link ke detail transaksi

---

## 2. 📦 Manajemen Produk

CRUD lengkap untuk produk.

### Fitur
- List produk dengan **filter** (nama, kategori, status)
- **Sort** berdasarkan kolom apapun (nama, harga, tanggal)
- **Pagination** (15 item per halaman default)
- **Buat produk** baru dengan validasi SKU unik
- **Edit** data produk
- **Soft delete** — produk tidak benar-benar dihapus dari DB

### Form Fields
| Field | Wajib | Keterangan |
|---|---|---|
| Kategori | ✅ | Dropdown dari tabel categories |
| SKU | ✅ | Kode unik produk |
| Nama | ✅ | Nama produk |
| Deskripsi | ❌ | Teks bebas |
| Harga Beli | ✅ | `standard_cost` |
| Harga Jual | ✅ | `list_price` |
| Status Aktif | ✅ | Toggle on/off |

---

## 3. 🏭 Manajemen Inventaris

Kelola stok produk per gudang.

### Fitur
- Lihat stok semua produk di semua gudang
- **Filter per gudang** atau per produk
- **Filter stok rendah** — tampilkan hanya yang di bawah threshold
- **Adjust stok** manual (set quantity dan min_quantity)
- **Transfer antar gudang** — pindahkan stok dari gudang A ke B

### Transfer Stok
1. Pilih produk
2. Pilih gudang asal dan gudang tujuan
3. Masukkan jumlah (tidak boleh melebihi stok tersedia)
4. Transfer berjalan **atomik** dalam 1 database transaction

```
Gudang A: 100 unit → transfer 30 → Gudang A: 70, Gudang B: +30
```

### Alert Stok Rendah
- Visual badge merah pada baris dengan `quantity < min_quantity`
- Bisa di-set threshold per produk per gudang

---

## 4. 🛒 Manajemen Transaksi

Order management dengan state machine lengkap.

### State Machine

```
pending → processing → shipped → delivered
   │           │            │
   └───────────┴────────────┘
               ▼
            canceled
```

| Transisi | Aksi Sistem |
|---|---|
| any → `shipped` | Stok berkurang otomatis per item |
| `shipped` → `canceled` | Stok dikembalikan otomatis |
| any → `delivered` | Final state, tidak bisa diubah |

### Buat Transaksi
- Pilih customer, employee (PIC), warehouse sumber
- Tambah multiple item produk
- Harga per item bisa di-override saat input
- `order_number` digenerate otomatis: `TRX-YYYYMMDD-XXXX`

### Validasi
- Stok harus cukup sebelum bisa di-ship
- Tidak bisa ke status yang tidak valid dalam state machine
- Minimal 1 item per transaksi

---

## 5. 🏢 Manajemen Gudang

CRUD warehouse penyimpanan.

### Fitur
- Buat, edit, hapus gudang
- Set kapasitas gudang
- Aktif/nonaktif gudang
- Lihat total produk yang tersimpan

---

## 6. 👥 Manajemen Pelanggan

CRUD data customer.

### Fields: nama, email, telepon, alamat

---

## 7. 👨‍💼 Manajemen Karyawan

CRUD data employee/sales.

### Fields: nama, email, telepon, jabatan, status aktif

Karyawan bisa dipilih sebagai PIC saat membuat transaksi.

---

## 8. 📄 Laporan & Export

Export data dalam format PDF dan Excel.

### PDF (via DomPDF)
| Laporan | Endpoint | Filter |
|---|---|---|
| Inventaris | `GET /api/reports/inventory/pdf` | — |
| Penjualan | `GET /api/reports/sales/pdf` | `start_date`, `end_date` |
| Dashboard | `GET /api/reports/dashboard/pdf` | — |

### Excel (via Maatwebsite Excel)
| Laporan | Endpoint | Filter |
|---|---|---|
| Inventaris | `GET /api/reports/inventory/excel` | — |
| Penjualan | `GET /api/reports/sales/excel` | `start_date`, `end_date` |

### Import CSV
```bash
php artisan inventory:import /path/to/inventory.csv
```

---

## 9. 📧 Notifikasi Email

Alert otomatis untuk stok rendah.

### Cara Kerja
1. Scheduler jalankan `inventory:send-low-stock-alerts` setiap hari
2. Cek semua produk yang `quantity < min_quantity`
3. Kirim email alert ke admin via **queue** (background)

### Konfigurasi
```php
// routes/console.php
Schedule::command('inventory:send-low-stock-alerts')->daily();
// Atau: ->hourly(), ->everyFifteenMinutes(), dll.
```

---

## 10. 🔐 RBAC — Role Based Access Control

4 role dengan permission berbeda.

| Role | Deskripsi |
|---|---|
| `admin` | Akses penuh ke semua fitur |
| `manager` | Semua fitur kecuali manajemen user |
| `staff` | Buat transaksi, kelola inventaris |
| `viewer` | Read-only, hanya lihat data |

### Implementasi
- **Backend:** Middleware `CheckRole` di route group
- **Frontend:** UI sembunyikan tombol action berdasarkan role
- API tetap enforce role bahkan jika UI di-bypass

---

## 11. 🔑 Authentication & Session

- Token-based via **Laravel Sanctum**
- Token disimpan di `localStorage` frontend
- **Idle timeout** — auto-logout setelah **3 menit** tidak aktif
- Activity reset pada setiap interaksi (mouse, keyboard)
- Token di-revoke di server saat logout

---

## 12. 📱 Responsive Design

| Ukuran Layar | Layout |
|---|---|
| Desktop (1024px+) | Sidebar tetap terbuka, tabel penuh |
| Tablet (768px–1024px) | Sidebar collapsible |
| Mobile (<768px) | Sidebar jadi overlay drawer, tabel jadi card list |

Menggunakan Tailwind CSS breakpoints (`sm:`, `md:`, `lg:`).

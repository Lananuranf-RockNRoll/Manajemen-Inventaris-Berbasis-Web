# 🗄️ Dokumentasi Database

Dokumen ini menjelaskan skema database Sistem Informasi Manajemen Inventaris secara lengkap, mencakup struktur tabel, tipe data, fungsi setiap kolom, dan relasi antar tabel.

---

## Gambaran Umum Database

- **DBMS:** MySQL 8.0
- **Charset:** utf8mb4 (mendukung emoji dan karakter Unicode penuh)
- **Collation:** utf8mb4_unicode_ci
- **Normalisasi:** Bentuk Normal Ketiga (3NF)
- **Jumlah tabel utama:** 9 tabel

---

## Diagram Relasi (ERD)

```
users ─────────────────────────────────────────────────┐
                                                        │ (employee_id FK)
categories ──────────────┐                             │
                          │ (category_id FK)           │
                          ▼                             │
                      products ◄──── inventory          │
                          │         (product_id FK,     │
                          │          warehouse_id FK)   │
                          │                             │
                      transaction_items                 │
                          │ (product_id FK,             │
                          │  transaction_id FK)         │
                          │                             ▼
warehouses ──────────────►transactions ◄─── customers
           (warehouse_id FK)  │
                               │ (warehouse_id FK,
employees ────────────────────►│  employee_id FK,
          (warehouse_id FK)    │  customer_id FK)
```

---

## 1. Tabel `users`

Menyimpan data pengguna sistem beserta informasi autentikasi dan hak akses.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | NOT NULL | — | Nama lengkap pengguna |
| `email` | VARCHAR(100) | NOT NULL | — | Email unik sebagai username |
| `email_verified_at` | TIMESTAMP | NULL | NULL | Waktu verifikasi email |
| `password` | VARCHAR(255) | NOT NULL | — | Password ter-hash (bcrypt) |
| `role` | ENUM | NOT NULL | 'viewer' | Role pengguna: `admin`, `manager`, `staff`, `viewer` |
| `is_active` | TINYINT(1) | NOT NULL | 1 | Status aktif akun (1=aktif, 0=nonaktif) |
| `remember_token` | VARCHAR(100) | NULL | NULL | Token "ingat saya" |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |
| `deleted_at` | TIMESTAMP | NULL | NULL | Soft delete timestamp |

### Index
- `PRIMARY KEY (id)`
- `UNIQUE KEY (email)`

### Relasi
- `users` tidak memiliki foreign key ke tabel lain
- Direferensikan oleh: `employees` (tidak langsung, melalui relasi sistem)

---

## 2. Tabel `categories`

Menyimpan kategori produk/barang untuk pengelompokan inventaris.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | NOT NULL | — | Nama kategori |
| `slug` | VARCHAR(120) | NOT NULL | — | Slug URL-friendly dari nama kategori |
| `description` | TEXT | NULL | NULL | Deskripsi kategori |
| `is_active` | TINYINT(1) | NOT NULL | 1 | Status aktif kategori |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |

### Index
- `PRIMARY KEY (id)`
- `UNIQUE KEY (slug)`

### Relasi
- **One-to-Many** dengan `products`: Satu kategori memiliki banyak produk

---

## 3. Tabel `products`

Menyimpan data produk/barang yang dikelola dalam inventaris.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `sku` | VARCHAR(50) | NOT NULL | — | Stock Keeping Unit — kode unik produk |
| `name` | VARCHAR(255) | NOT NULL | — | Nama produk |
| `description` | TEXT | NULL | NULL | Deskripsi lengkap produk |
| `standard_cost` | DECIMAL(15,2) | NOT NULL | 0.00 | Harga modal/beli produk |
| `list_price` | DECIMAL(15,2) | NOT NULL | 0.00 | Harga jual produk |
| `category_id` | BIGINT UNSIGNED | NOT NULL | — | FK ke tabel `categories` |
| `is_active` | TINYINT(1) | NOT NULL | 1 | Status aktif produk |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |
| `deleted_at` | TIMESTAMP | NULL | NULL | Soft delete timestamp |

### Kolom Komputasi (Accessor)
- `profit_margin` = `list_price - standard_cost`
- `profit_percentage` = `((list_price - standard_cost) / standard_cost) * 100`

### Index
- `PRIMARY KEY (id)`
- `UNIQUE KEY (sku)`
- `INDEX (category_id)`

### Relasi
- **Many-to-One** dengan `categories`: Setiap produk memiliki satu kategori
- **One-to-Many** dengan `inventory`: Satu produk memiliki banyak record stok (per gudang)
- **One-to-Many** dengan `transaction_items`: Satu produk bisa ada di banyak item transaksi

---

## 4. Tabel `warehouses`

Menyimpan data gudang tempat penyimpanan barang inventaris.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | NOT NULL | — | Nama gudang |
| `region` | VARCHAR(100) | NULL | NULL | Wilayah/region gudang |
| `country` | VARCHAR(100) | NULL | NULL | Negara lokasi gudang |
| `state` | VARCHAR(100) | NULL | NULL | Provinsi/state lokasi gudang |
| `city` | VARCHAR(100) | NULL | NULL | Kota lokasi gudang |
| `postal_code` | VARCHAR(20) | NULL | NULL | Kode pos |
| `address` | TEXT | NULL | NULL | Alamat lengkap gudang |
| `phone` | VARCHAR(30) | NULL | NULL | Nomor telepon gudang |
| `email` | VARCHAR(100) | NULL | NULL | Email kontak gudang |
| `is_active` | TINYINT(1) | NOT NULL | 1 | Status aktif gudang |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |

### Relasi
- **One-to-Many** dengan `inventory`: Satu gudang memiliki banyak record stok
- **One-to-Many** dengan `employees`: Satu gudang memiliki banyak karyawan
- **One-to-Many** dengan `transactions`: Satu gudang bisa memiliki banyak transaksi

---

## 5. Tabel `inventory`

Menyimpan data stok setiap produk di setiap gudang. Tabel ini adalah inti dari sistem manajemen stok.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `product_id` | BIGINT UNSIGNED | NOT NULL | — | FK ke tabel `products` |
| `warehouse_id` | BIGINT UNSIGNED | NOT NULL | — | FK ke tabel `warehouses` |
| `qty_on_hand` | INT | NOT NULL | 0 | Jumlah stok fisik aktual di gudang |
| `qty_reserved` | INT | NOT NULL | 0 | Jumlah stok yang direservasi untuk order |
| `min_stock` | INT | NOT NULL | 10 | Batas minimum stok (trigger low stock alert) |
| `max_stock` | INT | NOT NULL | 1000 | Batas maksimum kapasitas stok |
| `last_restocked_at` | TIMESTAMP | NULL | NULL | Waktu terakhir stok ditambah |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |

### Kolom Komputasi (Accessor)
- `qty_available` = `qty_on_hand - qty_reserved`
- `is_low_stock` = `qty_available <= min_stock`

### Index
- `PRIMARY KEY (id)`
- `UNIQUE KEY (product_id, warehouse_id)` — satu produk hanya boleh punya satu record per gudang

### Relasi
- **Many-to-One** dengan `products`
- **Many-to-One** dengan `warehouses`

---

## 6. Tabel `customers`

Menyimpan data pelanggan yang melakukan pembelian/transaksi.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | NOT NULL | — | Nama lengkap customer |
| `email` | VARCHAR(100) | NULL | NULL | Email customer |
| `phone` | VARCHAR(30) | NULL | NULL | Nomor telepon |
| `address` | TEXT | NULL | NULL | Alamat lengkap |
| `credit_limit` | DECIMAL(15,2) | NOT NULL | 0.00 | Batas kredit maksimum |
| `credit_used` | DECIMAL(15,2) | NOT NULL | 0.00 | Kredit yang sudah digunakan |
| `status` | ENUM | NOT NULL | 'active' | Status: `active`, `inactive`, `blacklisted` |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |
| `deleted_at` | TIMESTAMP | NULL | NULL | Soft delete timestamp |

### Kolom Komputasi (Accessor)
- `credit_available` = `credit_limit - credit_used`

### Relasi
- **One-to-Many** dengan `transactions`

---

## 7. Tabel `employees`

Menyimpan data karyawan yang dapat ditugaskan ke gudang tertentu.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | NOT NULL | — | Nama lengkap karyawan |
| `email` | VARCHAR(100) | NOT NULL | — | Email karyawan (unique) |
| `phone` | VARCHAR(30) | NULL | NULL | Nomor telepon |
| `job_title` | VARCHAR(100) | NULL | NULL | Jabatan/posisi |
| `department` | VARCHAR(100) | NULL | NULL | Departemen |
| `hire_date` | DATE | NULL | NULL | Tanggal bergabung |
| `warehouse_id` | BIGINT UNSIGNED | NULL | NULL | FK ke tabel `warehouses` (nullable) |
| `is_active` | TINYINT(1) | NOT NULL | 1 | Status aktif karyawan |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |
| `deleted_at` | TIMESTAMP | NULL | NULL | Soft delete timestamp |

### Relasi
- **Many-to-One** dengan `warehouses` (nullable — karyawan bisa tidak terikat gudang)
- **One-to-Many** dengan `transactions`

---

## 8. Tabel `transactions`

Menyimpan data order/transaksi penjualan dari customer.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `order_number` | VARCHAR(20) | NOT NULL | — | Nomor order unik (format: ORD-YYYYMMDD-XXXXX) |
| `customer_id` | BIGINT UNSIGNED | NOT NULL | — | FK ke tabel `customers` |
| `warehouse_id` | BIGINT UNSIGNED | NOT NULL | — | FK ke tabel `warehouses` |
| `employee_id` | BIGINT UNSIGNED | NULL | NULL | FK ke tabel `employees` (nullable) |
| `status` | ENUM | NOT NULL | 'pending' | Status: `pending`, `processing`, `shipped`, `delivered`, `canceled` |
| `order_date` | DATE | NOT NULL | — | Tanggal order dibuat |
| `shipped_date` | DATE | NULL | NULL | Tanggal pengiriman |
| `total_amount` | DECIMAL(15,2) | NOT NULL | 0.00 | Total nilai transaksi |
| `notes` | TEXT | NULL | NULL | Catatan tambahan |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |

### Alur Status Transaksi

```
pending ──► processing ──► shipped ──► delivered
   │              │            │
   └──────────────┴────────────┴──► canceled
```

### Relasi
- **Many-to-One** dengan `customers`
- **Many-to-One** dengan `warehouses`
- **Many-to-One** dengan `employees` (nullable)
- **One-to-Many** dengan `transaction_items`

---

## 9. Tabel `transaction_items`

Menyimpan detail item (produk) yang ada dalam setiap transaksi.

### Struktur Kolom

| Kolom | Tipe Data | Null | Default | Deskripsi |
|-------|-----------|------|---------|-----------|
| `id` | BIGINT UNSIGNED | NOT NULL | AUTO_INCREMENT | Primary key |
| `transaction_id` | BIGINT UNSIGNED | NOT NULL | — | FK ke tabel `transactions` |
| `product_id` | BIGINT UNSIGNED | NOT NULL | — | FK ke tabel `products` |
| `quantity` | INT | NOT NULL | — | Jumlah unit yang dipesan |
| `unit_price` | DECIMAL(15,2) | NOT NULL | — | Harga per unit saat transaksi |
| `created_at` | TIMESTAMP | NULL | NULL | Waktu data dibuat |
| `updated_at` | TIMESTAMP | NULL | NULL | Waktu data diperbarui |

### Kolom Komputasi (Accessor)
- `subtotal` = `quantity * unit_price`

### Relasi
- **Many-to-One** dengan `transactions`
- **Many-to-One** dengan `products`

---

## Tabel Tambahan

### `personal_access_tokens` (dibuat otomatis oleh Laravel Sanctum)

| Kolom | Deskripsi |
|-------|-----------|
| `id` | Primary key |
| `tokenable_type` | Tipe model (App\Models\User) |
| `tokenable_id` | ID user pemilik token |
| `name` | Nama token |
| `token` | Hash token (SHA-256) |
| `abilities` | Kemampuan token (JSON) |
| `last_used_at` | Waktu terakhir digunakan |
| `expires_at` | Waktu kedaluwarsa token |
| `created_at` | Waktu dibuat |
| `updated_at` | Waktu diperbarui |

---

## Ringkasan Relasi Antar Tabel

| Tabel | Berelasi Dengan | Jenis Relasi |
|-------|----------------|--------------|
| `categories` | `products` | One-to-Many |
| `products` | `categories` | Many-to-One |
| `products` | `inventory` | One-to-Many |
| `products` | `transaction_items` | One-to-Many |
| `warehouses` | `inventory` | One-to-Many |
| `warehouses` | `employees` | One-to-Many |
| `warehouses` | `transactions` | One-to-Many |
| `customers` | `transactions` | One-to-Many |
| `employees` | `transactions` | One-to-Many |
| `transactions` | `transaction_items` | One-to-Many |
| `inventory` | `products` | Many-to-One |
| `inventory` | `warehouses` | Many-to-One |

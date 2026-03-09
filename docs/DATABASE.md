# 🗄️ Database Documentation

InvenSys menggunakan MySQL 8.0 dengan 9 tabel utama.

---

## ERD (Entity Relationship)

```
users ──────────────── (akun login)

categories
  └── products ──────── (produk punya 1 kategori)

warehouses
  └── inventory ─────── (stok: product × warehouse, unique)
        └── products

customers ──┐
employees ──┤── transactions ──── transaction_items ──── products
warehouses ─┘
```

---

## Tabel Detail

### `users`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar(255) | |
| email | varchar(255) UNIQUE | |
| password | varchar(255) | bcrypt hashed |
| role | enum | `admin\|manager\|staff\|viewer` |
| is_active | boolean | default true |
| email_verified_at | timestamp | nullable |
| deleted_at | timestamp | SoftDelete |
| created_at, updated_at | timestamp | |

### `categories`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar(100) UNIQUE | |
| description | text | nullable |
| created_at, updated_at | timestamp | |

### `products`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| category_id | bigint FK → categories | |
| sku | varchar(50) UNIQUE | Kode produk |
| name | varchar(200) | |
| description | text | nullable |
| standard_cost | decimal(12,2) | Harga beli |
| list_price | decimal(12,2) | Harga jual |
| is_active | boolean | default true |
| deleted_at | timestamp | SoftDelete |
| created_at, updated_at | timestamp | |

### `warehouses`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar(100) | |
| location | varchar(255) | nullable |
| capacity | integer | nullable |
| is_active | boolean | default true |
| created_at, updated_at | timestamp | |

### `inventory`

Pivot table stok per produk per gudang.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_id | bigint FK → products | |
| warehouse_id | bigint FK → warehouses | |
| quantity | integer | Stok aktual |
| min_quantity | integer | Threshold alert (default 10) |
| created_at, updated_at | timestamp | |

**Unique constraint:** `(product_id, warehouse_id)`

### `employees`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar(100) | |
| email | varchar(100) | nullable |
| phone | varchar(20) | nullable |
| position | varchar(100) | nullable |
| is_active | boolean | default true |
| created_at, updated_at | timestamp | |

### `customers`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar(100) | |
| email | varchar(100) | nullable |
| phone | varchar(20) | nullable |
| address | text | nullable |
| created_at, updated_at | timestamp | |

### `transactions`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_number | varchar(20) UNIQUE | Format: TRX-YYYYMMDD-XXXX |
| customer_id | bigint FK → customers | |
| employee_id | bigint FK → employees | nullable |
| warehouse_id | bigint FK → warehouses | Sumber stok |
| status | enum | `pending\|processing\|shipped\|delivered\|canceled` |
| order_date | date | |
| shipped_date | date | nullable |
| total_amount | decimal(14,2) | |
| notes | text | nullable |
| created_at, updated_at | timestamp | |

### `transaction_items`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| transaction_id | bigint FK → transactions | |
| product_id | bigint FK → products | |
| quantity | integer | |
| unit_price | decimal(12,2) | Harga saat order |
| subtotal | decimal(14,2) | quantity × unit_price |
| created_at, updated_at | timestamp | |

---

## Transaction State Machine

```
                  ┌─────────────────────────┐
                  ▼                         │
pending ──► processing ──► shipped ──► delivered
  │              │             │
  └──────────────┴─────────────┘
                 ▼
              canceled
```

**Event-driven inventory:**
- `shipped` → `OrderShipped` event → `DeductInventoryOnShip` listener (kurangi stok)
- `canceled` (dari shipped) → `OrderCanceled` event → `RestoreInventoryOnCancel` (kembalikan stok)

---

## Seeders

| Seeder | Jumlah Data |
|---|---|
| UserSeeder | 4 user (admin, manager, staff, viewer) |
| CategorySeeder | ~10 kategori |
| WarehouseSeeder | 3 gudang |
| ProductSeeder | ~50 produk |
| CustomerSeeder | ~20 pelanggan |
| EmployeeSeeder | ~10 karyawan |
| InventorySeeder | Stok awal semua produk × semua gudang |

```bash
# Jalankan seeder
php artisan db:seed

# Reset + seed ulang
php artisan migrate:fresh --seed

# Docker
docker-compose exec php php artisan migrate:fresh --seed
```

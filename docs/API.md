# 🔌 Dokumentasi REST API

Dokumen ini adalah referensi lengkap seluruh endpoint REST API Sistem Informasi Manajemen Inventaris.

---

## Informasi Umum

| Properti | Nilai |
|----------|-------|
| **Base URL** | `http://127.0.0.1:8000/api` |
| **Format** | JSON |
| **Autentikasi** | Bearer Token (Laravel Sanctum) |
| **Header Wajib** | `Content-Type: application/json`, `Accept: application/json` |

### Header Request

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your_token_here}
```

---

## 1. Authentication

### POST `/auth/login`

Login pengguna dan mendapatkan Bearer Token.

- **Akses:** Public (tanpa token)

**Request Body:**
```json
{
  "email": "admin@inventory.test",
  "password": "password"
}
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@inventory.test",
      "role": "admin",
      "is_active": true
    },
    "token": "1|uabFZCfN5BimiuA2iQtXvXgAWUu2T7eLVZ1SZ4bF"
  }
}
```

**Response Error (401):**
```json
{
  "status": "error",
  "message": "Email atau password salah"
}
```

---

### POST `/auth/logout`

Logout pengguna dan invalidasi token aktif.

- **Akses:** Authenticated

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Logout berhasil"
}
```

---

### GET `/auth/me`

Mendapatkan data pengguna yang sedang login.

- **Akses:** Authenticated

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@inventory.test",
    "role": "admin",
    "is_active": true,
    "created_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

---

## 2. Dashboard

### GET `/dashboard/summary`

Mendapatkan ringkasan KPI untuk ditampilkan di dashboard.

- **Akses:** Authenticated (semua role)

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": {
    "total_revenue": 15820000000,
    "total_orders": 1250,
    "shipped_orders": 430,
    "pending_orders": 87,
    "canceled_orders": 23,
    "total_products": 275,
    "total_warehouses": 9,
    "total_customers": 400,
    "low_stock_alerts": 14,
    "top_category": "Electronics",
    "revenue_this_month": 2350000000
  }
}
```

---

### GET `/dashboard/top-products`

Mendapatkan daftar 10 produk terlaris berdasarkan revenue.

- **Akses:** Authenticated (semua role)

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 42,
      "name": "Laptop Pro X1",
      "sku": "LAP-001",
      "category_name": "Electronics",
      "total_qty": 320,
      "total_revenue": 1280000000
    }
  ]
}
```

---

### GET `/dashboard/low-stock`

Mendapatkan daftar produk dengan stok di bawah batas minimum.

- **Akses:** Authenticated (semua role)

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": [
    {
      "inventory_id": 15,
      "product_name": "USB-C Cable 2m",
      "warehouse_name": "Gudang Jakarta Utara",
      "qty_available": 3,
      "min_stock": 10
    }
  ]
}
```

---

## 3. Products (Barang)

### GET `/products`

Mendapatkan daftar produk dengan pagination, pencarian, dan filter.

- **Akses:** Authenticated (semua role)

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| `page` | integer | Nomor halaman (default: 1) |
| `per_page` | integer | Jumlah data per halaman (default: 15) |
| `search` | string | Pencarian berdasarkan nama atau SKU |
| `category_id` | integer | Filter berdasarkan ID kategori |
| `active` | boolean | Filter berdasarkan status aktif |

**Contoh Request:**
```
GET /api/products?page=1&per_page=15&search=laptop&category_id=2
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 42,
      "sku": "LAP-001",
      "name": "Laptop Pro X1",
      "description": "Laptop untuk kebutuhan profesional",
      "standard_cost": "3500000.00",
      "list_price": "4200000.00",
      "profit_margin": 700000,
      "profit_percentage": 20,
      "is_active": true,
      "category": {
        "id": 2,
        "name": "Electronics"
      },
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 19,
    "per_page": 15,
    "total": 275
  }
}
```

---

### POST `/products`

Membuat produk baru.

- **Akses:** Staff, Manager, Admin

**Request Body:**
```json
{
  "sku": "LAP-002",
  "name": "Laptop Business B2",
  "description": "Laptop ringan untuk pebisnis",
  "standard_cost": 4000000,
  "list_price": 5000000,
  "category_id": 2,
  "is_active": true
}
```

**Validasi:**
- `sku`: required, string, unique
- `name`: required, string, max:255
- `standard_cost`: required, numeric, min:0
- `list_price`: required, numeric, min:0
- `category_id`: required, exists:categories,id

**Response Sukses (201):**
```json
{
  "status": "success",
  "message": "Produk berhasil ditambahkan",
  "data": {
    "id": 276,
    "sku": "LAP-002",
    "name": "Laptop Business B2",
    "standard_cost": "4000000.00",
    "list_price": "5000000.00",
    "category": { "id": 2, "name": "Electronics" },
    "is_active": true
  }
}
```

---

### GET `/products/{id}`

Mendapatkan detail satu produk.

- **Akses:** Authenticated (semua role)

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": {
    "id": 42,
    "sku": "LAP-001",
    "name": "Laptop Pro X1",
    "description": "Laptop untuk kebutuhan profesional",
    "standard_cost": "3500000.00",
    "list_price": "4200000.00",
    "profit_margin": 700000,
    "profit_percentage": 20,
    "is_active": true,
    "category": { "id": 2, "name": "Electronics" }
  }
}
```

---

### PUT `/products/{id}`

Memperbarui data produk.

- **Akses:** Manager, Admin

**Request Body:** (sama dengan POST, semua field opsional kecuali validasi)
```json
{
  "name": "Laptop Pro X1 Updated",
  "list_price": 4500000
}
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Produk berhasil diperbarui",
  "data": { ... }
}
```

---

### DELETE `/products/{id}`

Menghapus produk (soft delete).

- **Akses:** Admin only

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Produk berhasil dihapus"
}
```

---

## 4. Categories (Kategori)

### GET `/categories`

Mendapatkan daftar semua kategori.

- **Akses:** Authenticated

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "Produk elektronik",
      "is_active": true
    }
  ],
  "meta": { "total": 5, "current_page": 1, "last_page": 1, "per_page": 50 }
}
```

---

### POST `/categories`

Membuat kategori baru.

- **Akses:** Staff, Manager, Admin

**Request Body:**
```json
{
  "name": "Office Supplies",
  "description": "Perlengkapan kantor",
  "is_active": true
}
```

**Response Sukses (201):**
```json
{
  "status": "success",
  "message": "Kategori berhasil ditambahkan",
  "data": {
    "id": 6,
    "name": "Office Supplies",
    "slug": "office-supplies",
    "description": "Perlengkapan kantor",
    "is_active": true
  }
}
```

---

### PUT `/categories/{id}`

Memperbarui kategori.

- **Akses:** Manager, Admin

---

### DELETE `/categories/{id}`

Menghapus kategori.

- **Akses:** Admin only

---

## 5. Warehouses (Gudang)

### GET `/warehouses`

Mendapatkan daftar semua gudang.

- **Akses:** Authenticated

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Gudang Jakarta Utara",
      "region": "Asia Pacific",
      "country": "Indonesia",
      "state": "DKI Jakarta",
      "city": "Jakarta Utara",
      "postal_code": "14350",
      "address": "Jl. Industri No.1",
      "phone": "+62-21-1234567",
      "is_active": true
    }
  ]
}
```

---

### POST `/warehouses`

Membuat gudang baru.

- **Akses:** Staff, Manager, Admin

**Request Body:**
```json
{
  "name": "Gudang Surabaya",
  "region": "Java",
  "country": "Indonesia",
  "state": "Jawa Timur",
  "city": "Surabaya",
  "postal_code": "60111",
  "address": "Jl. Raya Industri No.99",
  "phone": "+62-31-9876543",
  "is_active": true
}
```

---

### PUT `/warehouses/{id}` — Memperbarui gudang — **Akses:** Manager, Admin

### DELETE `/warehouses/{id}` — Menghapus gudang — **Akses:** Admin only

---

## 6. Inventory (Stok)

### GET `/inventory`

Mendapatkan daftar stok dengan filter.

- **Akses:** Authenticated

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| `warehouse_id` | integer | Filter berdasarkan gudang |
| `low_stock` | boolean | Tampilkan hanya stok rendah |
| `page` | integer | Nomor halaman |
| `per_page` | integer | Jumlah data per halaman |

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 15,
      "product_id": 42,
      "warehouse_id": 1,
      "qty_on_hand": 5,
      "qty_reserved": 2,
      "qty_available": 3,
      "min_stock": 10,
      "max_stock": 500,
      "is_low_stock": true,
      "product": { "id": 42, "name": "Laptop Pro X1", "sku": "LAP-001" },
      "warehouse": { "id": 1, "name": "Gudang Jakarta Utara" }
    }
  ]
}
```

---

### PUT `/inventory/{id}`

Memperbarui data stok manual.

- **Akses:** Manager, Admin

**Request Body:**
```json
{
  "qty_on_hand": 100,
  "min_stock": 15,
  "max_stock": 500
}
```

---

### POST `/inventory/transfer`

Transfer stok dari satu gudang ke gudang lain.

- **Akses:** Manager, Admin

**Request Body:**
```json
{
  "product_id": 42,
  "from_warehouse_id": 1,
  "to_warehouse_id": 3,
  "quantity": 20
}
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Transfer stok berhasil. 20 unit Laptop Pro X1 dipindahkan dari Gudang Jakarta Utara ke Gudang Bandung."
}
```

**Response Error (422):**
```json
{
  "status": "error",
  "message": "Stok tidak mencukupi. Stok tersedia: 3, diminta: 20"
}
```

---

## 7. Customers

### GET `/customers`

Mendapatkan daftar customer.

- **Akses:** Authenticated

**Query Parameters:** `page`, `per_page`, `search`, `status`

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "PT. Maju Bersama",
      "email": "procurement@majubersama.co.id",
      "phone": "+62-21-5551234",
      "credit_limit": "50000000.00",
      "credit_used": "12000000.00",
      "credit_available": 38000000,
      "status": "active"
    }
  ]
}
```

---

### POST `/customers`

Membuat customer baru.

- **Akses:** Staff, Manager, Admin

**Request Body:**
```json
{
  "name": "CV. Sejahtera Abadi",
  "email": "order@sejahtera.com",
  "phone": "+62-22-7778888",
  "address": "Jl. Merdeka No.10, Bandung",
  "credit_limit": 25000000,
  "status": "active"
}
```

---

### PUT `/customers/{id}` — Memperbarui customer — **Akses:** Manager, Admin

### DELETE `/customers/{id}` — Menghapus customer — **Akses:** Admin only

---

## 8. Employees (Karyawan)

### GET `/employees`

Mendapatkan daftar karyawan.

- **Akses:** Authenticated

**Query Parameters:** `page`, `per_page`, `search`, `warehouse_id`

---

### POST `/employees`

Membuat data karyawan baru.

- **Akses:** Staff, Manager, Admin

**Request Body:**
```json
{
  "name": "Budi Santoso",
  "email": "budi.santoso@perusahaan.com",
  "phone": "+62-812-3456789",
  "job_title": "Warehouse Supervisor",
  "department": "Warehouse Operations",
  "warehouse_id": 1,
  "hire_date": "2024-03-01",
  "is_active": true
}
```

---

### PUT `/employees/{id}` — Memperbarui karyawan — **Akses:** Manager, Admin

### DELETE `/employees/{id}` — Menghapus karyawan — **Akses:** Admin only

---

## 9. Transactions (Transaksi)

### GET `/transactions`

Mendapatkan daftar transaksi.

- **Akses:** Authenticated

**Query Parameters:**

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| `page` | integer | Nomor halaman |
| `status` | string | Filter status: pending, processing, shipped, delivered, canceled |
| `from` | date | Filter tanggal mulai (YYYY-MM-DD) |
| `to` | date | Filter tanggal akhir (YYYY-MM-DD) |
| `customer_id` | integer | Filter berdasarkan customer |

---

### POST `/transactions`

Membuat order baru.

- **Akses:** Staff, Manager, Admin

**Request Body:**
```json
{
  "customer_id": 1,
  "warehouse_id": 1,
  "notes": "Mohon dikemas dengan bubble wrap",
  "items": [
    {
      "product_id": 42,
      "quantity": 2,
      "unit_price": 4200000
    },
    {
      "product_id": 55,
      "quantity": 5,
      "unit_price": 85000
    }
  ]
}
```

**Response Sukses (201):**
```json
{
  "status": "success",
  "message": "Order berhasil dibuat",
  "data": {
    "id": 1251,
    "order_number": "ORD-20250601-00001",
    "status": "pending",
    "order_date": "2025-06-01",
    "total_amount": "8825000.00",
    "customer": { "id": 1, "name": "PT. Maju Bersama" },
    "warehouse": { "id": 1, "name": "Gudang Jakarta Utara" },
    "items": [
      {
        "product": { "id": 42, "name": "Laptop Pro X1" },
        "quantity": 2,
        "unit_price": "4200000.00",
        "subtotal": 8400000
      }
    ]
  }
}
```

---

### GET `/transactions/{id}`

Mendapatkan detail satu transaksi beserta seluruh item-nya.

- **Akses:** Authenticated

---

### PATCH `/transactions/{id}/status`

Memperbarui status transaksi.

- **Akses:** Manager, Admin

**Request Body:**
```json
{
  "status": "processing"
}
```

**Alur Status yang Valid:**
- `pending` → `processing` atau `canceled`
- `processing` → `shipped` atau `canceled`
- `shipped` → `delivered` atau `canceled`
- `delivered` → (final, tidak dapat diubah)
- `canceled` → (final, tidak dapat diubah)

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Status transaksi berhasil diperbarui menjadi processing",
  "data": {
    "id": 1251,
    "order_number": "ORD-20250601-00001",
    "status": "processing"
  }
}
```

**Response Error — Status tidak valid (422):**
```json
{
  "status": "error",
  "message": "Perubahan status dari 'delivered' ke 'processing' tidak diizinkan"
}
```

---

## Ringkasan Endpoint

| Method | Endpoint | Deskripsi | Akses Minimum |
|--------|----------|-----------|---------------|
| POST | `/auth/login` | Login | Public |
| POST | `/auth/logout` | Logout | Auth |
| GET | `/auth/me` | Data pengguna aktif | Auth |
| GET | `/dashboard/summary` | KPI dashboard | Auth |
| GET | `/dashboard/top-products` | Produk terlaris | Auth |
| GET | `/dashboard/low-stock` | Stok rendah | Auth |
| GET | `/products` | Daftar produk | Auth |
| POST | `/products` | Tambah produk | Staff |
| GET | `/products/{id}` | Detail produk | Auth |
| PUT | `/products/{id}` | Edit produk | Manager |
| DELETE | `/products/{id}` | Hapus produk | Admin |
| GET | `/categories` | Daftar kategori | Auth |
| POST | `/categories` | Tambah kategori | Staff |
| PUT | `/categories/{id}` | Edit kategori | Manager |
| DELETE | `/categories/{id}` | Hapus kategori | Admin |
| GET | `/warehouses` | Daftar gudang | Auth |
| POST | `/warehouses` | Tambah gudang | Staff |
| PUT | `/warehouses/{id}` | Edit gudang | Manager |
| DELETE | `/warehouses/{id}` | Hapus gudang | Admin |
| GET | `/inventory` | Daftar stok | Auth |
| PUT | `/inventory/{id}` | Update stok | Manager |
| POST | `/inventory/transfer` | Transfer stok | Manager |
| GET | `/customers` | Daftar customer | Auth |
| POST | `/customers` | Tambah customer | Staff |
| PUT | `/customers/{id}` | Edit customer | Manager |
| DELETE | `/customers/{id}` | Hapus customer | Admin |
| GET | `/employees` | Daftar karyawan | Auth |
| POST | `/employees` | Tambah karyawan | Staff |
| PUT | `/employees/{id}` | Edit karyawan | Manager |
| DELETE | `/employees/{id}` | Hapus karyawan | Admin |
| GET | `/transactions` | Daftar transaksi | Auth |
| POST | `/transactions` | Buat order | Staff |
| GET | `/transactions/{id}` | Detail transaksi | Auth |
| PATCH | `/transactions/{id}/status` | Update status | Manager |

# 📡 API Reference

InvenSys REST API — Base URL: `http://localhost/api`

Semua request dan response menggunakan **JSON**. Authentication via **Bearer Token** (Laravel Sanctum).

---

## Authentication

### Login

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@inventory.test",
  "password": "password"
}
```

**Response 200:**
```json
{
  "message": "Login berhasil.",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@inventory.test",
      "role": "admin",
      "is_active": true
    },
    "token": "1|abc123xyz..."
  }
}
```

### Logout

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

### Get Current User

```http
GET /api/auth/me
Authorization: Bearer {token}
```

---

## Header Wajib (Semua Endpoint Terproteksi)

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

---

## Dashboard

```http
GET /api/dashboard
```

**Response:**
```json
{
  "data": {
    "total_products": 120,
    "total_warehouses": 3,
    "total_customers": 45,
    "total_employees": 12,
    "pending_transactions": 5,
    "monthly_revenue": 15000000,
    "low_stock_products": [...],
    "recent_transactions": [...],
    "monthly_sales_chart": [...]
  }
}
```

---

## Products

### List Produk
```http
GET /api/products?filter[name]=laptop&filter[category_id]=1&sort=-created_at&page=1&per_page=15
```

**Query params:**
- `filter[name]` — search nama produk
- `filter[category_id]` — filter per kategori
- `filter[is_active]` — filter aktif (1/0)
- `sort` — kolom sort, prefix `-` untuk DESC (contoh: `-list_price`)
- `page`, `per_page` — pagination

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "sku": "PRD-001",
      "name": "Laptop Lenovo ThinkPad",
      "description": "Business laptop",
      "standard_cost": 8000000,
      "list_price": 12000000,
      "is_active": true,
      "category": { "id": 1, "name": "Electronics" }
    }
  ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 120 }
}
```

### Get Produk
```http
GET /api/products/{id}
```

### Buat Produk
```http
POST /api/products

{
  "category_id": 1,
  "sku": "PRD-002",
  "name": "Mouse Logitech MX Master",
  "description": "Wireless ergonomic mouse",
  "standard_cost": 150000,
  "list_price": 250000,
  "is_active": true
}
```

### Update Produk
```http
PUT /api/products/{id}

{ "name": "Mouse Logitech MX Master 3", "list_price": 280000 }
```

### Hapus Produk (Soft Delete)
```http
DELETE /api/products/{id}
```

---

## Categories

```http
GET    /api/categories
POST   /api/categories     { "name": "Electronics", "description": "..." }
GET    /api/categories/{id}
PUT    /api/categories/{id}
DELETE /api/categories/{id}
```

---

## Warehouses

```http
GET    /api/warehouses
POST   /api/warehouses
GET    /api/warehouses/{id}
PUT    /api/warehouses/{id}
DELETE /api/warehouses/{id}
```

**Body:**
```json
{
  "name": "Gudang Jakarta",
  "location": "Jakarta Selatan",
  "capacity": 1000,
  "is_active": true
}
```

---

## Inventory

### List Inventaris
```http
GET /api/inventory?filter[warehouse_id]=1&filter[low_stock]=true&page=1
```

**Query params:**
- `filter[warehouse_id]` — filter per gudang
- `filter[product_id]` — filter per produk
- `filter[low_stock]` — `true` = hanya tampilkan stok di bawah minimum

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "quantity": 5,
      "min_quantity": 10,
      "product": { "id": 1, "name": "Laptop Lenovo", "sku": "PRD-001" },
      "warehouse": { "id": 1, "name": "Gudang Jakarta" }
    }
  ]
}
```

### Adjust Stok
```http
PATCH /api/inventory/{id}

{
  "quantity": 50,
  "min_quantity": 10
}
```

### Transfer Stok Antar Gudang
```http
POST /api/inventory/transfer

{
  "product_id": 1,
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "quantity": 10,
  "notes": "Transfer untuk memenuhi demand Jakarta"
}
```

**Validasi:**
- `quantity` harus > 0
- `from_warehouse_id` != `to_warehouse_id`
- Stok di gudang asal harus mencukupi

---

## Transactions

### List Transaksi
```http
GET /api/transactions?filter[status]=pending&filter[customer_id]=1&sort=-order_date
```

**Status valid:** `pending | processing | shipped | delivered | canceled`

### Buat Transaksi
```http
POST /api/transactions

{
  "customer_id": 1,
  "employee_id": 2,
  "warehouse_id": 1,
  "order_date": "2026-03-10",
  "notes": "Urgent order",
  "items": [
    { "product_id": 1, "quantity": 2, "unit_price": 12000000 },
    { "product_id": 3, "quantity": 5, "unit_price": 250000 }
  ]
}
```

**Response 201:**
```json
{
  "message": "Transaksi berhasil dibuat.",
  "data": {
    "id": 15,
    "order_number": "TRX-20260310-0015",
    "status": "pending",
    "total_amount": 25250000,
    "items": [...]
  }
}
```

### Update Status Transaksi
```http
PATCH /api/transactions/{id}/status

{ "status": "processing" }
```

**State machine yang valid:**
```
pending     → processing, canceled
processing  → shipped, canceled
shipped     → delivered, canceled  ← stok dikembalikan otomatis
delivered   → (final, tidak bisa diubah)
canceled    → (final)
```

### Detail Transaksi
```http
GET /api/transactions/{id}
```

---

## Employees

```http
GET    /api/employees
POST   /api/employees
GET    /api/employees/{id}
PUT    /api/employees/{id}
DELETE /api/employees/{id}
```

**Body:**
```json
{
  "name": "Budi Santoso",
  "email": "budi@company.com",
  "phone": "08123456789",
  "position": "Sales Manager",
  "is_active": true
}
```

---

## Customers

```http
GET    /api/customers
POST   /api/customers
GET    /api/customers/{id}
PUT    /api/customers/{id}
DELETE /api/customers/{id}
```

**Body:**
```json
{
  "name": "PT Maju Bersama",
  "email": "info@majubersama.co.id",
  "phone": "021-5551234",
  "address": "Jl. Sudirman No. 1, Jakarta"
}
```

---

## Reports

```http
# PDF
GET /api/reports/inventory/pdf
GET /api/reports/sales/pdf?start_date=2026-01-01&end_date=2026-03-31
GET /api/reports/dashboard/pdf

# Excel
GET /api/reports/inventory/excel
GET /api/reports/sales/excel?start_date=2026-01-01&end_date=2026-03-31
```

Response: File download (PDF/xlsx)

---

## Error Responses

| Status | Keterangan |
|---|---|
| 401 | Token tidak ada atau expired |
| 403 | Role tidak punya akses |
| 404 | Data tidak ditemukan |
| 422 | Validasi gagal |
| 500 | Server error |

**Format 422:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "quantity": ["The quantity must be at least 1."]
  }
}
```

**Format 403:**
```json
{ "message": "Akses ditolak. Role Anda tidak memiliki izin." }
```

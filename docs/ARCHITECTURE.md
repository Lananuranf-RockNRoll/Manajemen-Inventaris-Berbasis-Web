# 🏗️ Arsitektur Sistem

Dokumen ini menjelaskan arsitektur keseluruhan Sistem Informasi Manajemen Inventaris, mencakup struktur komponen, alur komunikasi, dan desain teknis yang digunakan.

---

## 1. Gambaran Umum Arsitektur

Sistem ini menggunakan arsitektur **Decoupled (Frontend-Backend Terpisah)**, di mana frontend dan backend merupakan dua aplikasi independen yang berkomunikasi melalui REST API.

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER (Browser)                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
                  ┌──────────▼──────────┐
                  │   FRONTEND          │
                  │   Vue.js 3          │
                  │   TypeScript        │
                  │   Tailwind CSS      │
                  │   Vite (build)      │
                  │   ─────────────     │
                  │   Vue Router        │
                  │   Pinia Store       │
                  │   Axios Client      │
                  └──────────┬──────────┘
                             │
                 HTTP / REST API
                 Bearer Token (Sanctum)
                 Content-Type: application/json
                             │
                  ┌──────────▼──────────┐
                  │   BACKEND           │
                  │   Laravel 11        │
                  │   PHP 8.2+          │
                  │   ─────────────     │
                  │   API Routes        │
                  │   Middleware RBAC   │
                  │   Controllers       │
                  │   Services          │
                  │   Eloquent ORM      │
                  │   Events/Listeners  │
                  └──────────┬──────────┘
                             │
                  ┌──────────▼──────────┐
                  │   DATABASE          │
                  │   MySQL 8.0         │
                  │   9 Tabel Utama     │
                  │   Normalisasi 3NF   │
                  └─────────────────────┘
```

---

## 2. Komponen Utama Sistem

### 2.1 Frontend — Vue.js Application

Frontend adalah Single Page Application (SPA) yang berjalan sepenuhnya di browser pengguna. Setelah di-load pertama kali, navigasi antar halaman dilakukan tanpa reload browser penuh.

| Komponen | Teknologi | Fungsi |
|----------|-----------|--------|
| **Halaman (Views)** | Vue SFC (.vue) | Tampilan setiap halaman aplikasi |
| **Komponen UI** | Vue + Tailwind | Elemen UI yang dapat digunakan ulang |
| **Routing** | Vue Router 4 | Navigasi antar halaman dan route guard |
| **State Management** | Pinia | Menyimpan state global (auth, data) |
| **HTTP Client** | Axios | Mengirim request ke backend API |
| **Type System** | TypeScript | Validasi tipe data saat development |
| **Build Tool** | Vite | Bundling dan hot-reload development |

**Struktur layer frontend:**

```
Browser
└── App.vue (root)
    ├── AppLayout.vue (sidebar + topbar)
    │   ├── DashboardView.vue
    │   ├── ProductsView.vue
    │   ├── InventoryView.vue
    │   ├── TransactionsView.vue
    │   └── ... (halaman lainnya)
    └── LoginView.vue (tanpa layout)
```

### 2.2 Backend — Laravel REST API

Backend adalah aplikasi Laravel yang berfungsi murni sebagai REST API server. Backend tidak me-render HTML — hanya menerima request dan mengembalikan response dalam format JSON.

| Komponen | Fungsi |
|----------|--------|
| **Routes** (`routes/api.php`) | Mendefinisikan endpoint API |
| **Middleware** | Autentikasi Sanctum, RBAC role check |
| **Controllers** | Menangani request dan mengatur response |
| **Services** | Logika bisnis yang kompleks (stok, transaksi) |
| **Models** | Representasi tabel database dengan Eloquent |
| **Events & Listeners** | Manajemen stok berbasis event (OrderShipped, OrderCanceled) |
| **Resources** | Transformasi data model ke format JSON |

### 2.3 Database — MySQL

Database menyimpan seluruh data persisten sistem. Skema dirancang menggunakan normalisasi 3NF untuk meminimalkan redundansi dan menjaga integritas data.

---

## 3. Alur Request dan Response

### 3.1 Alur Autentikasi (Login)

```
Browser                   Frontend (Vue)               Backend (Laravel)          Database
   │                           │                              │                       │
   │  Klik tombol Login        │                              │                       │
   │ ─────────────────────────►│                              │                       │
   │                           │  POST /api/auth/login        │                       │
   │                           │  { email, password }         │                       │
   │                           │ ────────────────────────────►│                       │
   │                           │                              │  SELECT * FROM users  │
   │                           │                              │ ─────────────────────►│
   │                           │                              │  ◄─────────────────── │
   │                           │                              │  Hash compare         │
   │                           │  200 OK                      │                       │
   │                           │  { token, user }             │                       │
   │                           │ ◄────────────────────────────│                       │
   │                           │  Simpan token ke localStorage│                       │
   │  Redirect ke Dashboard    │                              │                       │
   │ ◄─────────────────────────│                              │                       │
```

### 3.2 Alur Request Data Terautentikasi

```
Frontend (Vue)                          Backend (Laravel)
     │                                        │
     │  GET /api/products                     │
     │  Header: Authorization: Bearer {token} │
     │ ──────────────────────────────────────►│
     │                                        │  Middleware: auth:sanctum
     │                                        │  Validasi token di tabel
     │                                        │  personal_access_tokens
     │                                        │
     │                                        │  Middleware: CheckRole
     │                                        │  Periksa user->role
     │                                        │
     │                                        │  ProductController@index
     │                                        │  Eloquent query + pagination
     │                                        │
     │  200 OK                                │
     │  { data: [...], meta: {...} }          │
     │ ◄──────────────────────────────────────│
     │                                        │
     │  Update reactive state (Pinia/ref)     │
     │  Render tabel di UI                    │
```

### 3.3 Alur Transaksi dengan Event-Driven Stok

```
Frontend           Backend Controller      TransactionService      Event System         Database
    │                      │                       │                     │                  │
    │ PATCH /status=shipped │                       │                     │                  │
    │ ──────────────────── ►│                       │                     │                  │
    │                       │ updateStatus()        │                     │                  │
    │                       │ ──────────────────── ►│                     │                  │
    │                       │                       │ Validasi alur status│                  │
    │                       │                       │ Update status DB    │                  │
    │                       │                       │ ─────────────────────────────────────►│
    │                       │                       │                     │                  │
    │                       │                       │ event(OrderShipped) │                  │
    │                       │                       │ ────────────────── ►│                  │
    │                       │                       │                     │ DeductInventory  │
    │                       │                       │                     │ ─────────────────►│
    │                       │                       │                     │ UPDATE inventory │
    │                       │                       │                     │  qty_on_hand -= n│
    │  200 OK               │                       │                     │                  │
    │ ◄─────────────────────│                       │                     │                  │
```

---

## 4. Pola Komunikasi REST API

### 4.1 Format Request

Semua request ke API menggunakan format berikut:

```
Method : GET | POST | PUT | PATCH | DELETE
URL    : http://127.0.0.1:8000/api/{resource}
Headers:
  Content-Type  : application/json
  Accept        : application/json
  Authorization : Bearer {sanctum_token}   ← wajib untuk endpoint terproteksi
Body   : JSON (untuk POST, PUT, PATCH)
```

### 4.2 Format Response

Semua response dari API menggunakan struktur yang konsisten:

**Response sukses (data tunggal):**
```json
{
  "status": "success",
  "message": "Data berhasil diambil",
  "data": { ... }
}
```

**Response sukses (data list dengan pagination):**
```json
{
  "status": "success",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://...",
    "last": "http://...",
    "prev": null,
    "next": "http://..."
  }
}
```

**Response error validasi (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "sku": ["The sku has already been taken."]
  }
}
```

**Response error autentikasi (401):**
```json
{
  "message": "Unauthenticated."
}
```

### 4.3 HTTP Status Code yang Digunakan

| Kode | Kondisi |
|------|---------|
| `200 OK` | Request berhasil (GET, PUT, PATCH) |
| `201 Created` | Resource baru berhasil dibuat (POST) |
| `204 No Content` | Berhasil tanpa response body (DELETE) |
| `401 Unauthorized` | Token tidak valid atau tidak ada |
| `403 Forbidden` | Token valid tapi role tidak cukup |
| `404 Not Found` | Resource tidak ditemukan |
| `422 Unprocessable` | Validasi input gagal |
| `500 Server Error` | Error internal server |

---

## 5. Sistem Autentikasi dan Otorisasi

### 5.1 Laravel Sanctum (Token-Based Auth)

Sistem menggunakan **Stateless Token Authentication** via Laravel Sanctum:

1. Pengguna login → backend membuat Personal Access Token
2. Token disimpan di tabel `personal_access_tokens`
3. Frontend menyimpan token di `localStorage`
4. Setiap request API menyertakan token di header `Authorization: Bearer {token}`
5. Backend memvalidasi token untuk setiap request ke route yang terproteksi

### 5.2 Role-Based Access Control (RBAC)

Hierarki role dari terendah ke tertinggi:

```
Viewer < Staff < Manager < Admin
```

| Permission | Viewer | Staff | Manager | Admin |
|------------|:------:|:-----:|:-------:|:-----:|
| Lihat semua data | ✅ | ✅ | ✅ | ✅ |
| Buat data / transaksi | ❌ | ✅ | ✅ | ✅ |
| Edit data | ❌ | ❌ | ✅ | ✅ |
| Transfer stok | ❌ | ❌ | ✅ | ✅ |
| Update status transaksi | ❌ | ❌ | ✅ | ✅ |
| Hapus data | ❌ | ❌ | ❌ | ✅ |

---

## 6. Arsitektur Event-Driven untuk Manajemen Stok

Manajemen stok menggunakan event-driven architecture untuk memastikan konsistensi data:

```
TransactionService
├── Status: pending → processing   → (tidak ada event stok)
├── Status: processing → shipped   → dispatch(OrderShipped)
│                                      └── DeductInventoryOnShip
│                                            └── inventory.qty_on_hand -= quantity
├── Status: shipped → delivered    → (tidak ada event stok)
└── Status: * → canceled           → dispatch(OrderCanceled)  [jika sebelumnya shipped]
                                       └── RestoreInventoryOnCancel
                                             └── inventory.qty_on_hand += quantity
```

---

## 7. Struktur Folder Singkat

### Backend (`inventory-app/`)

```
app/
├── Http/
│   ├── Controllers/Api/    ← Controller untuk setiap resource
│   ├── Middleware/         ← CheckRole, autentikasi
│   └── Requests/           ← Form Request validasi
├── Models/                 ← Eloquent models
├── Services/               ← Logika bisnis (InventoryService, TransactionService)
├── Events/                 ← OrderShipped, OrderCanceled
├── Listeners/              ← DeductInventory, RestoreInventory
└── Providers/              ← EventServiceProvider
routes/
└── api.php                 ← Definisi semua endpoint API
database/
├── migrations/             ← Skema tabel
└── seeders/                ← Data awal
```

### Frontend (`inventory-ui/`)

```
src/
├── api/                    ← Modul axios per resource
├── stores/                 ← Pinia stores (auth, dll)
├── views/                  ← Halaman aplikasi per fitur
├── components/
│   └── layout/             ← AppLayout (sidebar, topbar)
├── router/                 ← Definisi route dan guard
├── types/                  ← TypeScript interfaces
└── assets/
    └── main.css            ← Tailwind CSS entry
```

# 🏛 Architecture Documentation

Dokumentasi arsitektur sistem InvenSys.

---

## Gambaran Umum

InvenSys menggunakan arsitektur **decoupled** — backend API dan frontend SPA terpisah, namun di-serve dari satu Nginx container di production.

```
Browser
  │ HTTP :80
  ▼
┌─────────────────────────────────────────────┐
│              Nginx Container                 │
│  ┌───────────────────┐  ┌─────────────────┐ │
│  │   Vue 3 SPA       │  │ /api/* → PHP-FPM│ │
│  │   (static files)  │  │  FastCGI proxy  │ │
│  └───────────────────┘  └─────────────────┘ │
└─────────────────────────────────────────────┘
  │ FastCGI :9000
  ▼
┌─────────────────────────────────────────────┐
│         PHP-FPM Container (Laravel 12)       │
│                                              │
│  Route → Middleware → Controller → Service   │
│                            │                 │
│                         Model ↔ MySQL        │
│                            │                 │
│                    Event → Listener → Queue  │
└─────────────────────────────────────────────┘
         ┌──────────────┬────────────────────┐
         ▼              ▼                    ▼
   MySQL 8.0      Queue Worker          Scheduler
   Container      Container             Container
```

---

## Backend — Laravel 12

### Struktur Direktori

```
inventory-app/
├── app/
│   ├── Console/Commands/       # Artisan commands
│   │   ├── ImportInventoryCSV.php
│   │   └── SendLowStockAlerts.php
│   ├── Events/                 # Domain events
│   │   ├── OrderShipped.php
│   │   └── OrderCanceled.php
│   ├── Exports/                # Excel exports (Maatwebsite)
│   ├── Http/
│   │   ├── Controllers/Api/    # REST controllers (thin)
│   │   ├── Middleware/         # CORS, CheckRole, ForceJsonResponse
│   │   ├── Requests/           # Form Request validation
│   │   └── Resources/          # API Resource transformers
│   ├── Listeners/              # Event handlers
│   │   ├── DeductInventoryOnShip.php
│   │   └── RestoreInventoryOnCancel.php
│   ├── Mail/                   # Mailable classes
│   ├── Models/                 # Eloquent models
│   ├── Providers/              # EventServiceProvider
│   └── Services/               # Business logic
│       ├── InventoryService.php
│       ├── TransactionService.php
│       └── ReportService.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php                 # Semua API routes
```

### Alur Request

```
HTTP Request
  ↓
Middleware Stack:
  - CorsMiddleware (handle OPTIONS preflight)
  - ForceJsonResponse (semua response pasti JSON)
  - auth:sanctum (verifikasi Bearer token)
  - CheckRole (verifikasi role user)
  ↓
Form Request (validasi input)
  ↓
Controller (thin — hanya terima & return)
  ↓
Service Layer (business logic)
  ↓
Eloquent Model
  ↓
Database (MySQL)
  ↓ (async)
Event → Queue → Listener (side effects)
```

### Design Patterns

- **Service Layer** — Logic di `Services/`, controller hanya delegasi
- **API Resources** — Output transformation via `Http/Resources/`
- **Form Requests** — Validasi terpusat, bukan di controller
- **Events & Listeners** — Decoupled side effects (inventory deduction/restore)
- **Queue** — Async jobs untuk email dan background tasks

---

## Frontend — Vue 3 SPA

### Struktur Direktori

```
inventory-ui/src/
├── api/                  # Axios API calls per domain
│   ├── index.ts          # Axios instance + interceptors
│   ├── auth.ts
│   ├── products.ts
│   ├── inventory.ts
│   ├── transactions.ts
│   └── ...
├── components/
│   └── layout/
│       └── AppLayout.vue # Sidebar + main content layout
├── router/
│   └── index.ts          # Vue Router + navigation guards
├── stores/
│   └── auth.ts           # Pinia store (user, token, idle timer)
├── types/
│   └── index.ts          # TypeScript interfaces
└── views/                # Page components
    ├── dashboard/
    ├── products/
    ├── inventory/
    ├── transactions/
    └── ...
```

### State Management (Pinia)

```typescript
// stores/auth.ts
state: {
  user: User | null       // Data user login
  token: string | null    // Bearer token
}
// Keduanya disimpan di localStorage untuk persist

actions:
  login()   // POST /api/auth/login → simpan token & user
  logout()  // POST /api/auth/logout → hapus token, redirect /login

// Idle timeout: auto-logout setelah 3 menit tidak aktif
// Activity reset setiap mousemove, keypress, click
```

### Axios Interceptors

```typescript
// api/index.ts

// Request: tambah Authorization header otomatis
config.headers.Authorization = `Bearer ${token}`

// Response: jika 401 → auto redirect ke /login
if (error.response?.status === 401) {
  authStore.logout()
  router.push('/login')
}
```

---

## Event-Driven Inventory

```
TransactionController.updateStatus(id, 'shipped')
  → TransactionService.ship(transaction)
    → transaction.update({ status: 'shipped' })
    → event(new OrderShipped(transaction))
      → DeductInventoryOnShip::handle()
        → InventoryService.deductForTransaction(transaction)
          → inventory.decrement('quantity', qty) per item

TransactionController.updateStatus(id, 'canceled')  [was 'shipped']
  → TransactionService.cancel(transaction)
    → transaction.update({ status: 'canceled' })
    → event(new OrderCanceled(transaction))
      → RestoreInventoryOnCancel::handle()
        → InventoryService.restoreForTransaction(transaction)
          → inventory.increment('quantity', qty) per item
```

Listener berjalan **synchronous** untuk memastikan konsistensi data stok.

---

## Security

| Aspek | Implementasi |
|---|---|
| Authentication | Laravel Sanctum (Bearer token) |
| Authorization | `CheckRole` middleware per route group |
| CORS | `CorsMiddleware` — handle preflight OPTIONS |
| JSON Response | `ForceJsonResponse` — semua API response pasti JSON |
| Soft Delete | Products menggunakan soft delete |
| Input Validation | Form Request di semua endpoint write |
| Password | bcrypt hashing (Laravel default) |
| Idle Timeout | 3 menit auto-logout di frontend |

---

## Queue & Scheduler

```
Queue Driver: database (tabel `jobs`)

Jobs di-queue:
- SendLowStockAlert (email ke admin)

Scheduler (routes/console.php):
- SendLowStockAlerts → daily

Worker command:
php artisan queue:work --tries=3 --timeout=90
```

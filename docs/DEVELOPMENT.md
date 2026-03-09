# 💻 Development Guide

Panduan setup dan pengembangan lokal InvenSys.

---

## Prasyarat

| Tool | Versi | Cek |
|---|---|---|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20+ | `node -v` |
| npm | 9+ | `npm -v` |
| MySQL | 8.0 | `mysql --version` |
| Git | any | `git --version` |

**PHP Extensions wajib:** pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip, intl, xml

---

## Setup Backend (Laravel)

```bash
cd inventory-app

# Install PHP dependencies
composer install

# Copy dan edit environment
cp .env.example .env

# Generate app key
php artisan key:generate

# Edit .env — sesuaikan database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=

# Buat database (jika belum ada)
mysql -u root -e "CREATE DATABASE inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan migration + seed
php artisan migrate --seed

# Jalankan server
php artisan serve
# → http://127.0.0.1:8000
```

---

## Setup Frontend (Vue 3)

```bash
cd inventory-ui

# Install dependencies
npm install

# Set environment
echo "VITE_API_BASE_URL=http://127.0.0.1:8000/api" > .env

# Jalankan dev server
npm run dev
# → http://localhost:5173
```

---

## Queue Worker (untuk email & jobs)

```bash
cd inventory-app
php artisan queue:work --tries=3 --sleep=3
```

---

## Artisan Commands Berguna

```bash
# Reset database dari awal
php artisan migrate:fresh --seed

# Clear semua cache
php artisan optimize:clear

# Lihat semua API routes
php artisan route:list --path=api

# REPL interaktif
php artisan tinker

# Status queue jobs
php artisan queue:status

# Proses 1 job dari queue
php artisan queue:work --once

# Kirim low stock alert manual
php artisan inventory:send-low-stock-alerts

# Import inventory dari CSV
php artisan inventory:import /path/to/file.csv

# Jalankan scheduler sekali
php artisan schedule:run
```

---

## Vite Commands

```bash
# Dev server dengan Hot Module Replacement
npm run dev

# Build untuk production
npm run build

# Preview build hasil
npm run preview

# Type check TypeScript
npm run type-check

# Lint
npm run lint
```

---

## Testing

```bash
cd inventory-app

# Jalankan semua tests
php artisan test

# Test file spesifik
php artisan test tests/Feature/Api/ProductTest.php
php artisan test tests/Feature/Api/TransactionTest.php
php artisan test tests/Unit/Services/InventoryServiceTest.php

# Filter test tertentu
php artisan test --filter=ProductTest
php artisan test --filter=can_create_product

# Dengan coverage report
php artisan test --coverage

# Parallel testing (lebih cepat)
php artisan test --parallel
```

### Struktur Test

```
tests/
├── Feature/Api/
│   ├── ProductTest.php         # CRUD produk + validasi
│   └── TransactionTest.php     # State machine transaksi
└── Unit/Services/
    └── InventoryServiceTest.php # Business logic unit test
```

---

## Menambah Fitur Baru

### Backend

```bash
# 1. Buat migration
php artisan make:migration create_xxx_table

# 2. Buat model
php artisan make:model Xxx

# 3. Buat controller
php artisan make:controller Api/XxxController --api

# 4. Buat Form Request (validasi)
php artisan make:request StoreXxxRequest
php artisan make:request UpdateXxxRequest

# 5. Buat API Resource (output transformer)
php artisan make:resource XxxResource

# 6. Daftarkan route di routes/api.php
Route::apiResource('xxx', XxxController::class);
```

### Frontend

1. Buat `src/api/xxx.ts` — axios calls
2. Tambah types di `src/types/index.ts`
3. Buat `src/views/xxx/XxxView.vue`
4. Daftarkan di `src/router/index.ts`
5. Tambah link di `src/components/layout/AppLayout.vue`

---

## Environment Variables

### Backend (`inventory-app/.env`)

```env
APP_NAME=InvenSys
APP_ENV=local
APP_KEY=base64:...           # Auto-generated
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log              # Emails ke storage/logs/laravel.log
```

### Frontend (`inventory-ui/.env`)

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api   # Development
# Di production (Nginx proxy): VITE_API_BASE_URL=/api
```

---

## Tips & Best Practices

### Backend
- Business logic → `app/Services/` (bukan di Controller)
- Validasi → `app/Http/Requests/`
- Output format → `app/Http/Resources/`
- Selalu wrap service call di Controller dengan `try/catch`
- Event untuk side-effects (inventory change, email), bukan direct call

### Frontend
- Semua HTTP call melalui `src/api/`
- Gunakan TypeScript interfaces untuk semua data
- State global di Pinia, local state di `ref()` / `reactive()`
- Komponen reusable di `src/components/`
- Loading & error state selalu di-handle di setiap view

---

## Struktur Branch Git

```
main          ← production (auto deploy ke Railway)
develop       ← staging / integration testing
feature/*     ← fitur baru (merge ke develop)
fix/*         ← bug fix (merge ke develop atau main)
hotfix/*      ← critical fix langsung ke main
```

---

## Laragon (Windows) — Tips

Jika menggunakan Laragon:

```bash
# PHP path
C:\laragon\bin\php\php-8.3.x\php.exe

# Composer
C:\laragon\bin\composer\composer.phar

# Node.js
C:\laragon\bin\nodejs\node-v22\node.exe

# MySQL di Laragon sudah running di port 3306
# Username: root, Password: (kosong)
```

Tambahkan ke PATH agar bisa akses dari terminal biasa.

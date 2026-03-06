# 📥 Panduan Instalasi

Dokumen ini menjelaskan langkah-langkah instalasi lengkap Sistem Informasi Manajemen Inventaris dari awal hingga siap dijalankan di lingkungan lokal.

---

## Prasyarat Sistem

Pastikan perangkat Anda telah memiliki software berikut sebelum memulai instalasi:

| Software | Versi Minimum | Cek Versi |
|----------|---------------|-----------|
| PHP | 8.2 | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20.x LTS | `node -v` |
| npm | 10.x | `npm -v` |
| MySQL | 8.0 | `mysql --version` |
| Git | 2.x | `git --version` |

---

## 1. Backend Setup (Laravel)

### 1.1 Clone Repository

```bash
git clone https://github.com/username/inventory-app.git
cd inventory-app
```

### 1.2 Install Dependency PHP

```bash
composer install
```

Jika berada di environment production, gunakan flag `--no-dev` untuk mengecualikan package development:

```bash
composer install --no-dev --optimize-autoloader
```

### 1.3 Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Buka file `.env` dan sesuaikan konfigurasi berikut:

```env
APP_NAME="Sistem Inventaris"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Konfigurasi Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi CORS (sesuaikan dengan URL frontend)
FRONTEND_URL=http://localhost:5173

# Konfigurasi Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

### 1.4 Generate Application Key

```bash
php artisan key:generate
```

Perintah ini akan mengisi nilai `APP_KEY` di file `.env` secara otomatis.

### 1.5 Buat Database

Buat database baru di MySQL:

```sql
CREATE DATABASE inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Atau melalui tool seperti phpMyAdmin, Laragon, atau TablePlus.

### 1.6 Jalankan Migrasi dan Seeding

Jalankan migrasi untuk membuat seluruh tabel database:

```bash
php artisan migrate
```

Jalankan seeder untuk mengisi data awal (termasuk akun admin, data contoh produk, gudang, dan customer):

```bash
php artisan db:seed
```

Atau jalankan keduanya sekaligus:

```bash
php artisan migrate --seed
```

> ⚠️ **Perhatian:** Perintah `migrate --seed` akan membuat tabel dan mengisi data awal. Jika ingin mereset database dari awal, gunakan `php artisan migrate:fresh --seed`.

### 1.7 Publish Konfigurasi Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 1.8 Jalankan Development Server

```bash
php artisan serve
```

Backend API sekarang berjalan di: **http://127.0.0.1:8000**

Untuk menjalankan di port tertentu:

```bash
php artisan serve --port=8080
```

### Verifikasi Backend

Buka browser atau gunakan curl untuk memverifikasi API berjalan:

```bash
curl http://127.0.0.1:8000/api/ping
# Response: {"message": "API is running"}
```

---

## 2. Frontend Setup (Vue.js)

### 2.1 Clone Repository Frontend

```bash
git clone https://github.com/username/inventory-ui.git
cd inventory-ui
```

### 2.2 Install Dependency Node.js

```bash
npm install
```

### 2.3 Konfigurasi Environment Frontend

Salin file environment contoh:

```bash
cp .env.example .env
```

Sesuaikan URL backend di file `.env`:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

> **Catatan:** Jika backend berjalan di port atau host berbeda, sesuaikan nilai `VITE_API_BASE_URL` di atas.

Pastikan juga konfigurasi `src/api/index.ts` mengarah ke URL yang benar:

```typescript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
})
```

### 2.4 Jalankan Development Server

```bash
npm run dev
```

Frontend sekarang berjalan di: **http://localhost:5173**

### 2.5 Build untuk Production

Untuk membuat build production yang dioptimalkan:

```bash
npm run build
```

File hasil build akan tersimpan di folder `dist/`. Folder ini yang akan di-deploy ke web server.

Untuk preview hasil build secara lokal sebelum deploy:

```bash
npm run preview
```

---

## 3. Akun Default Setelah Seeding

| Role | Email | Password | Hak Akses |
|------|-------|----------|-----------|
| Admin | admin@inventory.test | password | Akses penuh (CRUD + hapus) |
| Manager | manager@inventory.test | password | CRUD, transfer stok, tanpa hapus |
| Staff | staff@inventory.test | password | Lihat data + buat transaksi |
| Viewer | viewer@inventory.test | password | Hanya lihat data |

---

## 4. Konfigurasi CORS Backend

Pastikan CORS dikonfigurasi dengan benar di `config/cors.php` agar frontend dapat berkomunikasi dengan backend:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,
```

---

## 5. Troubleshooting

### Error: `php artisan key:generate` gagal
Pastikan file `.env` sudah ada. Jika belum, jalankan `cp .env.example .env` terlebih dahulu.

### Error: `SQLSTATE[HY000] [1049] Unknown database`
Pastikan database `inventory_system` sudah dibuat di MySQL sebelum menjalankan migrasi.

### Error: `npm install` gagal karena versi Node.js
Gunakan Node.js versi 20 LTS. Jika menggunakan nvm:
```bash
nvm install 20
nvm use 20
```

### Error: CORS policy di browser
Pastikan nilai `FRONTEND_URL` di `.env` backend sesuai dengan URL frontend yang berjalan (termasuk port).

### Error: Unauthorized 401 di semua request API
Pastikan token disertakan di header `Authorization: Bearer {token}` dan token masih valid (belum expired atau logout).

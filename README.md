# 📦 Sistem Informasi Manajemen Inventaris Berbasis Web

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat-square&logo=vuedotjs)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=flat-square&logo=tailwindcss)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

Sistem informasi berbasis web untuk mengelola inventaris barang secara efisien dan terintegrasi. Dibangun dengan arsitektur **frontend-backend terpisah** menggunakan Laravel REST API dan Vue.js.

---

## 📋 Deskripsi Aplikasi

Sistem Informasi Manajemen Inventaris adalah aplikasi web yang dirancang untuk membantu organisasi atau perusahaan dalam mengelola data barang, memantau stok, serta mencatat transaksi barang masuk dan keluar secara real-time.

Sistem ini menggantikan pencatatan manual yang rawan kesalahan dengan solusi digital yang terintegrasi, akurat, dan mudah diakses dari perangkat apapun melalui browser web.

---

## 🎯 Tujuan Aplikasi

- Menyediakan sistem pencatatan inventaris yang akurat dan terintegrasi
- Memudahkan pemantauan stok barang secara real-time lintas gudang
- Mencatat riwayat transaksi barang masuk dan keluar secara terstruktur
- Menghasilkan laporan inventaris yang dapat diandalkan untuk pengambilan keputusan
- Membatasi akses fitur berdasarkan peran pengguna demi keamanan data

---

## ✨ Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| 📊 **Dashboard** | Ringkasan KPI inventaris, stok rendah, dan transaksi terkini |
| 📦 **Manajemen Barang** | CRUD data barang lengkap dengan pencarian dan filter kategori |
| 🏷️ **Manajemen Kategori** | Pengelompokan barang berdasarkan kategori |
| 🏭 **Manajemen Gudang** | Pengelolaan data multi-gudang |
| 📋 **Manajemen Stok** | Pemantauan stok real-time per produk per gudang |
| ⬆️ **Barang Masuk** | Pencatatan penerimaan dan penambahan stok |
| ⬇️ **Barang Keluar** | Pencatatan pengeluaran dan pengurangan stok |
| 👥 **Manajemen Customer** | Data pelanggan lengkap dengan informasi credit limit |
| 👤 **Manajemen Karyawan** | Data karyawan yang terhubung ke gudang |
| 🔄 **Manajemen Transaksi** | Order management dengan alur status yang tervalidasi |
| 🔐 **Role & Akses** | RBAC dengan 4 level: Admin, Manager, Staff, Viewer |
| 📈 **Laporan** | Laporan penjualan dan laporan inventaris |

---

## 🛠️ Teknologi yang Digunakan

### Backend

| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| PHP | 8.2+ | Bahasa pemrograman backend |
| Laravel | 11.x | Framework backend REST API |
| Laravel Sanctum | 4.x | Autentikasi berbasis token (Bearer Token) |
| Eloquent ORM | bawaan | Interaksi dengan database |
| MySQL | 8.0 | Sistem manajemen database |

### Frontend

| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| Vue.js | 3.x | Framework frontend reaktif |
| TypeScript | 5.x | Type-safe JavaScript |
| Vue Router | 4.x | Navigasi dan routing halaman |
| Pinia | 2.x | State management |
| Tailwind CSS | v4 | Utility-first CSS framework |
| Axios | 1.x | HTTP client untuk komunikasi API |
| Lucide Vue | latest | Library ikon |
| Vite | 6.x | Build tool dan dev server |

---

## 🏗️ Arsitektur Singkat

```
┌──────────────────────────────────────────────────────┐
│                  CLIENT (Browser)                     │
│                                                        │
│   Vue.js 3 + TypeScript + Tailwind CSS               │
│   ├── Vue Router  — navigasi halaman                 │
│   ├── Pinia       — state management & auth store    │
│   └── Axios       — HTTP client dengan interceptor   │
└──────────────────────┬───────────────────────────────┘
                        │  HTTP REST API
                        │  JSON + Bearer Token
                        │  http://127.0.0.1:8000/api
┌──────────────────────▼───────────────────────────────┐
│              BACKEND — Laravel 11                     │
│                                                        │
│   ├── Laravel Sanctum  — token authentication        │
│   ├── RBAC Middleware  — role-based access control   │
│   ├── Eloquent ORM     — database abstraction        │
│   └── Event-Driven     — inventory stock management  │
└──────────────────────┬───────────────────────────────┘
                        │
┌──────────────────────▼───────────────────────────────┐
│                 MySQL 8.0 Database                    │
│   9 tabel utama, normalisasi 3NF                     │
└──────────────────────────────────────────────────────┘
```

---

## 📁 Struktur Dokumentasi

```
docs/
├── INSTALLATION.md     # Panduan instalasi lengkap dari awal
├── ARCHITECTURE.md     # Dokumentasi arsitektur sistem
├── DATABASE.md         # Skema database dan relasi antar tabel
├── API.md              # Referensi lengkap REST API endpoint
├── FEATURES.md         # Dokumentasi fitur dan alur penggunaan
├── DEVELOPMENT.md      # Panduan pengembangan untuk developer
└── DEPLOYMENT.md       # Panduan deploy ke server production
```

---

## 🚀 Cara Menjalankan (Ringkas)

### Prasyarat

- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 20.x
- MySQL 8.0

### Backend (Laravel)

```bash
git clone https://github.com/username/inventory-app.git
cd inventory-app
composer install
cp .env.example .env
php artisan key:generate
# Sesuaikan konfigurasi database di .env
php artisan migrate --seed
php artisan serve
# API berjalan di http://127.0.0.1:8000
```

### Frontend (Vue.js)

```bash
git clone https://github.com/username/inventory-ui.git
cd inventory-ui
npm install
npm run dev
# UI berjalan di http://localhost:5173
```

### Akun Default

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@inventory.test | password |
| Manager | manager@inventory.test | password |
| Staff | staff@inventory.test | password |
| Viewer | viewer@inventory.test | password |

> 📖 Lihat [docs/INSTALLATION.md](docs/INSTALLATION.md) untuk panduan instalasi lengkap dan terperinci.

---

## 📚 Dokumentasi Lengkap

| Dokumen | Deskripsi |
|---------|-----------|
| [📥 INSTALLATION.md](docs/INSTALLATION.md) | Setup project backend dan frontend dari awal |
| [🏗️ ARCHITECTURE.md](docs/ARCHITECTURE.md) | Arsitektur sistem, alur data, dan komponen utama |
| [🗄️ DATABASE.md](docs/DATABASE.md) | Skema tabel, tipe data, dan relasi database |
| [🔌 API.md](docs/API.md) | Referensi endpoint REST API beserta contoh request/response |
| [✨ FEATURES.md](docs/FEATURES.md) | Penjelasan fitur dan alur penggunaan sistem |
| [💻 DEVELOPMENT.md](docs/DEVELOPMENT.md) | Panduan pengembangan fitur baru untuk developer |
| [🚀 DEPLOYMENT.md](docs/DEPLOYMENT.md) | Panduan deploy aplikasi ke server production |

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

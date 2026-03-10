# 📦 InvenSys — Sistem Manajemen Inventaris Berbasis Web

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5.9-3178C6?style=for-the-badge&logo=typescript)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)
![Redis](https://img.shields.io/badge/Redis-7-DC382D?style=for-the-badge&logo=redis)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)
![Kubernetes](https://img.shields.io/badge/Kubernetes-Ready-326CE5?style=for-the-badge&logo=kubernetes)

**Sistem manajemen inventaris berbasis web — Laravel 12 REST API + Vue 3 SPA.**  
**Siap Docker, Kubernetes, Redis queue, dan real-time email alert (WIB).**

</div>

---

## 📋 Daftar Isi
- [Fitur](#-fitur)
- [Tech Stack](#-tech-stack)
- [Arsitektur](#-arsitektur)
- [Quick Start Docker](#-quick-start-docker)
- [Quick Start Local](#-quick-start-local)
- [Environment Variables](#-environment-variables)
- [Default Accounts](#-default-accounts)
- [RBAC](#-rbac)
- [Dokumentasi](#-dokumentasi)

---

## ✨ Fitur

| Fitur | Deskripsi |
|---|---|
| 📊 **Dashboard** | KPI ringkasan revenue (USD), grafik order, alert stok rendah real-time |
| 📦 **Produk & Kategori** | CRUD lengkap dengan SKU, harga modal/jual USD, margin otomatis, soft delete |
| 🏭 **Inventaris Multi-Gudang** | Stok per gudang, transfer antar gudang, min/max stock threshold |
| 🛒 **Transaksi** | State machine: `pending → processing → shipped → delivered / canceled` dengan validasi kredit customer |
| 👥 **Manajemen Customer** | CRUD customer, credit limit USD, credit enforcement per transaksi |
| 👤 **Manajemen User** | Admin buat/edit/hapus user dengan role manager/staff/viewer |
| 📋 **Laporan** | Export PDF & Excel: inventaris, penjualan, dashboard |
| 🚨 **Real-time Stock Alert** | Email otomatis ke admin saat stok turun di bawah minimum (WIB timezone) |
| 📅 **Daily Digest** | Rangkuman harian stok rendah dikirim tiap jam 08:00 WIB |
| ⏱️ **Auto-logout Idle** | Sesi otomatis berakhir setelah 3 menit tidak aktif (warning 30 detik sebelumnya) |
| 🔐 **RBAC** | 4 role: admin, manager, staff, viewer — enforced di backend & frontend |
| 📱 **Responsif** | Mobile-friendly, sidebar collapsible, card layout mobile |
| ⚡ **Redis** | Cache, queue, dan session menggunakan Redis untuk performa optimal |

---

## 🛠 Tech Stack

**Backend:** PHP 8.2+, Laravel 12, Laravel Sanctum 4, MySQL 8.0, Redis 7, DomPDF, Maatwebsite Excel

**Frontend:** Vue 3.5, TypeScript 5.9, Vite 7, Tailwind CSS v4, Pinia 3, Vue Router 5, Axios, Lucide Icons

**Infrastructure:** Docker, Docker Compose, Kubernetes, Nginx, PHP-FPM

---

## 🏗 Arsitektur

```
Browser
  │ HTTP :80
Nginx (Vue SPA static + /api/* reverse proxy)
  │ FastCGI :9000
PHP-FPM (Laravel 12 REST API)
  │
  ├── MySQL 8.0  (persistent data)
  ├── Redis 7    (cache + queue + session)
  ├── Queue Worker (default + notifications queue)
  └── Scheduler  (artisan schedule:work — daily digest 08:00 WIB)
```

Detail lengkap: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

---

## 🚀 Quick Start Docker

```bash
# 1. Clone repo
git clone https://github.com/Lananuranf-RockNRoll/Manajemen-Inventaris-Berbasis-Web.git
cd Manajemen-Inventaris-Berbasis-Web

# 2. Salin dan isi environment file
cp .env.docker.example .env
# Edit .env — isi APP_KEY, DB_PASSWORD, MAIL_* dll

# 3. Generate APP_KEY (kalau belum ada)
# php artisan key:generate --show  → paste hasilnya ke .env

# 4. Jalankan
docker-compose up --build -d
```

Tunggu log `🚀 Starting PHP-FPM...` lalu buka **http://localhost**

> ✅ Migration dan seeding database berjalan **otomatis** saat container start.

Detail lengkap: [docs/DOCKER.md](docs/DOCKER.md)

---

## 💻 Quick Start Local

```bash
# Backend
cd inventory-app
composer install
cp .env.example .env
php artisan key:generate
# Edit .env — sesuaikan DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan migrate --seed
php artisan serve              # → http://127.0.0.1:8000

# Frontend (terminal baru)
cd inventory-ui
npm install
npm run dev                    # → http://localhost:5173
```

Detail lengkap: [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)

---

## 🔑 Environment Variables

> ⚠️ **JANGAN commit file `.env` ke Git!** File `.env` sudah ditambahkan ke `.gitignore`.

Salin template yang tersedia:

```bash
# Untuk Docker
cp .env.docker.example .env

# Untuk development lokal (backend)
cp inventory-app/.env.example inventory-app/.env
```

Variabel penting yang **wajib** diisi:

| Variable | Keterangan |
|---|---|
| `APP_KEY` | Generate: `php artisan key:generate --show` |
| `DB_PASSWORD` | Password database MySQL |
| `DB_ROOT_PASSWORD` | Password root MySQL (Docker only) |
| `MAIL_USERNAME` | Email Gmail pengirim alert |
| `MAIL_PASSWORD` | [Google App Password](https://myaccount.google.com/apppasswords) (bukan password Gmail biasa) |

---

## 👤 Default Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@inventory.test | password |
| Manager | manager@inventory.test | password |
| Staff | staff@inventory.test | password |
| Viewer | viewer@inventory.test | password |

> ⚠️ Ganti password default setelah login pertama di production!

---

## 🔐 RBAC

| Fitur | Admin | Manager | Staff | Viewer |
|:---|:---:|:---:|:---:|:---:|
| Dashboard & Laporan | ✅ | ✅ | ✅ | ✅ |
| Lihat semua data | ✅ | ✅ | ✅ | ✅ |
| Buat Transaksi | ✅ | ✅ | ✅ | ❌ |
| Edit Produk / Gudang | ✅ | ✅ | ❌ | ❌ |
| Transfer Stok | ✅ | ✅ | ❌ | ❌ |
| Atur Credit Customer | ✅ | ✅ | ❌ | ❌ |
| Kelola Karyawan | ✅ | ✅ | ❌ | ❌ |
| Hapus Data | ✅ | ❌ | ❌ | ❌ |
| **Manajemen User** | ✅ | ❌ | ❌ | ❌ |
| Reset Credit Customer | ✅ | ❌ | ❌ | ❌ |

---

## 📚 Dokumentasi

| Dokumen | Deskripsi |
|---|---|
| [API Reference](docs/API.md) | Semua endpoint REST API dengan contoh request/response |
| [Arsitektur](docs/ARCHITECTURE.md) | Struktur kode, design patterns, alur data & event |
| [Database](docs/DATABASE.md) | Schema tabel, ERD, relasi, dan state machine |
| [Docker](docs/DOCKER.md) | Panduan lengkap Docker & Docker Compose |
| [Kubernetes](docs/KUBERNETES.md) | Panduan deploy ke Kubernetes cluster |
| [Deployment](docs/DEPLOYMENT.md) | Deploy ke Railway (free tier) |
| [Development](docs/DEVELOPMENT.md) | Setup development lokal step-by-step |
| [Fitur](docs/FEATURES.md) | Dokumentasi lengkap semua fitur |

---

## 📄 Lisensi

[MIT License](LICENSE)

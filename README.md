# 📦 InvenSys — Sistem Manajemen Inventaris Berbasis Web

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5.9-3178C6?style=for-the-badge&logo=typescript)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)
![Kubernetes](https://img.shields.io/badge/Kubernetes-Ready-326CE5?style=for-the-badge&logo=kubernetes)

**Sistem manajemen inventaris berbasis web — Laravel 12 REST API + Vue 3 SPA, siap Docker & Kubernetes.**

</div>

---

## 📋 Daftar Isi
- [Fitur](#-fitur)
- [Tech Stack](#-tech-stack)
- [Arsitektur](#-arsitektur)
- [Quick Start Docker](#-quick-start-docker)
- [Quick Start Local](#-quick-start-local)
- [Default Accounts](#-default-accounts)
- [RBAC](#-rbac)
- [Dokumentasi](#-dokumentasi)

---

## ✨ Fitur

- **Dashboard** — KPI ringkasan, grafik penjualan, alert stok rendah
- **Produk & Kategori** — CRUD dengan SKU, harga beli/jual, soft delete
- **Inventaris Multi-Warehouse** — Stok per gudang, transfer antar gudang
- **Transaksi** — State machine: `pending → processing → shipped → delivered / canceled`
- **Laporan** — Export PDF & Excel (inventaris, penjualan, dashboard)
- **Email Alert** — Notifikasi stok rendah via queue & scheduler
- **RBAC** — 4 role: admin, manager, staff, viewer
- **Responsif** — Mobile-friendly, sidebar collapsible, card layout mobile
- **Docker & Kubernetes** — Siap deploy production

---

## 🛠 Tech Stack

**Backend:** PHP 8.2+, Laravel 12, Sanctum 4, MySQL 8.0, DomPDF, Maatwebsite Excel, Spatie Query Builder

**Frontend:** Vue 3.5, TypeScript 5.9, Vite 7, Tailwind CSS v4, Pinia 3, Vue Router 5, Axios, Chart.js, Lucide Icons

**Infrastructure:** Docker, Docker Compose, Kubernetes, Nginx, PHP-FPM

---

## 🏗 Arsitektur

```
Browser
  │ HTTP :80
Nginx (Vue SPA static + /api/* FastCGI proxy)
  │ FastCGI :9000
PHP-FPM (Laravel 12 REST API)
  │
  ├── MySQL 8.0
  ├── Queue Worker (database queue)
  └── Scheduler (artisan schedule:work)
```

Detail lengkap: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

---

## 🚀 Quick Start Docker

```bash
git clone https://github.com/Lananuranf-RockNRoll/Manajemen-Inventaris-Berbasis-Web.git
cd Manajemen-Inventaris-Berbasis-Web

docker-compose up --build
```

Tunggu log `🚀 Starting PHP-FPM...` lalu buka **http://localhost**

> Migration dan seeding database berjalan otomatis.

Detail: [docs/DOCKER.md](docs/DOCKER.md)

---

## 💻 Quick Start Local

```bash
# Backend
cd inventory-app
composer install && cp .env.example .env
php artisan key:generate
# Edit .env sesuaikan DB credentials
php artisan migrate --seed
php artisan serve        # http://127.0.0.1:8000

# Frontend (terminal baru)
cd inventory-ui
npm install && npm run dev   # http://localhost:5173
```

Detail: [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)

---

## 👤 Default Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@inventory.test | password |
| Manager | manager@inventory.test | password |
| Staff | staff@inventory.test | password |
| Viewer | viewer@inventory.test | password |

---

## 🔐 RBAC

| Fitur | Admin | Manager | Staff | Viewer |
|---|:---:|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ | ✅ |
| Kelola Produk | ✅ | ✅ | ❌ | ❌ |
| Lihat Produk | ✅ | ✅ | ✅ | ✅ |
| Transfer Stok | ✅ | ✅ | ✅ | ❌ |
| Kelola Transaksi | ✅ | ✅ | ✅ | ❌ |
| Lihat Laporan | ✅ | ✅ | ✅ | ✅ |
| Kelola Karyawan | ✅ | ✅ | ❌ | ❌ |
| Manajemen User | ✅ | ❌ | ❌ | ❌ |

---

## 📚 Dokumentasi

| Dokumen | Deskripsi |
|---|---|
| [API Reference](docs/API.md) | Semua endpoint REST API dengan contoh request/response |
| [Arsitektur](docs/ARCHITECTURE.md) | Struktur kode, design patterns, dan alur data |
| [Database](docs/DATABASE.md) | Schema tabel, ERD, dan relasi |
| [Docker](docs/DOCKER.md) | Panduan lengkap Docker & Docker Compose |
| [Kubernetes](docs/KUBERNETES.md) | Panduan deploy ke Kubernetes |
| [Deployment](docs/DEPLOYMENT.md) | Deploy ke Railway (gratis) |
| [Development](docs/DEVELOPMENT.md) | Setup development lokal |
| [Fitur](docs/FEATURES.md) | Dokumentasi semua fitur |

---

## 📄 Lisensi

[MIT License](LICENSE)

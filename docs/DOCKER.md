# 🐳 Docker Documentation

Panduan lengkap menjalankan InvenSys menggunakan Docker.

---

## Prasyarat

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) v24+
- Docker Compose v2.x (sudah termasuk di Docker Desktop)
- RAM minimal 4GB untuk Docker

---

## Struktur File Docker

```
Project-root/
├── Dockerfile              # Multi-stage build
├── docker-compose.yml      # Orchestrasi semua service
├── .env                    # Environment variables
└── docker/
    ├── nginx.conf          # Konfigurasi Nginx
    ├── entrypoint.sh       # Script startup PHP container
    └── mysql-init/
        └── 01-init.sql     # Inisialisasi database
```

---

## Services

| Service | Image | Port | Fungsi |
|---|---|---|---|
| `db` | mysql:8.0 | 3306 | Database MySQL |
| `php` | invensys-backend | 9000 | Laravel PHP-FPM |
| `nginx` | invensys-nginx | 80 | Web server + SPA |
| `queue` | invensys-backend | — | Queue worker |
| `scheduler` | invensys-backend | — | Artisan scheduler |

---

## Dockerfile — Multi-Stage Build

```dockerfile
# Stage 1: Backend — PHP-FPM + Laravel
FROM php:8.2-fpm-alpine AS backend
# Install PHP extensions, Composer, copy code, composer install

# Stage 2: Frontend build — Node.js build Vue SPA
FROM node:20-alpine AS frontend-build
# npm ci && VITE_API_BASE_URL=/api npm run build

# Stage 3: Nginx — Serve SPA + proxy to PHP-FPM
FROM nginx:1.25-alpine AS nginx
# Copy nginx.conf + built frontend dist/
```

**Keuntungan multi-stage:**
- Image nginx final ~75MB (tanpa Node.js atau source code)
- Image backend ~180MB (hanya PHP + vendor)
- Build cache optimal per stage

---

## Quick Start

```bash
# Clone repo
git clone https://github.com/Lananuranf-RockNRoll/Manajemen-Inventaris-Berbasis-Web.git
cd Manajemen-Inventaris-Berbasis-Web

# Jalankan semua service (build otomatis)
docker-compose up --build

# Atau background
docker-compose up -d --build
```

Aplikasi tersedia di **http://localhost** setelah log:
```
✅ MySQL is up!
🌱 Seeding database...
⚡ Caching config, routes, views...
🚀 Starting PHP-FPM...
```

---

## Environment Variables (.env)

```env
APP_ENV=production
APP_KEY=base64:...          # php -r "echo 'base64:'.base64_encode(random_bytes(32));"
APP_DEBUG=false
APP_URL=http://localhost

DB_ROOT_PASSWORD=rootsecret123
DB_DATABASE=inventory_system
DB_USERNAME=invensys
DB_PASSWORD=invensys_pass123

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@invensys.app
```

---

## Perintah Docker Sehari-hari

```bash
# Status semua container
docker-compose ps

# Logs real-time
docker-compose logs -f
docker-compose logs -f php
docker-compose logs -f nginx

# Masuk ke container
docker-compose exec php sh
docker-compose exec db mysql -u invensys -pinvensys_pass123 inventory_system

# Artisan commands
docker-compose exec php php artisan tinker
docker-compose exec php php artisan migrate:status
docker-compose exec php php artisan queue:status

# Restart service
docker-compose restart php
docker-compose restart nginx

# Stop semua
docker-compose down

# RESET DATABASE (hapus volume)
docker-compose down -v

# Rebuild setelah perubahan code
docker-compose build --no-cache && docker-compose up -d
```

---

## Nginx Configuration

Nginx dikonfigurasi untuk:

1. **Serve Vue SPA** — semua route non-API ke `index.html` (SPA fallback)
2. **Proxy `/api/*`** — ke PHP-FPM via FastCGI
3. **Proxy `/sanctum/*`** — untuk CSRF Laravel Sanctum
4. **Cache static assets** — JS/CSS/images cache 1 tahun
5. **Gzip** — aktif untuk semua text content

```nginx
location ~ ^/(api|sanctum)(/.*)?$ {
    fastcgi_pass php:9000;
    fastcgi_param SCRIPT_FILENAME /var/www/html/public/index.php;
    include fastcgi_params;
}

location / {
    try_files $uri $uri/ /index.html;
}
```

---

## Entrypoint Script (`docker/entrypoint.sh`)

Dijalankan otomatis saat PHP container start:

1. **Wait for MySQL** — retry tiap 3 detik sampai DB ready
2. **Run migrations** — `php artisan migrate --force`
3. **Seed database** — `php artisan db:seed --force`
4. **Cache** — config, routes, views
5. **Start PHP-FPM**

> Queue & Scheduler container langsung jalankan command mereka tanpa migrate.

---

## Volumes

| Volume | Mount Point | Isi |
|---|---|---|
| `db_data` | `/var/lib/mysql` | Data MySQL (persisten) |
| `app_storage` | `/var/www/html/storage/app` | File upload |
| `app_logs` | `/var/www/html/storage/logs` | Log aplikasi |

---

## Health Check

MySQL container memiliki health check:
```yaml
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
  interval: 10s
  timeout: 5s
  retries: 15
  start_period: 30s
```

PHP container hanya start **setelah MySQL healthy**.

---

## Troubleshooting

**Port 80 sudah dipakai:**
```bash
# Windows
netstat -ano | findstr :80
# Ganti port: "8080:80" di docker-compose.yml
```

**Container PHP restart terus:**
```bash
docker-compose logs php
# Biasanya APP_KEY belum di-set atau DB tidak konek
```

**Database tidak konek:**
```bash
docker-compose logs db
# Tunggu MySQL init selesai (30-60 detik pertama kali)
```

**Reset total:**
```bash
docker-compose down -v && docker-compose up --build
```

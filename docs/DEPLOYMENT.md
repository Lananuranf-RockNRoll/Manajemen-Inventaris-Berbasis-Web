# 🚀 Panduan Deployment ke Production

Dokumen ini menjelaskan langkah-langkah lengkap untuk men-deploy Sistem Informasi Manajemen Inventaris ke server production berbasis Linux (Ubuntu 22.04 LTS).

---

## Spesifikasi Server yang Direkomendasikan

| Komponen | Minimum | Direkomendasikan |
|----------|---------|-----------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Storage | 20 GB SSD | 50 GB SSD |
| OS | Ubuntu 20.04 LTS | Ubuntu 22.04 LTS |
| Bandwidth | 100 Mbps | 1 Gbps |

---

## 1. Setup Server Awal

### 1.1 Update System

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip software-properties-common
```

### 1.2 Buat User Non-Root (Opsional tapi Direkomendasikan)

```bash
sudo adduser deploy
sudo usermod -aG sudo deploy
su - deploy
```

---

## 2. Install PHP 8.2

```bash
# Tambah repository PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP dan ekstensi yang dibutuhkan Laravel
sudo apt install -y \
  php8.2 \
  php8.2-fpm \
  php8.2-mysql \
  php8.2-xml \
  php8.2-mbstring \
  php8.2-curl \
  php8.2-zip \
  php8.2-bcmath \
  php8.2-tokenizer \
  php8.2-json \
  php8.2-gd

# Verifikasi instalasi
php -v
```

Output yang diharapkan:
```
PHP 8.2.x (cli) (built: ...)
```

### Konfigurasi PHP-FPM

```bash
# Edit konfigurasi PHP untuk production
sudo nano /etc/php/8.2/fpm/php.ini
```

Ubah nilai berikut:
```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

---

## 3. Install Composer

```bash
# Download installer
curl -sS https://getcomposer.org/installer -o composer-setup.php

# Verifikasi hash (opsional tapi disarankan)
HASH=$(curl -sS https://composer.github.io/installer.sig)
php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); }"

# Install secara global
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Hapus installer
rm composer-setup.php

# Verifikasi
composer --version
```

---

## 4. Install Node.js

```bash
# Install Node.js 20 LTS via NodeSource
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verifikasi
node -v    # Harus v20.x.x
npm -v     # Harus v10.x.x
```

---

## 5. Install dan Konfigurasi MySQL

### 5.1 Install MySQL Server

```bash
sudo apt install -y mysql-server

# Jalankan wizard keamanan MySQL
sudo mysql_secure_installation
```

Ikuti prompt:
- Pasang VALIDATE PASSWORD component: **Yes**
- Password strength: **STRONG (2)**
- Masukkan dan konfirmasi root password yang kuat
- Remove anonymous users: **Yes**
- Disallow root login remotely: **Yes**
- Remove test database: **Yes**
- Reload privilege tables: **Yes**

### 5.2 Buat Database dan User

```bash
sudo mysql -u root -p
```

Di dalam MySQL shell:

```sql
-- Buat database
CREATE DATABASE inventory_production
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Buat user khusus aplikasi
CREATE USER 'inventory_user'@'localhost' IDENTIFIED BY 'GuntiPasswordYangSangatKuat#2024!';

-- Berikan hak akses
GRANT ALL PRIVILEGES ON inventory_production.* TO 'inventory_user'@'localhost';

-- Terapkan perubahan
FLUSH PRIVILEGES;

-- Verifikasi
SHOW DATABASES;
EXIT;
```

### 5.3 Aktifkan MySQL

```bash
sudo systemctl start mysql
sudo systemctl enable mysql
```

---

## 6. Install Nginx

```bash
sudo apt install -y nginx

sudo systemctl start nginx
sudo systemctl enable nginx

# Verifikasi
nginx -v
```

---

## 7. Deploy Backend (Laravel)

### 7.1 Clone Repository

```bash
# Buat direktori web
sudo mkdir -p /var/www
cd /var/www

# Clone repository backend
sudo git clone https://github.com/username/inventory-app.git inventory-api
sudo chown -R $USER:$USER /var/www/inventory-api
cd /var/www/inventory-api
```

### 7.2 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 7.3 Konfigurasi Environment

```bash
cp .env.example .env
nano .env
```

Isi konfigurasi production:

```env
APP_NAME="Sistem Inventaris"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.domainanda.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_production
DB_USERNAME=inventory_user
DB_PASSWORD=GuntiPasswordYangSangatKuat#2024!

# Frontend URL (untuk CORS)
FRONTEND_URL=https://domainanda.com

# Sanctum
SANCTUM_STATEFUL_DOMAINS=domainanda.com
SESSION_DOMAIN=.domainanda.com

# Cache dan Session (gunakan Redis untuk production)
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 7.4 Generate Application Key

```bash
php artisan key:generate
```

### 7.5 Jalankan Migration dan Seeding

```bash
# Jalankan migrasi
php artisan migrate --force

# Jalankan seeder (hanya untuk initial setup)
php artisan db:seed --force
```

> ⚠️ Flag `--force` diperlukan karena Laravel memblokir migrasi di environment production tanpa konfirmasi.

### 7.6 Optimasi untuk Production

```bash
# Cache konfigurasi
php artisan config:cache

# Cache route
php artisan route:cache

# Cache view
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 7.7 Set Permission File

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/inventory-api

# Set permission folder
sudo find /var/www/inventory-api -type d -exec chmod 755 {} \;
sudo find /var/www/inventory-api -type f -exec chmod 644 {} \;

# Storage dan bootstrap/cache harus writable
sudo chmod -R 775 /var/www/inventory-api/storage
sudo chmod -R 775 /var/www/inventory-api/bootstrap/cache

# Tambahkan user deploy ke group www-data
sudo usermod -aG www-data $USER
```

---

## 8. Deploy Frontend (Vue.js)

### 8.1 Clone Repository Frontend

```bash
cd /var/www
sudo git clone https://github.com/username/inventory-ui.git inventory-ui
sudo chown -R $USER:$USER /var/www/inventory-ui
cd /var/www/inventory-ui
```

### 8.2 Konfigurasi Environment Frontend

```bash
cp .env.example .env
nano .env
```

```env
VITE_API_BASE_URL=https://api.domainanda.com/api
```

### 8.3 Install Dependencies

```bash
npm install
```

### 8.4 Build untuk Production

```bash
npm run build
```

Hasil build tersimpan di folder `dist/`. Set permission:

```bash
sudo chown -R www-data:www-data /var/www/inventory-ui/dist
sudo chmod -R 755 /var/www/inventory-ui/dist
```

---

## 9. Konfigurasi Nginx

### 9.1 Konfigurasi Virtual Host Backend (API)

```bash
sudo nano /etc/nginx/sites-available/inventory-api
```

```nginx
server {
    listen 80;
    server_name api.domainanda.com;
    root /var/www/inventory-api/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Charset
    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_types text/plain application/json application/javascript text/css;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Timeout untuk proses lama
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/inventory-api-access.log;
    error_log  /var/log/nginx/inventory-api-error.log;
}
```

### 9.2 Konfigurasi Virtual Host Frontend

```bash
sudo nano /etc/nginx/sites-available/inventory-ui
```

```nginx
server {
    listen 80;
    server_name domainanda.com www.domainanda.com;
    root /var/www/inventory-ui/dist;
    index index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # SPA — semua route diarahkan ke index.html
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Logging
    access_log /var/log/nginx/inventory-ui-access.log;
    error_log  /var/log/nginx/inventory-ui-error.log;
}
```

### 9.3 Aktifkan Konfigurasi

```bash
# Enable konfigurasi
sudo ln -s /etc/nginx/sites-available/inventory-api /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/inventory-ui  /etc/nginx/sites-enabled/

# Test konfigurasi
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## 10. Setup SSL dengan Let's Encrypt (HTTPS)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generate sertifikat SSL untuk kedua domain
sudo certbot --nginx -d domainanda.com -d www.domainanda.com
sudo certbot --nginx -d api.domainanda.com

# Verifikasi auto-renewal
sudo certbot renew --dry-run
```

Certbot akan otomatis memodifikasi konfigurasi Nginx untuk redirect HTTP ke HTTPS dan memperbarui sertifikat setiap 90 hari.

---

## 11. Setup Firewall (UFW)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw deny 3306   # Blokir akses MySQL dari luar
sudo ufw enable

# Verifikasi status
sudo ufw status
```

---

## 12. Proses Update Aplikasi

### Update Backend

```bash
cd /var/www/inventory-api

# Pull kode terbaru
git pull origin main

# Install dependency baru (jika ada)
composer install --no-dev --optimize-autoloader

# Jalankan migration baru (jika ada)
php artisan migrate --force

# Clear dan rebuild cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Update Frontend

```bash
cd /var/www/inventory-ui

# Pull kode terbaru
git pull origin main

# Install dependency baru (jika ada)
npm install

# Rebuild
npm run build

# Set permission
sudo chown -R www-data:www-data dist/
```

---

## 13. Monitoring dan Pemeliharaan

### Cek Log Nginx

```bash
# Access log
sudo tail -f /var/log/nginx/inventory-api-access.log

# Error log
sudo tail -f /var/log/nginx/inventory-api-error.log
```

### Cek Log Laravel

```bash
sudo tail -f /var/www/inventory-api/storage/logs/laravel.log
```

### Cek Status Service

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
```

### Backup Database

```bash
# Buat script backup
nano /home/deploy/backup-db.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/deploy/backups"
mkdir -p $BACKUP_DIR

mysqldump -u inventory_user -p'GuntiPasswordYangSangatKuat#2024!' \
  inventory_production | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Hapus backup lebih dari 30 hari
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup selesai: backup_$DATE.sql.gz"
```

```bash
chmod +x /home/deploy/backup-db.sh

# Jadwalkan backup otomatis setiap hari jam 2 pagi
crontab -e
# Tambahkan:
0 2 * * * /home/deploy/backup-db.sh >> /home/deploy/backups/backup.log 2>&1
```

---

## Troubleshooting Production

### Error: 502 Bad Gateway
```bash
# Cek status PHP-FPM
sudo systemctl status php8.2-fpm

# Restart jika perlu
sudo systemctl restart php8.2-fpm
```

### Error: 403 Forbidden
```bash
# Periksa permission storage
sudo chmod -R 775 /var/www/inventory-api/storage
sudo chown -R www-data:www-data /var/www/inventory-api
```

### Error: CORS blocked di browser
Pastikan `.env` backend sudah benar:
```env
FRONTEND_URL=https://domainanda.com
SANCTUM_STATEFUL_DOMAINS=domainanda.com
```
Kemudian clear cache:
```bash
php artisan config:clear && php artisan config:cache
```

### Error: Vue Router — halaman 404 saat refresh
Pastikan konfigurasi Nginx frontend menggunakan `try_files $uri $uri/ /index.html;` agar semua route di-handle oleh Vue Router.

### Database: Too many connections
Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
max_connections = 200
```
Restart MySQL:
```bash
sudo systemctl restart mysql
```

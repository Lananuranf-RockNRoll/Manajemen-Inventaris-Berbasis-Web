# 🚀 Deployment Guide — Railway (Gratis)

Panduan deploy InvenSys ke [Railway](https://railway.app) — platform cloud gratis dengan $5/bulan credit.

---

## Kenapa Railway?

| Fitur | Railway | Render | Fly.io |
|---|---|---|---|
| Docker support | ✅ Native | ✅ | ✅ |
| MySQL included | ✅ Plugin | ❌ Berbayar | ❌ |
| Free tier | $5/bulan credit | Ya (spin-down) | 3 shared VM |
| Kemudahan setup | ⭐⭐⭐ | ⭐⭐ | ⭐ |
| Auto deploy dari GitHub | ✅ | ✅ | Manual |

---

## Prasyarat

- Repo ini sudah di GitHub ✅
- Akun Railway (daftar gratis di [railway.app](https://railway.app))

---

## Langkah Deploy

### Step 1 — Buat Project Railway

1. Login ke [railway.app](https://railway.app)
2. Klik **"New Project"**
3. Pilih **"Deploy from GitHub repo"**
4. Authorize Railway ke GitHub → pilih repo `Manajemen-Inventaris-Berbasis-Web`
5. Railway akan detect `Dockerfile` di root secara otomatis

### Step 2 — Tambah MySQL Service

1. Di dalam project, klik **"+ New"**
2. Pilih **"Database" → "Add MySQL"**
3. Railway otomatis buat MySQL dan expose environment variables:
   - `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

### Step 3 — Set Environment Variables

Di service **App** → tab **"Variables"**, tambahkan:

```env
APP_ENV=production
APP_KEY=base64:GENERATE_DULU_DI_BAWAH
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@invensys.app
```

**Generate APP_KEY** (jalankan lokal):
```bash
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

### Step 4 — Deploy

- Push ke branch `main` → Railway **auto-deploy** otomatis
- Atau klik **"Deploy"** manual di dashboard
- Tunggu build selesai (~3–5 menit)

### Step 5 — Jalankan Migration

Setelah deploy berhasil, buka tab **"Shell"** di Railway service App:

```bash
php artisan migrate --force
php artisan db:seed --force
```

### Step 6 — Akses Aplikasi

Railway memberikan URL public:
```
https://manajemen-inventaris-production.up.railway.app
```

---

## Railway CLI (Opsional tapi Berguna)

```bash
# Install
npm install -g @railway/cli

# Login
railway login

# Link ke project
railway link

# Jalankan command di production
railway run php artisan migrate --force
railway run php artisan db:seed --force
railway run php artisan optimize:clear

# Lihat logs live
railway logs

# Buka dashboard
railway open
```

---

## Konfigurasi Dockerfile untuk Railway

Railway menggunakan `Dockerfile` di root project. Sudah dikonfigurasi untuk Railway:

- ✅ `EXPOSE 80` — Railway detect port otomatis
- ✅ Single container (Nginx + PHP + SPA)
- ✅ Entrypoint script handle migration otomatis
- ✅ Health check endpoint `/up` (Laravel default)

---

## Setup Email Production (Opsional)

Ganti `MAIL_MAILER=log` dengan SMTP provider:

**Mailtrap (testing):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_user
MAIL_PASSWORD=your_mailtrap_pass
```

**Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=yourapp@gmail.com
MAIL_PASSWORD=your_google_app_password
MAIL_ENCRYPTION=tls
```

---

## Custom Domain

1. Railway dashboard → service App → tab **"Settings"**
2. **"Custom Domain"** → masukkan domain kamu
3. Tambah CNAME record di DNS provider:
   ```
   CNAME  www  your-app.up.railway.app
   ```

---

## Auto Deploy

Setiap `git push` ke branch `main` akan trigger deploy otomatis di Railway. Tidak perlu action manual.

```bash
# Push code baru → otomatis deploy
git add .
git commit -m "feat: tambah fitur X"
git push origin main
```

---

## Troubleshooting

**Build gagal:**
- Cek Railway build logs
- Pastikan `Dockerfile` ada di root project
- Pastikan `APP_KEY` sudah di-set

**500 Internal Server Error:**
```bash
railway run php artisan config:clear
railway run php artisan cache:clear
```

**Database tidak konek:**
- Pastikan semua `DB_*` variables sudah di-set dari Railway MySQL plugin
- Gunakan `${{MYSQLHOST}}` bukan hardcode IP

**Migration gagal:**
```bash
railway run php artisan migrate:status
railway run php artisan migrate --force
```

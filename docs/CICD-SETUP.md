# InvenSys — Panduan Setup CI/CD Pipeline

**DevSecOps Pipeline** untuk project Laravel + Vue 3 dengan deployment otomatis ke AWS EC2 via GitHub Actions.

---

## Arsitektur Pipeline

```
Git Push ke main
      |
      +-- [Job 1] Security Scan
      |     +-- composer audit  (PHP CVE check)
      |     +-- npm audit       (Node CVE check)
      |     +-- Gitleaks        (secret leak scan)
      |
      +-- [Job 2] PHP Tests (PHPUnit - Unit + Feature)
      |
      +-- [Job 3] Frontend Build + TypeScript Check
      |
      +-- [Job 4] Docker Build + Push ke GHCR
      |     +-- Build invensys-backend image
      |     +-- Build invensys-nginx image
      |     +-- Trivy container scan
      |
      +-- [Job 5] Deploy ke EC2
            +-- SSH ke EC2, pull image dari GHCR
            +-- Rolling update containers
            +-- php artisan migrate + cache clear
            +-- Health check (auto rollback jika gagal)
```

---

## Prasyarat

- AWS EC2 minimal t3.small (2 vCPU, 2 GB RAM)
- Repository sudah di GitHub
- File `.pem` SSH key EC2

---

## Langkah 1 — Setup EC2 (sekali saja)

```bash
# Upload setup script ke EC2
scp -i your-key.pem scripts/ec2-setup.sh ubuntu@EC2_IP:/tmp/

# SSH masuk ke EC2
ssh -i your-key.pem ubuntu@EC2_IP

# Jalankan setup script
sudo bash /tmp/ec2-setup.sh
```

Setelah selesai, isi konfigurasi `.env`:

```bash
nano /opt/invensys/.env
```

Nilai yang WAJIB diisi:

```
APP_KEY=base64:...          <- php artisan key:generate --show
APP_URL=http://IP_EC2:8888
DB_PASSWORD=password_kuat_16char
DB_ROOT_PASSWORD=root_password_kuat_16char
```

---

## Langkah 2 — Tambahkan Health Check Route

Buka `inventory-app/routes/api.php`, tambahkan di bagian paling bawah:

```php
// Health check endpoint -- TANPA middleware auth
Route::get('/health', function () {
    try {
        \DB::connection()->getPdo();
        $db = 'ok';
    } catch (\Exception $e) {
        $db = 'error';
    }
    return response()->json([
        'status'    => $db === 'ok' ? 'ok' : 'degraded',
        'timestamp' => now()->toISOString(),
        'checks'    => ['database' => $db],
    ], $db === 'ok' ? 200 : 503);
});
```

---

## Langkah 3 — Buka Port di AWS

AWS Console > EC2 > Security Groups > Inbound Rules > Edit:

| Type | Protocol | Port | Source |
|------|----------|------|--------|
| Custom TCP | TCP | 8888 | 0.0.0.0/0 |
| SSH | TCP | 22 | IP kamu saja (lebih aman) |

---

## Langkah 4 — Setup GitHub Secrets

GitHub Repository > Settings > Secrets and variables > Actions > New repository secret:

| Secret Name | Nilai |
|-------------|-------|
| `EC2_HOST` | IP publik EC2 (contoh: `13.251.x.x`) |
| `EC2_USER` | `ubuntu` (atau `ec2-user` untuk Amazon Linux) |
| `EC2_SSH_KEY` | Seluruh isi file `.pem` termasuk header BEGIN/END |

**Cara copy isi .pem:**
```bash
cat your-key.pem
# Copy SEMUA teks, termasuk:
# -----BEGIN RSA PRIVATE KEY-----
# ...konten...
# -----END RSA PRIVATE KEY-----
```

---

## Langkah 5 — Commit & Push File Pipeline

```bash
# Dari root project (Project-pi/)
git add .github/workflows/deploy.yml
git add scripts/deploy-remote.sh
git add scripts/ec2-setup.sh
git add .gitleaks.toml
git add docs/CICD-SETUP.md

git commit -m "feat: CI/CD pipeline DevSecOps dengan GitHub Actions"
git push origin main
```

Pipeline akan otomatis berjalan. Pantau di tab **Actions** di GitHub.

---

## Alur Pipeline (estimasi waktu)

| Job | Waktu | Keterangan |
|-----|-------|------------|
| Security Scan | ~3 menit | composer audit + npm audit + Gitleaks |
| PHP Tests | ~5 menit | PHPUnit dengan MySQL service |
| Frontend Build | ~3 menit | Vite build + TypeScript check |
| Docker Build | ~8 menit | Multi-stage build + Trivy scan |
| Deploy ke EC2 | ~5 menit | SSH + pull image + migrate + health check |
| **Total** | **~25 menit** | |

---

## Rollback Manual

Jika aplikasi bermasalah setelah deploy:

```bash
ssh -i your-key.pem ubuntu@EC2_IP
bash /opt/invensys/rollback.sh
```

Rollback otomatis juga terjadi jika health check gagal setelah deploy.

---

## Troubleshooting

### Pipeline gagal di Security Scan

```bash
# Fix PHP vulnerabilities
cd inventory-app && composer update nama/package

# Fix Node vulnerabilities
cd inventory-ui && npm update nama-package
```

### Container tidak mau start

```bash
ssh ubuntu@EC2_IP
docker compose -f /opt/invensys/docker-compose.ec2.yml logs --tail=100
```

### Health check gagal terus

```bash
# Test dari lokal
curl http://EC2_IP:8888/api/health

# Cek dari dalam EC2
docker exec invensys_php php artisan about
docker exec invensys_nginx nginx -t
```

---

## Security Checklist

- [ ] `.env` ada di `.gitignore` dan tidak pernah di-commit
- [ ] `APP_DEBUG=false` di production
- [ ] Password DB minimal 16 karakter
- [ ] Port MySQL (3306) dan Redis (6379) tidak expose ke internet
- [ ] SSH hanya menggunakan key-based auth (bukan password)
- [ ] GitHub Secrets tidak pernah di-print ke log pipeline
- [ ] Dependency di-scan tiap push (composer audit + npm audit)
- [ ] Docker image di-scan dengan Trivy tiap build

---

## File Struktur CI/CD

```
Project-pi/
├── .github/
│   └── workflows/
│       └── deploy.yml          <- Pipeline utama GitHub Actions
├── scripts/
│   ├── deploy-remote.sh        <- Dieksekusi di EC2 saat deploy
│   ├── ec2-setup.sh            <- Setup awal EC2 (sekali saja)
│   └── health-check-route.php  <- Referensi endpoint health check
├── .gitleaks.toml              <- Konfigurasi secret scanner
└── docs/
    └── CICD-SETUP.md           <- Dokumentasi ini
```

---

*InvenSys CI/CD Pipeline — DevSecOps Grade*

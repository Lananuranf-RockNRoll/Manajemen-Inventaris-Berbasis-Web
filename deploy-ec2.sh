#!/bin/bash
# =============================================================
# InvenSys — EC2 Deploy Script
# Jalankan: bash deploy-ec2.sh
# =============================================================

set -e  # stop on error

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }
info() { echo -e "${BLUE}[→]${NC} $1"; }

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║        InvenSys — EC2 Deploy             ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# ── 1. Cek prerequisites ──────────────────────────────────────
info "Mengecek prerequisites..."
command -v docker  >/dev/null 2>&1 || err "Docker tidak ditemukan. Install dulu: sudo dnf install -y docker && sudo systemctl start docker"
command -v git     >/dev/null 2>&1 || err "Git tidak ditemukan."
log "Docker & Git tersedia"

# ── 2. Cek .env ───────────────────────────────────────────────
if [ ! -f ".env" ]; then
    warn ".env tidak ditemukan!"
    if [ -f ".env.docker.example" ]; then
        cp .env.docker.example .env
        warn "File .env dibuat dari .env.docker.example"
        warn "WAJIB isi APP_KEY dan password di .env sebelum lanjut!"
        echo ""
        echo "  Edit file: nano .env"
        echo "  Lalu jalankan script ini lagi."
        exit 1
    else
        err ".env.docker.example juga tidak ada!"
    fi
fi

# Cek APP_KEY
APP_KEY_VAL=$(grep "^APP_KEY=" .env | cut -d'=' -f2)
if [ -z "$APP_KEY_VAL" ] || [ "$APP_KEY_VAL" = "base64:GENERATE_ME" ]; then
    err "APP_KEY di .env belum diisi! Isi dengan output dari: php artisan key:generate --show"
fi
log ".env tersedia dan APP_KEY sudah diisi"

# ── 3. Port conflict check ────────────────────────────────────
info "Mengecek port conflicts..."
if ss -tlnp 2>/dev/null | grep -q ":8888 "; then
    err "Port 8888 sudah dipakai! Ubah port di docker-compose.ec2.yml"
fi
log "Port 8888 tersedia"

# ── 4. Build images ───────────────────────────────────────────
info "Building Docker images..."
docker build --target backend -t invensys-backend:latest . || err "Build backend gagal"
docker build --target nginx   -t invensys-nginx:latest .   || err "Build nginx gagal"
log "Images berhasil di-build"

# ── 5. Start containers ───────────────────────────────────────
info "Menjalankan containers..."
docker compose -f docker-compose.ec2.yml up -d
log "Containers started"

# ── 6. Tunggu DB ready ────────────────────────────────────────
info "Menunggu MySQL siap..."
RETRIES=30
while [ $RETRIES -gt 0 ]; do
    if docker exec invensys_db mysqladmin ping -h localhost -u root --password="${DB_ROOT_PASSWORD:-rootsecret123}" --silent 2>/dev/null; then
        break
    fi
    RETRIES=$((RETRIES - 1))
    echo -n "."
    sleep 3
done
echo ""
[ $RETRIES -eq 0 ] && err "MySQL tidak kunjung ready setelah 90 detik"
log "MySQL siap"

# ── 7. Laravel setup ─────────────────────────────────────────
info "Menjalankan Laravel migrations & seeder..."
docker exec invensys_php php artisan migrate --force
docker exec invensys_php php artisan db:seed --force
docker exec invensys_php php artisan config:cache
docker exec invensys_php php artisan route:cache
docker exec invensys_php php artisan view:cache
docker exec invensys_php php artisan storage:link 2>/dev/null || true
log "Laravel setup selesai"

# ── 8. Cek status ────────────────────────────────────────────
echo ""
echo "─────────────────────────────────────────────"
echo "  Status Containers:"
docker compose -f docker-compose.ec2.yml ps
echo ""
echo "─────────────────────────────────────────────"

# ── 9. Final info ────────────────────────────────────────────
EC2_IP=$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || echo "YOUR_EC2_IP")
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         Deploy Berhasil! ✅               ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "  🌐 URL InvenSys : ${BLUE}http://${EC2_IP}:8888${NC}"
echo ""
echo "  📋 Default accounts:"
echo "     admin@inventory.test   / password  (admin)"
echo "     manager@inventory.test / password  (manager)"
echo "     staff@inventory.test   / password  (staff)"
echo ""
echo "  🔧 Commands berguna:"
echo "     docker compose -f docker-compose.ec2.yml logs -f"
echo "     docker compose -f docker-compose.ec2.yml ps"
echo "     docker compose -f docker-compose.ec2.yml down"
echo ""
warn "JANGAN lupa buka port 8888 di AWS Security Group!"

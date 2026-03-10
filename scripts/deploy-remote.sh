#!/bin/bash
# =============================================================
# InvenSys — Remote Deploy Script (dieksekusi DI EC2 via SSH)
#
# Dipanggil dari GitHub Actions dengan env vars:
#   COMMIT_SHA     — SHA commit yang di-deploy
#   GHCR_REGISTRY  — contoh: ghcr.io/username
#   GHCR_TOKEN     — GitHub token untuk pull image
#   GHCR_USER      — username GitHub
# =============================================================

set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'
YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

log()  { echo -e "${GREEN}[OK]  $(date '+%H:%M:%S')${NC} $1"; }
warn() { echo -e "${YELLOW}[!!]  $(date '+%H:%M:%S')${NC} $1"; }
err()  { echo -e "${RED}[ERR] $(date '+%H:%M:%S')${NC} $1"; exit 1; }
info() { echo -e "${BLUE}[-->] $(date '+%H:%M:%S')${NC} $1"; }

APP_DIR="/opt/invensys"
COMPOSE_FILE="$APP_DIR/docker-compose.ec2.yml"
LOG_FILE="/var/log/invensys/deploy.log"

mkdir -p "$(dirname "$LOG_FILE")"
exec > >(tee -a "$LOG_FILE") 2>&1

echo ""
echo "======================================================"
echo "  InvenSys -- Automated Deploy"
echo "  Commit : ${COMMIT_SHA:0:12}..."
echo "  Time   : $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================================"
echo ""

# ── Validasi env ─────────────────────────────────────────────
info "Validasi environment..."
[ -z "${COMMIT_SHA:-}" ]    && err "COMMIT_SHA tidak diset!"
[ -z "${GHCR_REGISTRY:-}" ] && err "GHCR_REGISTRY tidak diset!"
[ -z "${GHCR_TOKEN:-}" ]    && err "GHCR_TOKEN tidak diset!"
[ -z "${GHCR_USER:-}" ]     && err "GHCR_USER tidak diset!"
log "Environment valid"

# ── Prereq ────────────────────────────────────────────────────
command -v docker &>/dev/null || err "Docker tidak terinstall! Jalankan scripts/ec2-setup.sh"
docker info       &>/dev/null || err "Docker daemon tidak berjalan! sudo systemctl start docker"
log "Docker OK"

mkdir -p "$APP_DIR"
cd "$APP_DIR"

# ── Login GHCR ───────────────────────────────────────────────
info "Login ke GitHub Container Registry..."
echo "$GHCR_TOKEN" | docker login ghcr.io -u "$GHCR_USER" --password-stdin
log "Login GHCR berhasil"

# ── Simpan info rollback ──────────────────────────────────────
info "Simpan image saat ini untuk rollback..."
docker inspect invensys_php 2>/dev/null \
    | python3 -c "import sys,json; print(json.load(sys.stdin)[0]['Config']['Image'])" \
    > /tmp/rollback_backend.txt 2>/dev/null || echo "none" > /tmp/rollback_backend.txt
docker inspect invensys_nginx 2>/dev/null \
    | python3 -c "import sys,json; print(json.load(sys.stdin)[0]['Config']['Image'])" \
    > /tmp/rollback_nginx.txt 2>/dev/null || echo "none" > /tmp/rollback_nginx.txt
log "Rollback info: $(cat /tmp/rollback_backend.txt)"

# ── Pull images baru ─────────────────────────────────────────
info "Pull images dari GHCR..."
docker pull "${GHCR_REGISTRY}/invensys-backend:${COMMIT_SHA}" \
    || err "Gagal pull invensys-backend:${COMMIT_SHA}"
docker pull "${GHCR_REGISTRY}/invensys-nginx:${COMMIT_SHA}" \
    || err "Gagal pull invensys-nginx:${COMMIT_SHA}"

docker tag "${GHCR_REGISTRY}/invensys-backend:${COMMIT_SHA}" invensys-backend:latest
docker tag "${GHCR_REGISTRY}/invensys-nginx:${COMMIT_SHA}"   invensys-nginx:latest
log "Images di-pull dan di-tag sebagai latest"

# ── Validasi .env ─────────────────────────────────────────────
info "Validasi .env production..."
[ ! -f "$APP_DIR/.env" ] && err ".env tidak ada! Jalankan scripts/ec2-setup.sh terlebih dulu."

APP_KEY_VAL=$(grep "^APP_KEY=" "$APP_DIR/.env" | cut -d'=' -f2- | tr -d ' ')
if [ -z "$APP_KEY_VAL" ]; then
    err "APP_KEY kosong di .env! Generate dengan: php artisan key:generate --show"
fi
log ".env valid (APP_KEY sudah diisi)"

# ── Update compose file ──────────────────────────────────────
info "Update docker-compose.ec2.yml..."
[ -f /tmp/docker-compose.ec2.yml ] && cp /tmp/docker-compose.ec2.yml "$COMPOSE_FILE"
log "Compose file updated"

# ── Start DB dan Redis dulu ───────────────────────────────────
info "Starting DB dan Redis..."
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" \
    up -d --no-build --remove-orphans db redis

# ── Tunggu MySQL siap ────────────────────────────────────────
info "Menunggu MySQL ready (max 90 detik)..."
# Baca DB_ROOT_PASSWORD dari .env (format: DB_ROOT_PASSWORD=nilainya)
DB_ROOT=$(grep "^DB_ROOT_PASSWORD=" "$APP_DIR/.env" 2>/dev/null \
    | cut -d'=' -f2- | tr -d ' ' || echo "rootsecret123")

RETRIES=30
while [ $RETRIES -gt 0 ]; do
    if docker exec invensys_db mysqladmin ping -h localhost \
        -u root "--password=${DB_ROOT}" --silent 2>/dev/null; then
        break
    fi
    RETRIES=$((RETRIES-1))
    echo -n "."
    sleep 3
done
echo ""
[ $RETRIES -eq 0 ] && err "MySQL tidak ready setelah 90 detik!"
log "MySQL ready"

# ── Start PHP backend ────────────────────────────────────────
info "Starting PHP, Queue, Scheduler..."
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" \
    up -d --no-build php queue scheduler
sleep 5

# ── Laravel migrations & cache ───────────────────────────────
info "Jalankan Laravel migrations..."
docker exec invensys_php php artisan migrate --force --no-interaction \
    || err "Migration gagal! Cek: docker logs invensys_php"

info "Clear dan rebuild cache..."
docker exec invensys_php php artisan config:cache
docker exec invensys_php php artisan route:cache
docker exec invensys_php php artisan view:cache
docker exec invensys_php php artisan storage:link 2>/dev/null || true
log "Laravel setup selesai"

# ── Start Nginx ──────────────────────────────────────────────
info "Starting Nginx..."
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" \
    up -d --no-build nginx
log "Nginx up"

# ── Buat rollback script ──────────────────────────────────────
cat > /opt/invensys/rollback.sh << 'ROLLBACK'
#!/bin/bash
set -euo pipefail
echo "Rollback InvenSys dimulai..."
PREV_BACK=$(cat /tmp/rollback_backend.txt 2>/dev/null || echo "none")
PREV_NGX=$(cat /tmp/rollback_nginx.txt 2>/dev/null || echo "none")
[ "$PREV_BACK" = "none" ] && { echo "Tidak ada rollback image!"; exit 1; }
echo "  Backend -> $PREV_BACK"
echo "  Nginx   -> $PREV_NGX"
docker tag "$PREV_BACK" invensys-backend:latest 2>/dev/null || true
docker tag "$PREV_NGX"  invensys-nginx:latest   2>/dev/null || true
docker compose -f /opt/invensys/docker-compose.ec2.yml \
    --env-file /opt/invensys/.env up -d --no-build
echo "Rollback selesai!"
ROLLBACK
chmod +x /opt/invensys/rollback.sh

# ── Cek semua container running ───────────────────────────────
info "Verifikasi container status..."
echo ""
docker compose -f "$COMPOSE_FILE" ps
echo ""

EXITED=$(docker compose -f "$COMPOSE_FILE" ps --status=exited --quiet 2>/dev/null | wc -l)
if [ "$EXITED" -gt 0 ]; then
    warn "Ada container yang exit! Log 50 baris terakhir:"
    docker compose -f "$COMPOSE_FILE" logs --tail=50
    err "Deploy gagal - ada container yang tidak berjalan!"
fi

# ── Cleanup ───────────────────────────────────────────────────
docker image prune -f --filter "until=168h" 2>/dev/null || true

# ── Summary ───────────────────────────────────────────────────
EC2_IP=$(curl -s --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null \
    || hostname -I | awk '{print $1}')

echo ""
echo "======================================================"
echo "  Deploy Berhasil!"
echo "  URL  : http://${EC2_IP}:8888"
echo "  SHA  : ${COMMIT_SHA:0:12}"
echo "  Time : $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Log  : $LOG_FILE"
echo "  Undo : bash /opt/invensys/rollback.sh"
echo "======================================================"
echo ""

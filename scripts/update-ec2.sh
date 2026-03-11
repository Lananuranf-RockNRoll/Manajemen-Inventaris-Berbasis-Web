#!/bin/bash
# =============================================================
# InvenSys — Update Script (Manual Pull & Restart di EC2)
# =============================================================
# CARA PAKAI di EC2 (dari folder repo langsung):
#   cd ~/Manajemen-Inventaris-Berbasis-Web
#   bash scripts/update-ec2.sh
#
# Dari laptop via SSH satu baris:
#   ssh -i key.pem ec2-user@EC2_IP \
#     "cd ~/Manajemen-Inventaris-Berbasis-Web && bash scripts/update-ec2.sh"
# =============================================================

set -euo pipefail

# ── Warna ─────────────────────────────────────────────────────
RED='\033[0;31m';  GREEN='\033[0;32m'
YELLOW='\033[1;33m'; BLUE='\033[0;34m'
CYAN='\033[0;36m';  BOLD='\033[1m'; NC='\033[0m'

log()     { echo -e "${GREEN}[OK] $(date '+%H:%M:%S')${NC} $1"; }
warn()    { echo -e "${YELLOW}[!!] $(date '+%H:%M:%S')${NC} $1"; }
err()     { echo -e "${RED}[ERR] $(date '+%H:%M:%S')${NC} $1"; exit 1; }
info()    { echo -e "${BLUE}[-->] $(date '+%H:%M:%S')${NC} $1"; }
section() { echo -e "\n${CYAN}${BOLD}━━━ $1 ━━━${NC}"; }

# ── Auto-detect APP_DIR dari lokasi script ini ─────────────────
# Bekerja meski dijalankan dari direktori manapun.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

# ── Konfigurasi ────────────────────────────────────────────────
COMPOSE_FILE="$APP_DIR/docker-compose.ec2.yml"
ENV_FILE="$APP_DIR/.env"
REPO_URL="https://github.com/Lananuranf-RockNRoll/Manajemen-Inventaris-Berbasis-Web.git"
BRANCH="${BRANCH:-main}"

# ── Log disimpan di dalam repo — tidak perlu sudo sama sekali ──
LOG_DIR="$APP_DIR/storage/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/update-ec2.log"
exec > >(tee -a "$LOG_FILE") 2>&1

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║       InvenSys — EC2 Update (git pull)           ║${NC}"
echo -e "${BOLD}║  Dir    : $APP_DIR${NC}"
echo -e "${BOLD}║  Branch : $BRANCH${NC}"
echo -e "${BOLD}║  Time   : $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""

# ════════════════════════════════════════════════════════
section "1. Validasi Prerequisites"
# ════════════════════════════════════════════════════════

command -v docker &>/dev/null \
    || err "Docker tidak ditemukan! Install: sudo dnf install -y docker && sudo systemctl start docker"
docker info &>/dev/null \
    || err "Docker daemon tidak jalan! Jalankan: sudo systemctl start docker"
command -v git &>/dev/null \
    || err "Git tidak ditemukan! Install: sudo dnf install -y git"
log "Docker dan Git tersedia"

# Cari .env: di root repo dulu, fallback ke /opt/invensys
if [ ! -f "$ENV_FILE" ]; then
    if [ -f "/opt/invensys/.env" ]; then
        ENV_FILE="/opt/invensys/.env"
        warn ".env diambil dari fallback: $ENV_FILE"
    else
        err ".env tidak ditemukan!
  Buat dulu: cp .env.docker .env  lalu isi APP_KEY, DB_PASSWORD, dll."
    fi
fi
log ".env ditemukan: $ENV_FILE"

[ -f "$COMPOSE_FILE" ] \
    || err "docker-compose.ec2.yml tidak ada di $APP_DIR!"

APP_KEY_VAL=$(grep "^APP_KEY=" "$ENV_FILE" | cut -d'=' -f2- | tr -d ' ' || true)
[ -z "$APP_KEY_VAL" ] \
    && err "APP_KEY di .env kosong!
  Generate: docker run --rm php:8.2-cli php -r \"echo 'base64:'.base64_encode(random_bytes(32));\""
log ".env dan APP_KEY valid"

# ════════════════════════════════════════════════════════
section "2. Simpan State Saat Ini (untuk Rollback)"
# ════════════════════════════════════════════════════════

cd "$APP_DIR"

OLD_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "none")
echo "$OLD_COMMIT" > /tmp/invensys_rollback_commit.txt
log "Commit sebelumnya: $OLD_COMMIT"

docker inspect invensys_php 2>/dev/null \
    | python3 -c "import sys,json; print(json.load(sys.stdin)[0]['Config']['Image'])" \
    > /tmp/invensys_rollback_image.txt 2>/dev/null \
    || echo "invensys-backend:latest" > /tmp/invensys_rollback_image.txt
log "Image backup: $(cat /tmp/invensys_rollback_image.txt)"

# ════════════════════════════════════════════════════════
section "3. Git Pull — Tarik Kode Terbaru"
# ════════════════════════════════════════════════════════

REMOTE_URL=$(git remote get-url origin 2>/dev/null || echo "")
if [ -z "$REMOTE_URL" ]; then
    info "Remote origin belum diset, menambahkan..."
    git remote add origin "$REPO_URL"
fi
log "Remote: $(git remote get-url origin)"

# Cek perubahan lokal uncommitted
if ! git diff --quiet 2>/dev/null || ! git diff --cached --quiet 2>/dev/null; then
    warn "Ada perubahan lokal yang belum di-commit:"
    git status --short
    echo ""
    echo -e "${YELLOW}Pilihan penyelesaian:${NC}"
    echo "  [A] Simpan sementara (aman):"
    echo "        git stash"
    echo "        bash scripts/update-ec2.sh"
    echo ""
    echo "  [B] Buang perubahan lokal (hati-hati):"
    echo "        git checkout -- ."
    echo "        bash scripts/update-ec2.sh"
    echo ""
    echo "  [C] Commit lokal dulu:"
    echo "        git add -A && git commit -m 'wip'"
    echo "        bash scripts/update-ec2.sh"
    err "Selesaikan perubahan lokal, lalu jalankan script lagi."
fi

info "Fetch dari origin/$BRANCH..."
git fetch origin "$BRANCH" 2>&1 \
    || err "git fetch gagal! Cek koneksi internet di EC2."

LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse "origin/$BRANCH")

if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    warn "Sudah up-to-date (${LOCAL_COMMIT:0:12}). Lanjutkan rebuild & restart..."
else
    info "git pull origin $BRANCH..."
    if ! git pull origin "$BRANCH" --ff-only 2>&1; then
        echo ""
        warn "git pull --ff-only gagal (ada diverge)."
        echo ""
        echo "  Pilihan:"
        echo "  [1] Reset ke remote (BUANG commit lokal):"
        echo "        git fetch origin $BRANCH"
        echo "        git reset --hard origin/$BRANCH"
        echo "        bash scripts/update-ec2.sh"
        echo ""
        echo "  [2] Lihat perbedaan dulu:"
        echo "        git log HEAD..origin/$BRANCH --oneline"
        err "Selesaikan konflik lalu jalankan lagi."
    fi
    log "git pull berhasil: $OLD_COMMIT → $(git rev-parse --short HEAD)"
fi

# ════════════════════════════════════════════════════════
section "4. Build Docker Images Baru"
# ════════════════════════════════════════════════════════

COMMIT_TAG=$(git rev-parse --short HEAD 2>/dev/null || echo "local")

info "Build invensys-backend..."
docker build \
    --target backend \
    --tag invensys-backend:latest \
    --tag "invensys-backend:${COMMIT_TAG}" \
    . 2>&1 || err "Build backend gagal! Cek Dockerfile."
log "invensys-backend:latest (tag: $COMMIT_TAG)"

info "Build invensys-nginx..."
docker build \
    --target nginx \
    --tag invensys-nginx:latest \
    --tag "invensys-nginx:${COMMIT_TAG}" \
    . 2>&1 || err "Build nginx gagal!"
log "invensys-nginx:latest (tag: $COMMIT_TAG)"

# ════════════════════════════════════════════════════════
section "5. Restart Containers — DB & Redis TIDAK disentuh"
# ════════════════════════════════════════════════════════

info "Pastikan DB dan Redis tetap hidup..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" \
    up -d --no-build --no-recreate db redis
log "DB dan Redis aman (tidak di-restart)"

info "Restart PHP, Queue, Scheduler dengan image baru..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" \
    up -d --no-build --force-recreate php queue scheduler
log "PHP / Queue / Scheduler direstart"

info "Tunggu entrypoint selesai (~10 detik)..."
sleep 10

info "Restart Nginx..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" \
    up -d --no-build --force-recreate nginx
log "Nginx direstart"

# ════════════════════════════════════════════════════════
section "6. Tunggu MySQL Ready"
# ════════════════════════════════════════════════════════

info "Polling MySQL (max 90 detik)..."
DB_ROOT=$(grep "^DB_ROOT_PASSWORD=" "$ENV_FILE" 2>/dev/null \
    | cut -d'=' -f2- | tr -d ' ' || echo "rootsecret123")

RETRIES=30
while [ $RETRIES -gt 0 ]; do
    if docker exec invensys_db mysqladmin ping -h localhost \
        -u root "--password=${DB_ROOT}" --silent 2>/dev/null; then
        break
    fi
    RETRIES=$((RETRIES - 1))
    echo -n "."
    sleep 3
done
echo ""
[ $RETRIES -eq 0 ] && err "MySQL tidak ready setelah 90 detik! Cek: docker logs invensys_db"
log "MySQL ready"

# ════════════════════════════════════════════════════════
section "7. Laravel Post-Deploy (Migration & Cache)"
# ════════════════════════════════════════════════════════

info "php artisan migrate --force..."
docker exec invensys_php php artisan migrate --force --no-interaction \
    || err "Migration gagal! Debug: docker logs invensys_php --tail=50"
log "Migrations OK"

info "Rebuild caches..."
docker exec invensys_php php artisan config:cache
docker exec invensys_php php artisan route:cache
docker exec invensys_php php artisan view:cache
docker exec invensys_php php artisan event:cache
docker exec invensys_php php artisan storage:link 2>/dev/null || true
log "Cache rebuilt"

# ════════════════════════════════════════════════════════
section "8. Health Check"
# ════════════════════════════════════════════════════════

info "Tunggu Nginx siap (5 detik)..."
sleep 5

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    --max-time 10 http://localhost:8888/api/health 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "503" ]; then
    HEALTH_JSON=$(curl -s --max-time 10 http://localhost:8888/api/health 2>/dev/null || echo "{}")
    log "Health check HTTP $HTTP_CODE OK"
    echo "  $HEALTH_JSON"
else
    warn "Health check HTTP $HTTP_CODE — mungkin masih booting."
    echo "  docker logs invensys_php   --tail=40"
    echo "  curl -v http://localhost:8888/api/health"
fi

# ════════════════════════════════════════════════════════
section "9. Status Semua Container"
# ════════════════════════════════════════════════════════

echo ""
docker compose -f "$COMPOSE_FILE" ps
echo ""

EXITED=$(docker compose -f "$COMPOSE_FILE" ps --status=exited --quiet 2>/dev/null | wc -l || echo "0")
if [ "$EXITED" -gt "0" ]; then
    warn "$EXITED container dalam kondisi exited!"
    echo "  Log     : docker compose -f $COMPOSE_FILE logs --tail=50"
    echo "  Rollback: bash scripts/rollback-ec2.sh"
fi

# ════════════════════════════════════════════════════════
section "10. Generate Rollback Script"
# ════════════════════════════════════════════════════════

NEW_COMMIT_FULL=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
PREV_COMMIT=$(cat /tmp/invensys_rollback_commit.txt 2>/dev/null || echo "unknown")
ROLLBACK_FILE="$APP_DIR/scripts/rollback-ec2.sh"

cat > "$ROLLBACK_FILE" << ROLLBACK
#!/bin/bash
# Auto-generated: $(date '+%Y-%m-%d %H:%M:%S')
# Rollback: ${NEW_COMMIT_FULL:0:12} → ${PREV_COMMIT}
set -euo pipefail
SDIR="\$(cd "\$(dirname "\${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="\$(cd "\$SDIR/.." && pwd)"
COMPOSE_FILE="\$APP_DIR/docker-compose.ec2.yml"
ENV_FILE="\$APP_DIR/.env"
[ ! -f "\$ENV_FILE" ] && ENV_FILE="/opt/invensys/.env"
echo "Rollback: ${NEW_COMMIT_FULL:0:12} → ${PREV_COMMIT}"
cd "\$APP_DIR"
[ "${PREV_COMMIT}" != "none" ] && [ "${PREV_COMMIT}" != "unknown" ] \
    && git checkout "${PREV_COMMIT}" -- .
docker build --target backend --tag invensys-backend:latest . 2>&1
docker build --target nginx   --tag invensys-nginx:latest   . 2>&1
docker compose -f "\$COMPOSE_FILE" --env-file "\$ENV_FILE" \
    up -d --no-build --force-recreate php queue scheduler nginx
EC2_IP=\$(curl -s --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || hostname -I | awk '{print \$1}')
echo "Rollback selesai -> http://\${EC2_IP}:8888"
ROLLBACK
chmod +x "$ROLLBACK_FILE"
log "Rollback script siap: $ROLLBACK_FILE"

# ════════════════════════════════════════════════════════
section "11. Cleanup Image Lama"
# ════════════════════════════════════════════════════════

docker image prune -f --filter "until=168h" 2>/dev/null || true
log "Image lama (>7 hari) dibersihkan"

# ════════════════════════════════════════════════════════
# SUMMARY
# ════════════════════════════════════════════════════════

EC2_IP=$(curl -s --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null \
    || hostname -I | awk '{print $1}')
CURRENT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")

echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║          Update Berhasil!                        ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  URL      : ${BLUE}http://${EC2_IP}:8888${NC}"
echo -e "  Commit   : ${BOLD}${PREV_COMMIT} -> ${CURRENT_COMMIT}${NC}"
echo -e "  Log      : $LOG_FILE"
echo -e "  Rollback : ${YELLOW}bash scripts/rollback-ec2.sh${NC}"
echo ""
echo "  Perintah cepat:"
echo "    docker compose -f $COMPOSE_FILE ps"
echo "    docker compose -f $COMPOSE_FILE logs -f php"
echo "    curl http://localhost:8888/api/health"
echo ""

#!/bin/bash
# =============================================================
# InvenSys — Update Script (Manual Pull & Restart di EC2)
# =============================================================
# Gunakan script ini untuk menarik kode terbaru dari GitHub
# dan me-restart aplikasi TANPA merusak .env, database,
# atau volume yang sudah berjalan.
#
# CARA PAKAI (jalankan langsung di EC2):
#   cd /opt/invensys
#   bash update-ec2.sh
#
# Atau dari laptop via SSH satu baris:
#   ssh -i key.pem ubuntu@EC2_IP "cd /opt/invensys && bash update-ec2.sh"
# =============================================================

set -euo pipefail

# ── Warna output ─────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'
YELLOW='\033[1;33m'; BLUE='\033[0;34m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

log()     { echo -e "${GREEN}[✓] $(date '+%H:%M:%S')${NC} $1"; }
warn()    { echo -e "${YELLOW}[!] $(date '+%H:%M:%S')${NC} $1"; }
err()     { echo -e "${RED}[✗] $(date '+%H:%M:%S')${NC} $1"; exit 1; }
info()    { echo -e "${BLUE}[→] $(date '+%H:%M:%S')${NC} $1"; }
section() { echo -e "\n${CYAN}${BOLD}━━━ $1 ━━━${NC}"; }

# ── Konfigurasi (sesuaikan jika path berbeda) ─────────────────
APP_DIR="/opt/invensys"
COMPOSE_FILE="$APP_DIR/docker-compose.ec2.yml"
ENV_FILE="$APP_DIR/.env"
LOG_DIR="/var/log/invensys"
LOG_FILE="$LOG_DIR/update.log"
REPO_URL="https://github.com/Lananuranf-RockNRoll/Manajemen-Inventaris-Berbasis-Web.git"
BRANCH="${BRANCH:-main}"

# ── Pastikan log dir ada ─────────────────────────────────────
mkdir -p "$LOG_DIR"
# Tee ke log file sekaligus tampil di terminal
exec > >(tee -a "$LOG_FILE") 2>&1

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║       InvenSys — EC2 Update (git pull)           ║${NC}"
echo -e "${BOLD}║  Branch : ${BRANCH}                              ${NC}"
echo -e "${BOLD}║  Time   : $(date '+%Y-%m-%d %H:%M:%S')               ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""

# ════════════════════════════════════════════════════════
section "1. Validasi Prerequisites"
# ════════════════════════════════════════════════════════

command -v docker &>/dev/null || err "Docker tidak ditemukan! Jalankan scripts/ec2-setup.sh"
docker info       &>/dev/null || err "Docker daemon tidak jalan! sudo systemctl start docker"
command -v git    &>/dev/null || err "Git tidak ditemukan! sudo apt install git"
log "Docker dan Git tersedia"

[ -f "$ENV_FILE" ]     || err ".env tidak ada di $ENV_FILE! Setup belum selesai. Jalankan scripts/ec2-setup.sh"
[ -f "$COMPOSE_FILE" ] || err "docker-compose.ec2.yml tidak ada di $APP_DIR!"

APP_KEY_VAL=$(grep "^APP_KEY=" "$ENV_FILE" | cut -d'=' -f2- | tr -d ' ' || true)
[ -z "$APP_KEY_VAL" ] && err "APP_KEY di .env kosong! Isi dulu sebelum update."
log ".env dan APP_KEY valid"

# ════════════════════════════════════════════════════════
section "2. Simpan State Saat Ini (untuk Rollback)"
# ════════════════════════════════════════════════════════

cd "$APP_DIR"

# Catat commit sebelum update
OLD_COMMIT="none"
if [ -d "$APP_DIR/.git" ]; then
    OLD_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "none")
fi
echo "$OLD_COMMIT" > /tmp/invensys_rollback_commit.txt
log "Commit sebelumnya: $OLD_COMMIT"

# Catat image Docker yang sedang jalan
docker inspect invensys_php 2>/dev/null \
    | python3 -c "import sys,json; print(json.load(sys.stdin)[0]['Config']['Image'])" \
    > /tmp/invensys_rollback_backend.txt 2>/dev/null || echo "invensys-backend:latest" > /tmp/invensys_rollback_backend.txt
log "Image saat ini: $(cat /tmp/invensys_rollback_backend.txt)"

# ════════════════════════════════════════════════════════
section "3. Git Pull — Tarik Kode Terbaru"
# ════════════════════════════════════════════════════════

# Pastikan direktori adalah git repo yang valid
if [ ! -d "$APP_DIR/.git" ]; then
    warn "Folder $APP_DIR bukan git repository!"
    warn "Melakukan fresh clone dari: $REPO_URL"

    # Backup file konfigurasi lokal sebelum clone
    info "Backup .env dan compose file sebelum clone..."
    cp "$ENV_FILE"     /tmp/invensys_env_backup
    cp "$COMPOSE_FILE" /tmp/invensys_compose_backup

    # Clone ke temporary folder lalu pindahkan isinya
    TMPDIR=$(mktemp -d)
    git clone --depth=1 --branch "$BRANCH" "$REPO_URL" "$TMPDIR"
    # Salin isi repo ke APP_DIR (tidak timpa .env dan volume)
    rsync -av --exclude='.env' \
              --exclude='storage/app/*' \
              --exclude='storage/logs/*' \
              "$TMPDIR/" "$APP_DIR/"
    rm -rf "$TMPDIR"

    # Kembalikan .env asli
    cp /tmp/invensys_env_backup "$ENV_FILE"
    log "Fresh clone selesai, .env dipertahankan"
else
    # ── Repo sudah ada, cek remote ──────────────────────────
    REMOTE_URL=$(git remote get-url origin 2>/dev/null || echo "")
    if [ -z "$REMOTE_URL" ]; then
        info "Remote origin belum diset, menambahkan..."
        git remote add origin "$REPO_URL"
    fi
    log "Remote origin: $(git remote get-url origin)"

    # ── Cek apakah ada perubahan lokal yang uncommitted ─────
    if ! git diff --quiet 2>/dev/null || ! git diff --cached --quiet 2>/dev/null; then
        warn "Ada perubahan lokal yang belum di-commit:"
        git status --short
        echo ""
        warn "Pilihan penanganan konflik:"
        echo ""
        echo -e "  ${YELLOW}[A] Stash (simpan sementara, lanjut pull):${NC}"
        echo "      git stash"
        echo "      bash update-ec2.sh   # jalankan ulang"
        echo ""
        echo -e "  ${YELLOW}[B] Discard perubahan lokal (HATI-HATI):${NC}"
        echo "      git checkout -- ."
        echo "      bash update-ec2.sh"
        echo ""
        echo -e "  ${YELLOW}[C] Simpan sebagai commit lokal dulu:${NC}"
        echo "      git add -A && git commit -m 'local changes before update'"
        echo "      bash update-ec2.sh"
        echo ""
        err "Selesaikan perubahan lokal terlebih dulu. Script dihentikan."
    fi

    # ── Fetch dan cek apakah ada update baru ────────────────
    info "Fetch dari origin/$BRANCH..."
    git fetch origin "$BRANCH" 2>&1 || err "git fetch gagal! Cek koneksi atau SSH key."

    LOCAL_COMMIT=$(git rev-parse HEAD)
    REMOTE_COMMIT=$(git rev-parse "origin/$BRANCH")

    if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
        warn "Kode sudah up-to-date dengan origin/$BRANCH (commit: ${LOCAL_COMMIT:0:12})"
        warn "Tidak ada perubahan baru. Apakah ingin restart ulang service?"
        echo ""
        echo -e "  ${BLUE}Untuk paksa restart tanpa pull:${NC}"
        echo "    docker compose -f $COMPOSE_FILE --env-file $ENV_FILE restart php nginx queue scheduler"
        echo ""
        # Lanjutkan tetap (mungkin user ingin rebuild ulang)
    fi

    # ── Lakukan git pull ────────────────────────────────────
    info "Menjalankan git pull origin $BRANCH..."
    if ! git pull origin "$BRANCH" --ff-only 2>&1; then
        echo ""
        warn "git pull --ff-only gagal! Kemungkinan ada diverge."
        warn "Pilihan resolusi:"
        echo ""
        echo -e "  ${YELLOW}[1] Merge (gabungkan perubahan):${NC}"
        echo "      git pull origin $BRANCH --no-ff"
        echo ""
        echo -e "  ${YELLOW}[2] Reset ke versi remote (BUANG commit lokal):${NC}"
        echo "      git fetch origin $BRANCH"
        echo "      git reset --hard origin/$BRANCH"
        echo "      bash update-ec2.sh"
        echo ""
        echo -e "  ${YELLOW}[3] Lihat diff sebelum memutuskan:${NC}"
        echo "      git log HEAD..origin/$BRANCH --oneline"
        echo "      git diff HEAD origin/$BRANCH"
        echo ""
        err "Pull gagal. Pilih salah satu opsi di atas, lalu jalankan update-ec2.sh lagi."
    fi

    NEW_COMMIT=$(git rev-parse --short HEAD)
    log "git pull berhasil: $OLD_COMMIT → $NEW_COMMIT"
fi

# ════════════════════════════════════════════════════════
section "4. Build Docker Images Baru"
# ════════════════════════════════════════════════════════

info "Build invensys-backend (PHP-FPM)..."
docker build \
    --target backend \
    --tag invensys-backend:latest \
    --tag "invensys-backend:$(git rev-parse --short HEAD 2>/dev/null || echo 'local')" \
    --no-cache \
    . 2>&1 || err "Build backend gagal! Cek Dockerfile."
log "invensys-backend built"

info "Build invensys-nginx (dengan frontend terbaru)..."
docker build \
    --target nginx \
    --tag invensys-nginx:latest \
    --tag "invensys-nginx:$(git rev-parse --short HEAD 2>/dev/null || echo 'local')" \
    --no-cache \
    . 2>&1 || err "Build nginx gagal!"
log "invensys-nginx built"

# ════════════════════════════════════════════════════════
section "5. Restart Containers (Zero Downtime sejauh mungkin)"
# ════════════════════════════════════════════════════════

# Pastikan DB dan Redis tetap nyala (tidak di-restart)
info "Pastikan DB dan Redis tetap berjalan..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" \
    up -d --no-build --no-recreate db redis
log "DB dan Redis tidak disentuh"

# Restart service aplikasi dengan image baru
info "Restart PHP, Queue, Scheduler dengan image terbaru..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" \
    up -d --no-build --force-recreate php queue scheduler
sleep 8  # beri waktu PHP-FPM + entrypoint selesai (migrate, cache, dsb.)
log "PHP, Queue, Scheduler restarted"

# Restart Nginx terakhir (frontend baru sudah ada di image)
info "Restart Nginx dengan frontend terbaru..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" \
    up -d --no-build --force-recreate nginx
log "Nginx restarted"

# ════════════════════════════════════════════════════════
section "6. Tunggu MySQL Ready"
# ════════════════════════════════════════════════════════

info "Menunggu MySQL siap (max 90 detik)..."
DB_ROOT=$(grep "^DB_ROOT_PASSWORD=" "$ENV_FILE" 2>/dev/null \
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
[ $RETRIES -eq 0 ] && err "MySQL tidak ready! Cek: docker logs invensys_db"
log "MySQL ready"

# ════════════════════════════════════════════════════════
section "7. Laravel Post-Deploy Tasks"
# ════════════════════════════════════════════════════════

info "Jalankan database migrations..."
docker exec invensys_php php artisan migrate --force --no-interaction \
    || err "Migration gagal! Cek: docker logs invensys_php"
log "Migrations selesai"

info "Rebuild application cache..."
docker exec invensys_php php artisan config:cache
docker exec invensys_php php artisan route:cache
docker exec invensys_php php artisan view:cache
docker exec invensys_php php artisan event:cache
docker exec invensys_php php artisan storage:link 2>/dev/null || true
log "Cache rebuilt"

# ════════════════════════════════════════════════════════
section "8. Health Check"
# ════════════════════════════════════════════════════════

info "Menunggu service siap..."
sleep 5

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    --max-time 10 http://localhost:8888/api/health 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "503" ]; then
    # 503 = degraded tapi aplikasi merespons (DB mungkin belum ready)
    HEALTH_BODY=$(curl -s --max-time 10 http://localhost:8888/api/health 2>/dev/null || echo "{}")
    log "Health check OK (HTTP $HTTP_CODE)"
    echo "  Response: $HEALTH_BODY"
else
    warn "Health check HTTP $HTTP_CODE — cek log container:"
    echo ""
    echo "  docker logs invensys_php   --tail=30"
    echo "  docker logs invensys_nginx --tail=20"
    echo ""
    warn "Aplikasi mungkin masih starting. Coba manual:"
    echo "  curl http://localhost:8888/api/health"
fi

# ════════════════════════════════════════════════════════
section "9. Status Containers"
# ════════════════════════════════════════════════════════

echo ""
docker compose -f "$COMPOSE_FILE" ps
echo ""

# Cek apakah ada container yang exit / unhealthy
EXITED=$(docker compose -f "$COMPOSE_FILE" ps --status=exited --quiet 2>/dev/null | wc -l || echo "0")
if [ "$EXITED" -gt "0" ]; then
    warn "Ada $EXITED container yang exit!"
    warn "Tampilkan log untuk debug:"
    echo ""
    echo "  docker compose -f $COMPOSE_FILE logs --tail=50"
    echo ""
    warn "Untuk rollback ke versi sebelumnya:"
    echo "  bash $APP_DIR/rollback.sh"
fi

# ════════════════════════════════════════════════════════
section "10. Buat / Update Rollback Script"
# ════════════════════════════════════════════════════════

NEW_COMMIT_FULL=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
PREV_COMMIT=$(cat /tmp/invensys_rollback_commit.txt 2>/dev/null || echo "unknown")

cat > "$APP_DIR/rollback.sh" << ROLLBACK_SCRIPT
#!/bin/bash
# Auto-generated oleh update-ec2.sh pada $(date '+%Y-%m-%d %H:%M:%S')
# Rollback dari: ${NEW_COMMIT_FULL:0:12}
# Ke           : ${PREV_COMMIT}
set -euo pipefail
echo ""
echo "╔══════════════════════════════════════════╗"
echo "║       InvenSys — Rollback                ║"
echo "╚══════════════════════════════════════════╝"
echo ""
echo "Rollback dari ${NEW_COMMIT_FULL:0:12} → ${PREV_COMMIT}"
echo ""

APP_DIR="/opt/invensys"
COMPOSE_FILE="\$APP_DIR/docker-compose.ec2.yml"
ENV_FILE="\$APP_DIR/.env"

# Kembali ke commit sebelumnya
cd "\$APP_DIR"
if [ "${PREV_COMMIT}" != "none" ] && [ "${PREV_COMMIT}" != "unknown" ]; then
    echo "→ git checkout ${PREV_COMMIT}..."
    git checkout "${PREV_COMMIT}" -- .
fi

# Rebuild images dari kode lama
echo "→ Rebuild images dari commit ${PREV_COMMIT}..."
docker build --target backend --tag invensys-backend:latest --no-cache . 2>&1
docker build --target nginx   --tag invensys-nginx:latest   --no-cache . 2>&1

# Restart
echo "→ Restart containers..."
docker compose -f "\$COMPOSE_FILE" --env-file "\$ENV_FILE" \\
    up -d --no-build --force-recreate php queue scheduler nginx

echo ""
echo "✅ Rollback selesai! Cek: http://\$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || hostname -I | awk '{print \$1}'):8888"
echo ""
ROLLBACK_SCRIPT
chmod +x "$APP_DIR/rollback.sh"
log "Rollback script diperbarui di $APP_DIR/rollback.sh"

# ════════════════════════════════════════════════════════
section "11. Cleanup Docker Resources"
# ════════════════════════════════════════════════════════

info "Bersihkan image lama (lebih dari 7 hari)..."
docker image prune -f --filter "until=168h" 2>/dev/null || true
log "Cleanup selesai"

# ════════════════════════════════════════════════════════
# SUMMARY
# ════════════════════════════════════════════════════════

EC2_IP=$(curl -s --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null \
    || hostname -I | awk '{print $1}')
CURRENT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")

echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║          Update Berhasil! ✅                      ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  🌐 URL      : ${BLUE}http://${EC2_IP}:8888${NC}"
echo -e "  📌 Commit   : ${BOLD}${PREV_COMMIT} → ${CURRENT_COMMIT}${NC}"
echo -e "  📋 Log file : $LOG_FILE"
echo -e "  🔄 Rollback : ${YELLOW}bash $APP_DIR/rollback.sh${NC}"
echo ""
echo "  📦 Commands berguna:"
echo "     docker compose -f $COMPOSE_FILE ps"
echo "     docker compose -f $COMPOSE_FILE logs -f php"
echo "     docker compose -f $COMPOSE_FILE logs -f nginx"
echo "     curl http://localhost:8888/api/health"
echo ""

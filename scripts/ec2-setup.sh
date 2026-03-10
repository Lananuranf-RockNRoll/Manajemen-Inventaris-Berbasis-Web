#!/bin/bash
# =============================================================
# InvenSys — EC2 Initial Setup Script
# Jalankan SEKALI di EC2 baru sebelum pertama kali deploy:
#
#   scp -i key.pem scripts/ec2-setup.sh ubuntu@EC2_IP:/tmp/
#   ssh -i key.pem ubuntu@EC2_IP
#   sudo bash /tmp/ec2-setup.sh
#
# Kompatibel: Ubuntu 22.04 / Amazon Linux 2023
# =============================================================

set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'
YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

log()  { echo -e "${GREEN}[OK] ${NC} $1"; }
warn() { echo -e "${YELLOW}[!!] ${NC} $1"; }
err()  { echo -e "${RED}[ERR]${NC} $1"; exit 1; }
info() { echo -e "${BLUE}[-->]${NC} $1"; }

# Deteksi OS
. /etc/os-release 2>/dev/null || true
OS_ID="${ID:-unknown}"
DEPLOY_USER="${SUDO_USER:-ubuntu}"

echo ""
echo "======================================================"
echo "  InvenSys -- EC2 Initial Setup"
echo "  OS   : $OS_ID"
echo "  User : $DEPLOY_USER"
echo "======================================================"
echo ""

# ── Install Docker ───────────────────────────────────────────
info "Install Docker..."
if command -v docker &>/dev/null; then
    warn "Docker sudah ada: $(docker --version)"
else
    if [[ "$OS_ID" == "amzn" ]]; then
        sudo dnf install -y docker
        sudo systemctl enable --now docker
    elif [[ "$OS_ID" == "ubuntu" ]]; then
        sudo apt-get update -qq
        sudo apt-get install -y ca-certificates curl gnupg
        sudo install -m 0755 -d /etc/apt/keyrings
        curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
            | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
        sudo chmod a+r /etc/apt/keyrings/docker.gpg
        echo "deb [arch=$(dpkg --print-architecture) \
            signed-by=/etc/apt/keyrings/docker.gpg] \
            https://download.docker.com/linux/ubuntu \
            $(lsb_release -cs) stable" \
            | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
        sudo apt-get update -qq
        sudo apt-get install -y \
            docker-ce docker-ce-cli containerd.io docker-compose-plugin
        sudo systemctl enable --now docker
    else
        err "OS '$OS_ID' tidak dikenali. Install Docker manual."
    fi
    log "Docker terinstall: $(docker --version)"
fi

# ── User ke group docker ─────────────────────────────────────
sudo usermod -aG docker "$DEPLOY_USER" 2>/dev/null || true
log "User '$DEPLOY_USER' ditambahkan ke group docker"

# ── Install tools ─────────────────────────────────────────────
info "Install tools pendukung..."
if [[ "$OS_ID" == "amzn" ]]; then
    sudo dnf install -y git curl wget python3 htop
elif [[ "$OS_ID" == "ubuntu" ]]; then
    sudo apt-get install -y git curl wget python3 htop
fi
log "Tools siap"

# ── Direktori project ────────────────────────────────────────
info "Membuat direktori /opt/invensys..."
sudo mkdir -p /opt/invensys
sudo mkdir -p /var/log/invensys
sudo chown -R "$DEPLOY_USER:$DEPLOY_USER" /opt/invensys
sudo chown -R "$DEPLOY_USER:$DEPLOY_USER" /var/log/invensys
log "Direktori siap"

# ── Template .env production ─────────────────────────────────
ENV_FILE="/opt/invensys/.env"
if [ ! -f "$ENV_FILE" ]; then
    info "Membuat template .env..."
    # Template ini mengikuti persis .env.example dari project
    sudo tee "$ENV_FILE" > /dev/null << 'ENVEOF'
APP_NAME=InvenSys
APP_ENV=production
APP_KEY=                          # [WAJIB] php artisan key:generate --show
APP_DEBUG=false
APP_URL=http://YOUR_EC2_IP:8888   # [WAJIB] ganti YOUR_EC2_IP

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=invensys
DB_PASSWORD=                      # [WAJIB] password kuat min 16 karakter
DB_ROOT_PASSWORD=                 # [WAJIB] root password kuat min 16 karakter

SESSION_DRIVER=redis
SESSION_LIFETIME=120
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
CACHE_STORE=redis

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@invensys.app
MAIL_FROM_NAME=InvenSys
ENVEOF
    sudo chown "$DEPLOY_USER:$DEPLOY_USER" "$ENV_FILE"
    sudo chmod 600 "$ENV_FILE"
    warn "Template .env dibuat di $ENV_FILE"
    warn "WAJIB isi sebelum deploy: APP_KEY, APP_URL, DB_PASSWORD, DB_ROOT_PASSWORD"
else
    warn ".env sudah ada, tidak ditimpa"
fi

# ── Log rotation ─────────────────────────────────────────────
info "Setup log rotation..."
sudo tee /etc/logrotate.d/invensys > /dev/null << 'LOGEOF'
/var/log/invensys/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0644 ubuntu ubuntu
}
LOGEOF
log "Log rotation: 14 hari"

# ── Docker daemon config ─────────────────────────────────────
if [ ! -f /etc/docker/daemon.json ]; then
    info "Konfigurasi Docker daemon..."
    sudo tee /etc/docker/daemon.json > /dev/null << 'DOCKEREOF'
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "100m",
    "max-file": "3"
  },
  "live-restore": true
}
DOCKEREOF
    sudo systemctl reload docker 2>/dev/null || sudo systemctl restart docker
    log "Docker daemon dikonfigurasi"
fi

# ── SSH hardening ────────────────────────────────────────────
info "SSH: nonaktifkan password auth..."
if grep -q "^PasswordAuthentication" /etc/ssh/sshd_config 2>/dev/null; then
    sudo sed -i 's/^PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
else
    echo "PasswordAuthentication no" | sudo tee -a /etc/ssh/sshd_config > /dev/null
fi
log "SSH hanya menerima key-based auth"

# ── Cek port 8888 ────────────────────────────────────────────
EC2_IP=$(curl -s --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null \
    || echo "IP_EC2_KAMU")

# ── Summary ───────────────────────────────────────────────────
echo ""
echo "======================================================"
echo "  Setup Selesai! Langkah selanjutnya:"
echo "======================================================"
echo ""
echo "  1. Isi .env production:"
echo "     nano /opt/invensys/.env"
echo "     -> APP_KEY   = php artisan key:generate --show"
echo "     -> APP_URL   = http://${EC2_IP}:8888"
echo "     -> DB_PASSWORD      = password kuat"
echo "     -> DB_ROOT_PASSWORD = password kuat"
echo ""
echo "  2. Buka port 8888 di AWS Security Group:"
echo "     AWS Console -> EC2 -> Security Groups"
echo "     -> Inbound Rules -> Add: TCP 8888, 0.0.0.0/0"
echo ""
echo "  3. Tambahkan GitHub Secrets di repository:"
echo "     EC2_HOST    = ${EC2_IP}"
echo "     EC2_USER    = ${DEPLOY_USER}"
echo "     EC2_SSH_KEY = (isi konten file .pem)"
echo ""
echo "  4. git push ke branch main -> pipeline otomatis jalan!"
echo ""
warn "PENTING: logout dan login ulang agar group docker aktif."
warn "Atau jalankan: newgrp docker"
echo ""

#!/bin/bash
# MOKA — Production Deploy Script
# Run this once on your GCP VM as root
# Usage: bash deploy.sh

set -e

REPO="https://github.com/johnyap511/moka.git"
BRANCH="claude/continue-previous-session-QztqY"
APP_DIR="/var/www/moka"
PHP="php8.2"

echo "========================================="
echo "  MOKA Production Deployment"
echo "========================================="

# ── 1. System packages ────────────────────────────────────────────────
echo "[1/8] Installing system packages..."
apt-get update -qq
apt-get install -y -qq \
    nginx git unzip curl \
    php8.2 php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-curl php8.2-zip php8.2-mysql \
    php8.2-sqlite3 php8.2-gd php8.2-intl \
    mysql-server 2>/dev/null || \
apt-get install -y -qq \
    nginx git unzip curl \
    php php-fpm php-cli php-mbstring php-xml \
    php-bcmath php-curl php-zip php-mysql \
    php-sqlite3 php-gd php-intl \
    mysql-server

# ── 2. Composer ───────────────────────────────────────────────────────
echo "[2/8] Installing Composer..."
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# ── 3. Clone / pull repo ──────────────────────────────────────────────
echo "[3/8] Cloning repository..."
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
    git fetch origin
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
else
    git clone -b "$BRANCH" "$REPO" "$APP_DIR"
    cd "$APP_DIR"
fi

# ── 4. PHP dependencies ───────────────────────────────────────────────
echo "[4/8] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || \
composer install --optimize-autoloader --no-interaction

# ── 5. Environment setup ──────────────────────────────────────────────
echo "[5/8] Setting up environment..."
if [ ! -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env.example" "$APP_DIR/.env" 2>/dev/null || cat > "$APP_DIR/.env" << 'ENVEOF'
APP_NAME=MOKA
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_SERVER_IP

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moka
DB_USERNAME=moka
DB_PASSWORD=Moka@Prod2026

SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
ENVEOF
fi

# Generate app key if missing
php artisan key:generate --force 2>/dev/null || true

# ── 6. Database setup (MySQL) ─────────────────────────────────────────
echo "[6/8] Setting up MySQL database..."
systemctl start mysql 2>/dev/null || service mysql start 2>/dev/null || true

mysql -u root 2>/dev/null <<SQLEOF || true
CREATE DATABASE IF NOT EXISTS moka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'moka'@'localhost' IDENTIFIED BY 'Moka@Prod2026';
GRANT ALL PRIVILEGES ON moka.* TO 'moka'@'localhost';
FLUSH PRIVILEGES;
SQLEOF

# Update .env for MySQL
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' "$APP_DIR/.env"
sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' "$APP_DIR/.env"
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=moka/' "$APP_DIR/.env"
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=moka/' "$APP_DIR/.env"
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=Moka@Prod2026/' "$APP_DIR/.env"
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' "$APP_DIR/.env"
sed -i 's/^APP_ENV=.*/APP_ENV=production/' "$APP_DIR/.env"

# Get the server's public IP and update APP_URL
SERVER_IP=$(curl -s --max-time 5 http://metadata.google.internal/computeMetadata/v1/instance/network-interfaces/0/access-configs/0/externalIp -H "Metadata-Flavor: Google" 2>/dev/null || hostname -I | awk '{print $1}')
sed -i "s|^APP_URL=.*|APP_URL=http://$SERVER_IP|" "$APP_DIR/.env"

echo "  Server IP detected: $SERVER_IP"

# ── 7. Laravel setup ──────────────────────────────────────────────────
echo "[7/8] Running Laravel setup..."
cd "$APP_DIR"

php artisan migrate --force 2>/dev/null || true
php artisan db:seed --force 2>/dev/null || true
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ── 8. Nginx config ───────────────────────────────────────────────────
echo "[8/8] Configuring Nginx..."

# Detect PHP-FPM socket
FPM_SOCK=$(find /var/run/php/ -name "*.sock" 2>/dev/null | head -1)
[ -z "$FPM_SOCK" ] && FPM_SOCK="/var/run/php/php8.2-fpm.sock"

cat > /etc/nginx/sites-available/moka << NGINXEOF
server {
    listen 80;
    listen [::]:80;
    server_name $SERVER_IP _;

    root $APP_DIR/public;
    index index.php index.html;

    client_max_body_size 64M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:$FPM_SOCK;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;
}
NGINXEOF

ln -sf /etc/nginx/sites-available/moka /etc/nginx/sites-enabled/moka
rm -f /etc/nginx/sites-enabled/default

# Start/restart services
systemctl enable php8.2-fpm nginx 2>/dev/null || systemctl enable php-fpm nginx 2>/dev/null || true
systemctl restart php8.2-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
nginx -t && systemctl restart nginx

echo ""
echo "========================================="
echo "  MOKA Deployment Complete!"
echo "========================================="
echo ""
echo "  Access your site at: http://$SERVER_IP"
echo ""
echo "  Login credentials:"
echo "  Admin : admin@moka.app  / Admin2026"
echo "  Owner : owner@moka.app  / Owner2026"
echo "  Guest : user@moka.app   / User2026"
echo ""
echo "========================================="

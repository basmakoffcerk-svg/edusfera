#!/bin/bash
set -euo pipefail

# ─── edusfera.by — Server Setup Script ──────────────────────────────────────
# Run this ONCE on a fresh Ubuntu 22.04/24.04 server as root.
# Usage: bash server-setup.sh

echo "🚀 Setting up edusfera.by production server..."

# ─── System updates ─────────────────────────────────────────────────────────

apt update && apt upgrade -y
apt install -y \
  curl git unzip software-properties-common \
  ufw fail2ban \
  certbot python3-certbot-nginx

# ─── PHP 8.3 ────────────────────────────────────────────────────────────────

add-apt-repository ppa:ondrej/php -y
apt update
apt install -y \
  php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml \
  php8.3-bcmath php8.3-intl php8.3-curl php8.3-gd php8.3-zip \
  php8.3-opcache php8.3-redis php8.3-sqlite3

# ─── Nginx ──────────────────────────────────────────────────────────────────

apt install -y nginx
systemctl enable nginx

# ─── Node.js 22 ─────────────────────────────────────────────────────────────

curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs

# ─── Composer ───────────────────────────────────────────────────────────────

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ─── PostgreSQL client (for backups) ────────────────────────────────────────

apt install -y postgresql-client

# ─── Deploy user ────────────────────────────────────────────────────────────

adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy

# ─── App directory ──────────────────────────────────────────────────────────

mkdir -p /var/www/edusfera
chown deploy:www-data /var/www/edusfera
chmod 775 /var/www/edusfera

# ─── SSH key for GitHub Actions ─────────────────────────────────────────────

mkdir -p /home/deploy/.ssh
touch /home/deploy/.ssh/authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh

echo ""
echo "📋 Next steps:"
echo "  1. Add your SSH public key to /home/deploy/.ssh/authorized_keys"
echo "  2. Clone the repo: cd /var/www/edusfera && git clone git@github.com:yourorg/edusfera.by.git ."
echo "  3. Copy .env.production to .env and fill in secrets"
echo "  4. Copy nginx config: cp nginx/nginx.conf /etc/nginx/sites-available/edusfera"
echo "  5. ln -s /etc/nginx/sites-available/edusfera /etc/nginx/sites-enabled/"
echo "  6. Run: certbot --nginx -d edusfera.by -d www.edusfera.by"
echo "  7. Add GitHub Actions secrets: PRODUCTION_HOST, PRODUCTION_USER, PRODUCTION_SSH_KEY"
echo ""
echo "✅ Server setup complete!"

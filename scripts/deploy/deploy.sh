#!/bin/bash
set -euo pipefail

# ─── edusfera.by deployment script ──────────────────────────────────────────
# Runs on the production server via GitHub Actions SSH.
# Usage: deploy.sh [staging|production] [--docker]

ENV="${1:-production}"
MODE="${2:-auto}"
APP_DIR="/var/www/edusfera"
BACKUP_DIR="/var/backups/edusfera"
DATE=$(date +%Y%m%d_%H%M%S)

cd "$APP_DIR"

# ─── Detect deployment mode ────────────────────────────────────────────────

if [ "$MODE" = "auto" ]; then
  if [ -f "docker-compose.prod.yml" ] && command -v docker &> /dev/null; then
    MODE="docker"
  else
    MODE="bare-metal"
  fi
fi

echo "🚀 Deploying edusfera.by to ${ENV} (mode: ${MODE})..."

# ─── Pre-deploy backup ──────────────────────────────────────────────────────

echo "📦 Creating pre-deploy backup..."
mkdir -p "$BACKUP_DIR"

if [ "$MODE" = "docker" ]; then
  docker compose -f docker-compose.prod.yml exec -T db \
    pg_dump -U "${DB_USERNAME}" "${DB_DATABASE}" | gzip > "$BACKUP_DIR/pre-deploy-${DATE}.sql.gz"
else
  pg_dump -U "${DB_USERNAME}" "${DB_DATABASE}" | gzip > "$BACKUP_DIR/pre-deploy-${DATE}.sql.gz"
fi

# ─── Pull code ──────────────────────────────────────────────────────────────

echo "📥 Pulling latest code..."
git fetch origin
git reset --hard "origin/${ENV}"

# ─── Dependencies ───────────────────────────────────────────────────────────

echo "📚 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress
npm ci --no-audit
npm run build

# ─── Laravel optimizations ──────────────────────────────────────────────────

echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# ─── Database migrations ───────────────────────────────────────────────────

echo "🗄️ Running migrations..."
php artisan migrate --force

# ─── Queue restart ──────────────────────────────────────────────────────────

echo "🔄 Restarting queue workers..."
php artisan queue:restart

# ─── Permissions ────────────────────────────────────────────────────────────

echo "🔒 Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ─── Reload services ────────────────────────────────────────────────────────

if [ "$MODE" = "docker" ]; then
  echo "🐳 Rebuilding and restarting Docker services..."
  docker compose -f docker-compose.prod.yml build app
  docker compose -f docker-compose.prod.yml up -d --force-recreate --no-deps app queue scheduler
  docker compose -f docker-compose.prod.yml exec -T nginx nginx -s reload
else
  echo "🔧 Reloading Nginx..."
  nginx -t && systemctl reload nginx

  echo "🔧 Reloading PHP-FPM..."
  systemctl reload php8.3-fpm
fi

# ─── Post-deploy verification ──────────────────────────────────────────────

echo "✅ Running health check..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
  echo "✅ Health check passed (HTTP ${HTTP_CODE})"
else
  echo "⚠️ Health check returned HTTP ${HTTP_CODE} — check logs"
fi

echo "🎉 Deployment to ${ENV} complete!"

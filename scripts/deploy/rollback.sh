#!/bin/bash
set -euo pipefail

# ─── edusfera.by Rollback Script ────────────────────────────────────────────
# Rolls back to the previous git commit on production.
# Usage: rollback.sh [commit-hash] [--docker]

TARGET="${1:-}"
MODE="${2:-auto}"
APP_DIR="/var/www/edusfera"

cd "$APP_DIR"

# ─── Detect deployment mode ────────────────────────────────────────────────

if [ "$MODE" = "auto" ]; then
  if [ -f "docker-compose.prod.yml" ] && command -v docker &> /dev/null; then
    MODE="docker"
  else
    MODE="bare-metal"
  fi
fi

# ─── Resolve target commit ─────────────────────────────────────────────────

if [ -z "$TARGET" ] || [ "$TARGET" = "--docker" ]; then
  TARGET=$(git log --oneline -2 | tail -1 | awk '{print $1}')
fi

echo "⏪ Rolling back to: $TARGET (mode: ${MODE})"
echo "Current HEAD: $(git log --oneline -1)"

read -p "Continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "Aborted."
  exit 1
fi

# ─── Rollback code ─────────────────────────────────────────────────────────

git reset --hard "$TARGET"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit && npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan queue:restart

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ─── Reload services ────────────────────────────────────────────────────────

if [ "$MODE" = "docker" ]; then
  docker compose -f docker-compose.prod.yml up -d --force-recreate --no-deps app queue scheduler
  docker compose -f docker-compose.prod.yml exec -T nginx nginx -s reload
else
  nginx -t && systemctl reload nginx
  systemctl reload php8.3-fpm
fi

echo "⏪ Rollback to $TARGET complete!"

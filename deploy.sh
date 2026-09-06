#!/usr/bin/env bash

# Exit on error
set -e

echo "🚀 Starting Edusfera Production Deployment..."

# 1. Enable Maintenance Mode
echo "🔒 Enabling maintenance mode..."
php artisan down --refresh=15 --retry=60 || true

# 2. Update Code & Dependencies
echo "🔒 Securing .env file permissions..."
if [ -f .env ]; then
    chmod 600 .env
fi

echo "📦 Optimizing Composer Autoloader..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 3. Build Frontend Assets
echo "🎨 Building Production Frontend Assets..."
npm ci --quiet || npm install --quiet
npm run build

# 4. Storage Link & Temporary Upload Directories (open_basedir fix)
echo "🔗 Verifying Storage Link & Temporary Directories..."
mkdir -p storage/app/tmp storage/app/livewire-tmp storage/app/private storage/app/public/avatars /var/www/u41698-5508/data/tmp || true
chmod -R 777 storage/app/tmp storage/app/livewire-tmp /var/www/u41698-5508/data/tmp || true
php artisan storage:link || true

# 5. Database Migrations
echo "🗄️ Running Database Migrations..."
php artisan migrate --force

# 6. Clear and Cache Configurations
echo "⚡ Caching Configurations, Routes, and Views..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize || true

# 7. Restart Queue Workers & Horizon/Reverb
echo "🔄 Restarting Queue Workers..."
php artisan queue:restart || true

# 8. Disable Maintenance Mode
echo "🔓 Disabling maintenance mode..."
php artisan up

echo "✅ Edusfera Production Deployment Complete Successfully!"

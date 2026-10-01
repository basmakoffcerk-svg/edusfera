#!/usr/bin/env bash
set -e

# ==============================================================================
# EDUSFERA.BY — PRODUCTION SERVER INITIAL SETUP & POST-DEPLOY SCRIPT
# ==============================================================================

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

echo "🚀 Настройка платформы Edusfera в: $PROJECT_DIR"

# 1. Проверка наличия .env файла
if [ ! -f .env ]; then
    if [ -f .env.production.example ]; then
        echo "⚠️ Файл .env не найден. Копируем из .env.production.example..."
        cp .env.production.example .env
        php artisan key:generate --force
        echo "❗️ Отредактируйте .env перед запуском в продакшн (укажите БД, пароли, Alfa-Bank API)!"
    else
        echo "❌ Ошибка: .env не найден!"
        exit 1
    fi
fi

# 2. Права доступа к директориям storage и bootstrap/cache
echo "🔒 Установка прав на директории storage и cache..."
mkdir -p storage/framework/{sessions,views,cache} storage/logs storage/app/public/avatars storage/app/tmp storage/app/livewire-tmp bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# 3. Символическая ссылка для хранилища
echo "🔗 Создание symlink storage..."
php artisan storage:link || true

# 4. Выполнение миграций базы данных
echo "🗄️ Выполнение миграций базы данных..."
php artisan migrate --force

# 5. Оптимизация и кэширование конфигурации Laravel
echo "⚡ Оптимизация кэша конфигурации, маршрутов и представлений..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize || true

# 6. Перезапуск воркеров очереди
echo "🔄 Перезапуск обработчиков очередей..."
php artisan queue:restart || true

echo "✅ Платформа Edusfera готова к работе в продакшн!"

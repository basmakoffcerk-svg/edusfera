#!/usr/bin/env bash
set -e

# ==============================================================================
# EDUSFERA.BY — PACKAGING DEPLOYMENT ARCHIVES
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "📦 Подготовка платформы Edusfera к архивации..."

# 1. Проверка сборки фронтенда
if [ ! -f "public/build/manifest.json" ]; then
    echo "🎨 Сборка фронтенд-ассетов..."
    npm run build
fi

# 2. Очистка временных файлов кэша и логов перед упаковкой
echo "🧹 Очистка локального кэша и временных файлов..."
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf storage/logs/*.log
find . -name ".DS_Store" -type f -delete 2>/dev/null || true

# Создание необходимых подпапок storage с защитными файлами
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/app/public/avatars
mkdir -p storage/app/{tmp,livewire-tmp,private}
mkdir -p storage/logs
mkdir -p bootstrap/cache

touch storage/framework/cache/.gitkeep
touch storage/framework/sessions/.gitkeep
touch storage/framework/views/.gitkeep
touch storage/logs/.gitkeep
touch bootstrap/cache/.gitkeep

# 3. Список исключений для архивов
EXCLUDES=(
    --exclude="./.git"
    --exclude="./.github"
    --exclude="./.codegraph"
    --exclude="./.venv"
    --exclude="./.vscode"
    --exclude="./node_modules"
    --exclude="./tests"
    --exclude="./scratch"
    --exclude="./.phpunit.result.cache"
    --exclude="./.env"
    --exclude="./.DS_Store"
    --exclude="./*/.DS_Store"
    --exclude="./*/*/.DS_Store"
    --exclude="./storage/logs/*.log"
    --exclude="./storage/framework/sessions/*"
    --exclude="./storage/framework/views/*"
    --exclude="./storage/framework/cache/*"
    --exclude="./*.tar.gz"
    --exclude="./*.zip"
    --exclude="./edusfera-ledger"
    --exclude="./edusfera-media"
    --exclude="./edusfera-workspace"
    --exclude="./Документы"
    --exclude="./public/storage"
    --exclude="./screenshots"
)

echo "📦 1/4 Создание edusfera-deploy-full.tar.gz (включая vendor/ и public/build/)..."
tar -czf edusfera-deploy-full.tar.gz "${EXCLUDES[@]}" .

echo "📦 2/4 Создание edusfera-deploy-full.zip (включая vendor/ и public/build/)..."
# Используем zip с исключениями
zip -q -r edusfera-deploy-full.zip . \
    -x "./.git/*" \
    -x "./.github/*" \
    -x "./.codegraph/*" \
    -x "./.venv/*" \
    -x "./.vscode/*" \
    -x "./node_modules/*" \
    -x "./tests/*" \
    -x "./scratch/*" \
    -x "./.phpunit.result.cache" \
    -x "./.env" \
    -x "./.DS_Store" \
    -x "*/**/.DS_Store" \
    -x "./storage/logs/*.log" \
    -x "./storage/framework/sessions/*" \
    -x "./storage/framework/views/*" \
    -x "./storage/framework/cache/*" \
    -x "./*.tar.gz" \
    -x "./*.zip" \
    -x "./edusfera-ledger/*" \
    -x "./edusfera-media/*" \
    -x "./edusfera-workspace/*" \
    -x "./Документы/*" \
    -x "./Документы на платформу/*" \
    -x "./public/storage*" \
    -x "./screenshots/*"

echo "📦 3/4 Создание edusfera-source.tar.gz (чистый исходный код без vendor)..."
tar -czf edusfera-source.tar.gz "${EXCLUDES[@]}" --exclude="./vendor" .

echo "📦 4/4 Создание edusfera-source.zip (чистый исходный код без vendor)..."
zip -q -r edusfera-source.zip . \
    -x "./vendor/*" \
    -x "./.git/*" \
    -x "./.github/*" \
    -x "./.codegraph/*" \
    -x "./.venv/*" \
    -x "./.vscode/*" \
    -x "./node_modules/*" \
    -x "./tests/*" \
    -x "./scratch/*" \
    -x "./.phpunit.result.cache" \
    -x "./.env" \
    -x "./.DS_Store" \
    -x "*/**/.DS_Store" \
    -x "./storage/logs/*.log" \
    -x "./storage/framework/sessions/*" \
    -x "./storage/framework/views/*" \
    -x "./storage/framework/cache/*" \
    -x "./*.tar.gz" \
    -x "./*.zip" \
    -x "./edusfera-ledger/*" \
    -x "./edusfera-media/*" \
    -x "./edusfera-workspace/*" \
    -x "./Документы/*" \
    -x "./Документы на платформу/*" \
    -x "./Требования, примеры, логотипы.pdf" \
    -x "./public/storage*" \
    -x "./screenshots/*"

echo "✅ Архивы успешно созданы:"
ls -lh edusfera-deploy-full.tar.gz edusfera-deploy-full.zip edusfera-source.tar.gz edusfera-source.zip

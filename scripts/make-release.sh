#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "🚀 [1/4] Сборка фронтенд-ассетов через Vite..."
npm run build

echo "📄 [2/4] Проверка конфигурации public/.htaccess..."
cat << 'EOF' > public/.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOF

echo "🧹 [3/4] Очистка старых архивов..."
rm -f edusfera-release.zip

echo "📦 [4/4] Создание готового архива edusfera-release.zip для сервера..."
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
touch storage/framework/views/.gitkeep storage/framework/cache/.gitkeep storage/framework/sessions/.gitkeep storage/logs/.gitkeep

zip -r edusfera-release.zip . \
    -x "node_modules/*" \
    -x ".git/*" \
    -x ".github/*" \
    -x ".venv/*" \
    -x ".codegraph/*" \
    -x ".agent/*" \
    -x ".agents/*" \
    -x "storage/logs/*.log" \
    -x "storage/framework/cache/data/*" \
    -x "storage/framework/sessions/*" \
    -x "storage/framework/views/*.php" \
    -x "edusfera-release.zip" \
    -x ".DS_Store"

echo ""
echo "✅ АРХИВ УСПЕШНО СОЗДАН!"
echo "📍 Файл: /Users/sergei/Desktop/edusfera.by/edusfera-release.zip"
ls -lh edusfera-release.zip

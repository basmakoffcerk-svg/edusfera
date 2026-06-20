FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
    git icu-dev libpq-dev oniguruma-dev \
    && docker-php-ext-install pdo_pgsql intl bcmath opcache pcntl mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (cacheable layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application
COPY . .

# Build frontend assets
RUN npm ci --no-audit && npm run build

# Laravel optimizations
RUN php artisan route:cache \
    && php artisan config:cache \
    && php artisan view:cache \
    && php artisan icons:cache

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

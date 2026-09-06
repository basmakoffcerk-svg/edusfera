# Stage 1: Build frontend assets
FROM node:22-alpine AS frontend-builder
WORKDIR /app
COPY package.json package-lock.json vite.config.js tailwind.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/
RUN npm ci --no-audit && npm run build

# Stage 2: Runtime PHP environment
FROM php:8.3-fpm-alpine
RUN apk add --no-cache \
    git icu-dev libpq-dev oniguruma-dev \
    && docker-php-ext-install pdo_pgsql intl bcmath opcache pcntl mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application files
COPY . .

# Copy built frontend assets
COPY --from=frontend-builder /app/public/build ./public/build

# Laravel optimizations
RUN php artisan route:cache \
    && php artisan config:cache \
    && php artisan view:cache \
    && php artisan icons:cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

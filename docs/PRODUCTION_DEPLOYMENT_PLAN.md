# План полноценного прода edusfera.by

## Фаза 1: Инфраструктура (неделя 1)

### 1.1 Выбор хостинга

**Рекомендация:** Hetzner Cloud (EU, дешево) или DigitalOcean

| Компонент | Hetzner CX32 | DigitalOcean |
|-----------|--------------|--------------|
| CPU/RAM | 4 vCPU / 8 GB | 4 vCPU / 8 GB |
| Storage | 80 GB NVMe | 80 GB SSD |
| Cost | ~€15/мес | $48/мес |
| Location | Нюрнберг (EU) | Франкфурт (EU) |

**Минимальная конфигурация:**
- 1x сервер приложения (Laravel + Nginx + PHP-FPM)
- 1x сервер БД (PostgreSQL 16)
- 1x managed Redis (Upstash / Redis Cloud / Hetzner)
-域名: edusfera.by

### 1.2 Docker-сборка для Laravel

Создать `Dockerfile`:

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git icu-dev libpq-dev \
    && docker-php-ext-install pdo_pgsql intl bcmath opcache pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .
RUN php artisan route:cache && php artisan config:cache && php artisan view:cache

EXPOSE 9000
CMD ["php-fpm"]
```

Nginx конфиг (`nginx.conf`):
```nginx
server {
    listen 80;
    server_name edusfera.by www.edusfera.by;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name edusfera.by www.edusfera.by;

    ssl_certificate /etc/letsencrypt/live/edusfera.by/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/edusfera.by/privkey.pem;

    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 1.3 Docker Compose для прода

```yaml
services:
  app:
    build: .
    restart: unless-stopped
    env_file: .env.production
    volumes:
      - storage:/var/www/html/storage
    networks:
      - edusfera

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - ./certbot/conf:/etc/letsencrypt
    depends_on:
      - app
    networks:
      - edusfera

  queue:
    build: .
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    restart: unless-stopped
    env_file: .env.production
    networks:
      - edusfera

  scheduler:
    build: .
    command: php artisan schedule:work
    restart: unless-stopped
    env_file: .env.production
    networks:
      - edusfera

  media:
    build: ./edusfera-media
    ports:
      - "8088:8088"
    env_file: .env.production
    networks:
      - edusfera

volumes:
  storage:

networks:
  edusfera:
    driver: bridge
```

---

## Фаза 2: Конфигурация (неделя 1-2)

### 2.1 Production .env

```bash
APP_NAME=Edusfera
APP_ENV=production
APP_KEY=base64:<сгенерировать>
APP_DEBUG=false
APP_URL=https://edusfera.by

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=edusfera
DB_USERNAME=edusfera
DB_PASSWORD=<сложный_пароль>

SESSION_DRIVER=redis
SESSION_LIFETIME=1440
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.edusfera.by
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=<пароль_redis>

MAIL_MAILER=smtp
MAIL_HOST=smtp.mail.ru
MAIL_PORT=465
MAIL_USERNAME=<email>
MAIL_PASSWORD=<пароль>
MAIL_FROM_ADDRESS="support@edusfera.by"

PAYMENT_GATEWAY=<реальный_провайдер>
PAYMENT_WEBHOOK_SECRET=<секрет>
PAYMENT_WEBHOOK_REQUIRE_SIGNATURE=true
PAYMENT_WEBHOOK_REQUIRE_IP_ALLOWLIST=true

CLASSROOM_JWT_SECRET=<64_символа>
MEDIA_SERVER_URL=wss://media.edusfera.by
MEDIA_SERVER_INTERNAL_URL=http://localhost:8088
CLASSROOM_TOKEN_ALG=RS256

PASSPORT_PRIVATE_KEY_PATH=storage/oauth-private.key
PASSPORT_PUBLIC_KEY_PATH=storage/oauth-public.key

METRICS_PROTECTION=basic_auth
METRICS_STORAGE=redis

EVENTS_BUS_DRIVER=redis_streams
EVENTS_TENANT=edusfera
```

### 2.2 RSA ключи

```bash
# OAuth2 ключи
openssl genrsa -out storage/oauth-private.key 4096
openssl rsa -out storage/oauth-public.key -in storage/oauth-private.key -pubout

# Classroom ключи
openssl genrsa -out storage/classroom-private.key 4096
openssl rsa -out storage/classroom-public.key -in storage/classroom-private.key -pubout

# APP_KEY
php artisan key:generate
```

### 2.3 SSL/TLS

```bash
# Certbot
docker run --rm -p 80:80 -v "$(pwd)/certbot/conf:/etc/letsencrypt" \
  certbot/certbot certonly --webroot -w /var/www/html \
  -d edusfera.by -d www.edusfera.by \
  --email support@edusfera.by --agree-tos --no-eff-email
```

---

## Фаза 3: Безопасность (неделя 2)

### 3.1 Что проверить

| Проверка | Статус | Действие |
|----------|--------|----------|
| `.env.production` в `.gitignore` | ✅ | Уже есть |
| `storage/*.key` в `.gitignore` | ✅ | Уже есть |
| `APP_DEBUG=false` | ⚠️ | Задать в .env.production |
| Webhook signature verification | ⚠️ | Включить `PAYMENT_WEBHOOK_REQUIRE_SIGNATURE=true` |
| IP allowlist для webhooks | ⚠️ | Заполнить `PAYMENT_WEBHOOK_ALLOWED_IPS` |
| Rate limiting на API | ✅ | Уже есть (ThrottleApiTest) |
| CSRF protection | ✅ | Laravel по умолчанию |
| XSS protection | ✅ | Blade escaping |
| SQL injection | ✅ | Eloquent ORM |

### 3.2 Firewall

```bash
# UFW / iptables
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw allow 3478/udp  # STUN/TURN
ufw enable
```

---

## Фаза 4: CI/CD (неделя 2-3)

### 4.1 GitHub Actions — деплой

Обновить `.github/workflows/ci-cd.yml`:

```yaml
deploy-production:
  name: Deploy to Production
  runs-on: ubuntu-latest
  needs: [test, lint]
  if: github.ref == 'refs/heads/main'
  environment: production

  steps:
    - name: Checkout
      uses: actions/checkout@v4

    - name: Deploy via SSH
      uses: appleboy/ssh-action@v1
      with:
        host: ${{ secrets.PRODUCTION_HOST }}
        username: ${{ secrets.PRODUCTION_USER }}
        key: ${{ secrets.PRODUCTION_SSH_KEY }}
        script: |
          cd /var/www/edusfera
          git pull origin main
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          php artisan queue:restart
          sudo systemctl reload nginx
```

### 4.2 Secrets для GitHub

Добавить в Settings → Secrets:
- `PRODUCTION_HOST` — IP сервера
- `PRODUCTION_USER` — SSH пользователь
- `PRODUCTION_SSH_KEY` — приватный SSH ключ
- `STAGING_HOST` / `STAGING_USER` / `STAGING_SSH_KEY` — для staging

---

## Фаза 5: Мониторинг (неделя 3)

### 5.1 Health Check

```bash
# Добавить cron job
* * * * * cd /var/www/edusfera && php artisan health:check --json > /dev/null 2>&1
```

### 5.2 Метрики

- Prometheus endpoint `/metrics` (уже реализован)
- Grafana дашборд
- Alerting: PagerDuty / Telegram bot

### 5.3 Логи

- `LOG_CHANNEL=stack` → daily файлы
- Laravel Telescope (опционально)
- `php artisan pail` для реалтайм логов

### 5.4 Бэкапы

```bash
# Cron: ежедневный бэкап
0 2 * * * /var/www/edusfera/scripts/backup/backup-db.sh daily

# Хранить 30 дней
# Off-site: S3 / Hetzner Snapshots
```

---

## Фаза 6: Classroom (неделя 3-4)

### 6.1 edusfera-media

Проблема: Go-образы не скачиваются из Docker Hub.

**Решения:**
1. Использовать зеркало (mirror.gcr.io)
2. Собрать локально и запушить в приватный registry
3. Использовать pre-built образ

### 6.2 TURN сервер

```bash
#coturn уже есть в docker-compose
# Настроить:
- DETECT_EXTERNAL_IP=yes
- DETECT_RELAY_IP=yes
- realm=edusfera.by
- user=<username>:<password>
```

---

## Чеклист перед деплоем

### Инфраструктура
- [ ] Сервер арендован и настроен
- [ ] Docker установлен
- [ ] PostgreSQL доступен
- [ ] Redis доступен
- [ ] Домен edusfera.by привязан
- [ ] DNS записи A/AAAA настроены

### Конфигурация
- [ ] `.env.production` заполнен
- [ ] RSA ключи сгенерированы
- [ ] SSL сертификат установлен
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` сгенерирован

### Безопасность
- [ ] Firewall настроен
- [ ] SSH ключи вместо паролей
- [ ] Webhook signatures включены
- [ ] IP allowlist для webhooks
- [ ] Rate limiting активен

### CI/CD
- [ ] GitHub Secrets добавлены
- [ ] Workflow деплоя настроен
- [ ] Auto-deploy с main ветки работает

### Тестирование
- [ ] Все тесты проходят
- [ ] Smoke test на проде (главная страница, API endpoints)
- [ ] Classroom работает
- [ ] Платежи тестовые проходят

### Мониторинг
- [ ] Health check работает
- [ ] Логи доступны
- [ ] Метрики экспортируются
- [ ] Бэкапы настроены

---

## Timeline

| Фаза | Срок | Стоимость |
|------|------|-----------|
| Инфраструктура | Неделя 1 | ~€15-50/мес |
| Конфигурация | Неделя 1-2 | — |
| Безопасность | Неделя 2 | — |
| CI/CD | Неделя 2-3 | — |
| Мониторинг | Неделя 3 | — |
| Classroom | Неделя 3-4 | — |
| **Итого** | **4 недели** | **€15-50/мес** |

---

## Альтернативы

### Вариант A: VPS (рекомендовано)
- Hetzner / DigitalOcean
- Полный контроль
- Дешево
- Средняя сложность

### Вариант B: PaaS
- Laravel Cloud / Railway / Render
- Проще в деплое
- Дороже (~$50-200/мес)
- Меньше контроля

### Вариант C: Kubernetes
- Scaling
- Сложно в настройке
- Дорого
- Только если нужен horizontal scaling

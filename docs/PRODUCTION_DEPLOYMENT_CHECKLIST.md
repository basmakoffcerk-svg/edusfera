# Чеклист продакшен-деплоя edusfera.by

Пошаговый чеклист для запуска на продакшене. Поддерживает Docker Compose и bare-metal варианты.

---

## Фаза 0: Подготовка (локально)

- [ ] Все тесты проходят: `composer test`
- [ ] Архитектурные тесты проходят: `php artisan test --testsuite=Architecture`
- [ ] Код запушен в `main` ветку
- [ ] `.env.production` создан из `.env.production.example` и заполнен (локально не коммитить!)

---

## Фаза 1: Сервер

### Выбор хостинга

| Параметр | Рекомендация |
|----------|-------------|
| Провайдер | Hetzner Cloud (EU, ~€15/мес) |
| Конфигурация | CX32: 4 vCPU / 8 GB RAM / 80 GB NVMe |
| ОС | Ubuntu 24.04 LTS |
| Локация | Нюрнберг (EU) — близко к Беларуси |

### Инициализация сервера

- [ ] VPS создан в Hetzner Cloud
- [ ] SSH ключ для доступа настроен
- [ ] DNS A-записи настроены:
  - `edusfera.by` → IP сервера
  - `www.edusfera.by` → IP сервера
  - `media.edusfera.by` → IP сервера (classroom)
  - `turn.edusfera.by` → IP сервера (TURN)
- [ ] DNS propagation проверен: `dig edusfera.by`

### Установка ПО на сервер

```bash
# Подключиться к серверу
ssh root@<SERVER_IP>

# Запустить скрипт настройки
bash /path/to/edusfera.by/scripts/deploy/server-setup.sh
```

- [ ] `server-setup.sh` выполнен без ошибок
- [ ] PHP 8.3 установлен: `php -v`
- [ ] Nginx установлен: `nginx -v`
- [ ] Node.js 22 установлен: `node -v`
- [ ] Composer установлен: `composer -V`
- [ ] PostgreSQL клиент установлен
- [ ] UFW firewall установлен
- [ ] Директория `/var/www/edusfera` создана
- [ ] Пользователь `deploy` создан и добавлен в `www-data`

---

## Фаза 2: Клонирование и конфигурация

### Клонирование репозитория

```bash
cd /var/www/edusfera
git clone git@github.com:<org>/edusfera.by.git .
```

- [ ] Репозиторий клонирован

### Настройка .env.production

```bash
cp .env.production.example .env.production
nano .env.production
```

Заполнить все пустые значения:

- [ ] `APP_KEY` — сгенерировать: `php artisan key:generate`
- [ ] `DB_PASSWORD` — сложный пароль для PostgreSQL
- [ ] `REDIS_PASSWORD` — пароль Redis (если требуется)
- [ ] `MAIL_USERNAME` / `MAIL_PASSWORD` — SMTP учётные данные
- [ ] `PAYMENT_WEBHOOK_SECRET` — секрет от платёжного провайдера
- [ ] `PAYMENT_WEBHOOK_ALLOWED_IPS` — IP платёжного провайдера
- [ ] `SITE_ADMIN_EMAIL` / `SITE_ADMIN_PASSWORD` — email и пароль админа (≥16 символов)
- [ ] `CLASSROOM_JWT_SECRET` — сгенерировать: `openssl rand -hex 32`
- [ ] `TURN_SERVER_USERNAME` / `TURN_SERVER_CREDENTIAL` — TURN учётные данные
- [ ] `METRICS_BASIC_USER` / `METRICS_BASIC_PASSWORD` — для /metrics endpoint
- [ ] `DB_HOST` — `db` (Docker) или `127.0.0.1` (bare-metal)
- [ ] `REDIS_HOST` — `redis` (Docker) или `127.0.0.1` (bare-metal)

### Генерация RSA ключей

```bash
# OAuth2 ключи
openssl genrsa -out storage/oauth-private.key 4096
openssl rsa -out storage/oauth-public.key -in storage/oauth-private.key -pubout

# Classroom ключи
openssl genrsa -out storage/classroom-private.key 4096
openssl rsa -out storage/classroom-public.key -in storage/classroom-private.key -pubout

# Права доступа
chmod 600 storage/*.key
chown www-data:www-data storage/*.key
```

- [ ] OAuth2 ключи сгенерированы
- [ ] Classroom ключи сгенерированы
- [ ] Права доступа на ключи установлены

---

## Фаза 3: SSL/TLS

### Получение сертификата

```bash
# Bare-metal
certbot --nginx -d edusfera.by -d www.edusfera.by \
  --email support@edusfera.by --agree-tos --no-eff-email

# Docker (сначала запустить nginx без SSL)
# Получить сертификат через webroot
certbot certonly --webroot -w /var/www/certbot \
  -d edusfera.by -d www.edusfera.by \
  --email support@edusfera.by --agree-tos --no-eff-email
```

- [ ] SSL сертификат получен
- [ ] HTTPS работает: `curl -I https://edusfera.by`
- [ ] HTTP → HTTPS редирект работает
- [ ] Auto-renewal настроен: `certbot renew --dry-run`

---

## Фаза 4: Запуск

### Вариант A: Docker Compose

```bash
cd /var/www/edusfera

# Собрать образы
docker compose -f docker-compose.prod.yml build

# Запустить все сервисы
docker compose -f docker-compose.prod.yml up -d

# Проверить статус
docker compose -f docker-compose.prod.yml ps
```

- [ ] Образ `edusfera-app` собран
- [ ] Все 7 контейнеров запущены (app, nginx, queue, scheduler, db, redis, media)
- [ ] Контейнеры healthy: `docker compose -f docker-compose.prod.yml ps`
- [ ] Логи без ошибок: `docker compose -f docker-compose.prod.yml logs --tail=50`

### Вариант B: Bare-metal

```bash
cd /var/www/edusfera

# Установить зависимости
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Laravel оптимизации
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Миграции
php artisan migrate --force

# Запуск сервисов
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart php8.3-queue  # если настроен supervisor/systemd
```

- [ ] PHP-FPM запущен
- [ ] Nginx запущен
- [ ] Очередь работает

---

## Фаза 5: Верификация

### Smoke-тесты

```bash
# Health check
curl -s https://edusfera.by/health
# Ожидаемый ответ: ok

# Главная страница
curl -s -o /dev/null -w "%{http_code}" https://edusfera.by
# Ожидаемый ответ: 200

# API эндпоинты
curl -s -o /dev/null -w "%{http_code}" https://edusfera.by/api/v1/me
# Ожидаемый ответ: 401 (не авторизован)

# Метрики
curl -s -o /dev/null -w "%{http_code}" https://edusfera.by/metrics
# Ожидаемый ответ: 401 (требуется basic auth)
```

- [ ] Health check возвращает `ok`
- [ ] Главная страница загружается (HTTP 200)
- [ ] API `/me` отвечает 401 (требуется авторизация)
- [ ] Метрики защищены basic auth
- [ ] SSL сертификат валиден (зелёный замок в браузере)

### Функциональные тесты

- [ ] Регистрация пользователя работает
- [ ] Вход в аккаунт работает
- [ ] Каталог репетиторов загружается
- [ ] Профиль репетитора открывается
- [ ] Бронирование урока работает (или payment gateway настроен)
- [ ] Classroom страница открывается (или media сервер запущен)

---

## Фаза 6: Firewall

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw allow 3478/udp  # STUN/TURN
ufw enable
```

- [ ] UFW включён
- [ ] Только порты 22, 80, 443, 3478 открыты
- [ ] SSH по ключу (пароли отключены)

---

## Фаза 7: CI/CD (GitHub Actions)

### Настройка секретов

В GitHub → Settings → Secrets and variables → Actions добавить:

- [ ] `PRODUCTION_HOST` — IP сервера
- [ ] `PRODUCTION_USER` — `deploy`
- [ ] `PRODUCTION_SSH_KEY` — приватный SSH ключ
- [ ] `STAGING_HOST` / `STAGING_USER` / `STAGING_SSH_KEY` — для staging (если есть)

### Проверка автодеплоя

- [ ] Push в `develop` → deploy на staging
- [ ] Push/merge в `main` → deploy на production
- [ ] PR → только тесты + линтер (без деплоя)

---

## Фаза 8: Мониторинг и бэкапы

### Мониторинг

- [ ] Health check cron: `* * * * * curl -sf http://localhost/health || echo "FAIL" >> /var/log/edusfera-health.log`
- [ ] Логи доступны: `docker compose logs -f` или `tail -f /var/log/nginx/error.log`
- [ ] Метрики экспортируются: `curl -u user:pass https://edusfera.by/metrics`

### Бэкапы

```bash
# Настроить cron (ежедневно в 2:00)
echo "0 2 * * * /var/www/edusfera/scripts/backup/backup-db.sh daily" | crontab -
```

- [ ] Cron для бэкапов настроен
- [ ] Тестовый восстановление выполнено: `scripts/backup/restore-db.sh`
- [ ] Off-site хранилище настроено (S3 / Hetzner Snapshots)

---

## Фаза 9: Classroom (опционально)

- [ ] edusfera.media собран и запущен: `curl http://localhost:8088/health`
- [ ] Coturn настроен: `turnserver.conf` с правильными credentiasl
- [ ] TURN сервер доступен: `turncli list` или `stunclient`
- [ ] Медиа сервер проксируется через Nginx (WSS)

---

## Известные проблемы

1. **edusfera-media Docker build** — `go.mod` может требовать Go > 1.24. Dockerfile использует `GOTOOLCHAIN=auto` для автоскачивания.
2. **Payment gateway** — в проде `PAYMENT_GATEWAY=disabled`. Нужна реальная интеграция.
3. **Redis auth** — если Redis без пароля, `REDIS_PASSWORD` оставить пустым.

---

## Откат

Если что-то пошло не так:

```bash
# Через скрипт
bash scripts/deploy/rollback.sh

# Или вручную
cd /var/www/edusfera
git log --oneline -5          # найти нужный коммит
git reset --hard <commit>     # откатить
composer install --no-dev --optimize-autoloader
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan migrate --force

# Docker
docker compose -f docker-compose.prod.yml up -d --force-recreate --no-deps app queue scheduler

# Bare-metal
systemctl reload php8.3-fpm && systemctl reload nginx
```

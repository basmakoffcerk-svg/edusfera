# 🚀 Руководство по развертыванию Edusfera на хостинге / VDS

Данное руководство описывает пошаговый процесс загрузки и запуска проекта **Edusfera** на сервере (Beget, Timeweb, Reg.ru, Hetzner, DigitalOcean, VDS/VPS) с операционной системой Linux (Ubuntu/Debian) или на cPanel/ISPmanager.

---

## 🛠️ Требования к серверу (System Requirements)

- **PHP**: `^8.2` или `8.3` (расширения: `pdo`, `pdo_pgsql` или `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `gd`, `zip`, `xml`, `intl`)
- **База данных**: PostgreSQL 14+ (или MySQL 8+)
- **Веб-сервер**: Nginx или Apache (директория DocumentRoot должна указывать на папку `/public`)
- **Node.js**: Node 18+ (для сборки фронтенда при необходимости)
- **Composer**: Composer 2.x
- **Протокол**: SSL / HTTPS (сертификат Let's Encrypt) — обязательно для платежного эквайринга WebPay и защиты авторизации!

---

## 📋 Пошаговый алгоритм загрузки на хостинг

### Шаг 1. Загрузка файлов на сервер
1. Скопируйте все файлы проекта на хостинг в целевую папку сайта (например, `/var/www/edusfera.by` или `~/edusfera.by/public_html`).
2. Убедитесь, что **DocumentRoot (корень сайта в настройках хостинга)** указывает строго на подпапку **`/public`**.

### Шаг 2. Настройка переменных окружения (`.env`)
1. Создайте файл `.env` на основе `.env.example` на сервере:
   ```bash
   cp .env.example .env
   ```
2. Откройте `.env` и задайте боевые настройки:
   ```ini
   APP_NAME="Edusfera"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://edusfera.by

   # Подключение к базе данных
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=edusfera_prod
   DB_USERNAME=edusfera_user
   DB_PASSWORD=ВашНадежныйПарольБД

   # Сессии и шифрование
   SESSION_DRIVER=database
   SESSION_ENCRYPT=true
   SESSION_SECURE_COOKIE=true

   # Настройки платежного шлюза WebPay
   PAYMENT_GATEWAY=webpay
   WEBPAY_STORE_ID=123456789
   WEBPAY_SECRET_KEY=ВашСекретныйКлючWebPay
   WEBPAY_TEST_MODE=false
   PAYMENT_WEBHOOK_REQUIRE_SIGNATURE=true

   # Технический администратор сайта
   SITE_ADMIN_SYNC_ENABLED=true
   SITE_ADMIN_EMAIL=admin@edusfera.by
   SITE_ADMIN_PASSWORD=ВашНадежныйПарольАдмина
   ```
3. Сгенерируйте секретный ключ приложения:
   ```bash
   php artisan key:generate
   ```

### Шаг 3. Установка зависимостей и сборка ассетов
1. Установите зависимости PHP в оптимизированном режиме:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
2. Соберите фронтенд-ассеты (уже собраны в папке `public/build`, но при надобности):
   ```bash
   npm run build
   ```

### Шаг 4. Настройка прав доступа к директориям
Выполните команды для установки прав на запись для веб-сервера (`www-data`):
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Шаг 5. Применение миграций и символическая ссылка
1. Выполните миграцию БД:
   ```bash
   php artisan migrate --force
   ```
2. Создайте символическую ссылку для загружаемых файлов:
   ```bash
   php artisan storage:link
   ```

### Шаг 6. Кэширование для максимальной производительности
Выполните команды кэширования конфигурации, маршрутов и видов:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize
```

---

## ⚙️ Автоматизация: Cron и Очереди (Queue / Cron Job)

### 1. Настройка Планировщика задач (Cron Job)
Добавьте следующую строчку в `crontab` хостинга (`crontab -e`):
```bash
* * * * * cd /var/www/edusfera.by && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Настройка Воркера Очередей (Supervisor)
Для обработки фоновых задач (отправка email, обработка вебхуков) создайте конфиг `/etc/supervisor/conf.d/edusfera-worker.conf`:
```ini
[program:edusfera-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/edusfera.by/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/edusfera.by/storage/logs/worker.log
stopwaitsecs=3600
```
Запустите воркер:
```bash
supervisorctl reread
supervisorctl update
supervisorctl start edusfera-worker:*
```

---

## 🚀 Автоматическое деплой-обновление (`deploy.sh`)

При последующих обновлениях проекта достаточно запустить скрипт:
```bash
./deploy.sh
```

---

## 🔒 Проверочный чек-лист (Production Ready Checklist)

- [x] `APP_ENV=production` и `APP_DEBUG=false` в файле `.env`
- [x] `APP_KEY` сгенерирован
- [x] Установлен SSL-сертификат (HTTPS)
- [x] Корень сайта указывает на папку `/public`
- [x] Скомпилированы ассеты (`public/build`)
- [x] Настроены права `775` на `storage` и `bootstrap/cache`
- [x] Выполнены миграции БД `php artisan migrate --force`
- [x] Включен Cron `php artisan schedule:run`
- [x] Запущен Supervisor queue worker

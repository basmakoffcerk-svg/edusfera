# 🚀 Комплект деплоя Edusfera (Deploy Kit)

В этой папке собраны все конфигурации и скрипты для быстрого развертывания проекта **Edusfera** на боевом сервере.

---

## 📂 Содержимое папки

- **`crontab.txt`** — готовые команды планировщика Cron для добавления в `crontab -e` или панель управления хостингом (cPanel, ISPmanager, Beget).
- **`supervisor/edusfera-worker.conf`** — конфигурация демона Supervisor для фоновой обработки очередей (отправка писем, вебхуки, биллинг).
- **`systemd/edusfera-worker.service`** — альтернативный юнит Systemd для запуска воркеров очереди на серверах Ubuntu/Debian.
- **`server-setup.sh`** — скрипт автоматической первичной настройки (права на папки `storage`, миграции, `storage:link`, кэширование).

---

## ⚡ Быстрый запуск на сервере

### 1. Распаковка архива
Распакуйте архив в корень веб-сервера (например `/var/www/edusfera.by`):
```bash
# Для .tar.gz:
tar -xzf edusfera-deploy-full.tar.gz -C /var/www/edusfera.by

# Для .zip:
unzip -q edusfera-deploy-full.zip -d /var/www/edusfera.by
```

### 2. Настройка окружения
```bash
cd /var/www/edusfera.by
cp .env.production.example .env
nano .env   # Укажите доступ к БД (PostgreSQL/MySQL), домен и API Alfa-Bank
```

### 3. Автоматическая настройка
Запустите скрипт:
```bash
./deploy/server-setup.sh
```

### 4. Включение Cron
Откройте планировщик:
```bash
crontab -e
```
Вставьте строчку:
```cron
* * * * * cd /var/www/edusfera.by && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Запуск воркера очереди (на выбор)

**Вариант А: Через Supervisor**
```bash
sudo cp deploy/supervisor/edusfera-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start edusfera-worker:*
```

**Вариант Б: Через Systemd**
```bash
sudo cp deploy/systemd/edusfera-worker.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now edusfera-worker
```

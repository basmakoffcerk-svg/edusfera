# Подключение микросервиса к ядру edusfera

Это пошаговая инструкция для разработчика нового микросервиса.

## Архитектура подключения

```
┌──────────────────────┐          ┌───────────────────────────┐
│   Ваш микросервис    │          │     Ядро (Laravel)        │
│                      │          │                           │
│  1. POST /oauth/token├─────────►│  Passport client_creds    │
│     получить токен   │          │  → access_token (RS256)   │
│                      │◄─────────┤                           │
│                      │          │                           │
│  2. GET /api/internal├─────────►│  Internal API (v1)        │
│     /v1/lessons/{id} │          │  scope: lessons:read      │
│     Bearer: <token>  │          │                           │
│                      │          │                           │
│  3. XREADGROUP       │          │                           │
│     Redis Streams    │◄─ Redis ─┤  Outbox Publisher (cron)  │
│     topic:lesson     │          │  → XADD topic:lesson      │
│                      │          │                           │
│  4. POST /webhooks/  │─────────►│  Webhook receiver         │
│     HMAC-подпись     │          │  VerifiesWebhookSignature │
└──────────────────────┘          └───────────────────────────┘
```

---

## 1. Регистрация Passport Client

```bash
# На сервере ядра (внутри Docker-контейнера app):
php artisan passport:client --client --name="my-service"
```

Будут выданы `client_id` и `client_secret`. Сохраните их.

---

## 2. Получение Access Token

```bash
curl -X POST http://app:8000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "<client_id>",
    "client_secret": "<client_secret>",
    "scope": "lessons:read internal:metrics:read"
  }'
```

Ответ:
```json
{
  "token_type": "Bearer",
  "expires_in": 1800,
  "access_token": "eyJ..."
}
```

> **Важно:** Токен живёт 30 минут (TTL настроен в ядре). Ваш сервис должен
> автоматически обновлять токен при получении 401.

---

## 3. Вызов Internal API

### Доступные эндпоинты

| Метод | Путь | Scope | Описание |
|-------|------|-------|----------|
| GET | `/api/internal/v1/health` | — | Liveness (без auth) |
| GET | `/api/internal/v1/health/ready` | `internal:metrics:read` | Readiness (DB + Redis + Outbox) |
| GET | `/api/internal/v1/lessons/{id}` | `lessons:read` | Данные урока |
| GET | `/api/internal/v1/lessons?user_id=` | `lessons:read` | Список уроков пользователя |
| GET | `/api/internal/v1/outbox/pending` | `internal:outbox:read` | Мониторинг pending событий |

### Пример запроса

```bash
curl http://app:8000/api/internal/v1/lessons/42 \
  -H "Authorization: Bearer <access_token>" \
  -H "Accept: application/json" \
  -H "X-Request-Id: $(uuidgen)"
```

Ответ:
```json
{
  "data": {
    "id": 42,
    "tutor_id": 5,
    "student_id": 12,
    "start_time": "2026-06-15T10:00:00+00:00",
    "end_time": "2026-06-15T11:00:00+00:00",
    "duration_minutes": 60,
    "price": "25.00",
    "status": "confirmed",
    "payment_status": "paid",
    "package_code": "single",
    "package_lessons": 1,
    "package_parent_lesson_id": null
  }
}
```

### Формат ошибок

```json
{
  "error": {
    "code": "not_found",
    "message": "Lesson not found.",
    "request_id": "0195f8a0-..."
  }
}
```

---

## 4. Подписка на интеграционные события (Redis Streams)

### Доступные события

| Тип | Версия | Aggregate | Когда |
|-----|--------|-----------|-------|
| `lesson.booked` | v1 | `lesson` | Бронирование урока |
| `lesson.completed` | v1 | `lesson` | Завершение урока |
| `lesson.cancelled` | v1 | `lesson` | Отмена урока |
| `payment.completed` | v1 | `payment` | Успешная оплата |

### Redis Stream name

Формат: `topic:{aggregate_type}` → например `topic:lesson`, `topic:payment`.

### Consumer Group

```bash
# Создать consumer group (один раз):
redis-cli XGROUP CREATE topic:lesson my-service-group 0 MKSTREAM

# Читать события:
redis-cli XREADGROUP GROUP my-service-group worker-1 COUNT 10 BLOCK 5000 STREAMS topic:lesson >
```

### Формат конверта события

```json
{
  "id": "0195f8a0-1234-7000-9000-abcdef123456",
  "type": "lesson.booked",
  "version": 1,
  "occurred_at": "2026-06-01T12:00:00+00:00",
  "tenant": "edusfera",
  "trace_id": "0195f8a0-abcd-7000-...",
  "actor": {
    "type": "user",
    "id": 12,
    "role": "student"
  },
  "aggregate": {
    "type": "lesson",
    "id": 42
  },
  "payload": {
    "lesson_id": 42,
    "tutor_id": 5,
    "student_id": 12,
    "start_time": "2026-06-15T10:00:00+00:00",
    "end_time": "2026-06-15T11:00:00+00:00",
    "duration_minutes": 60,
    "price": "25.00",
    "package_code": "single"
  }
}
```

### JSON-схемы

Полные JSON Schema спецификации payload'ов находятся в:
- `docs/events/lesson.booked.v1.json`
- `docs/events/lesson.completed.v1.json`
- `docs/events/lesson.cancelled.v1.json`
- `docs/events/payment.completed.v1.json`

> **Контракт обратной совместимости:** В рамках мажорной версии (v1) поля только
> добавляются, никогда не удаляются и не меняют тип. `additionalProperties: true`
> — ваш consumer должен игнорировать неизвестные поля.

---

## 5. Отправка webhook в ядро

Если ваш микросервис отправляет данные в ядро (например, результат AI-обработки),
используйте webhook-механизм с HMAC-подписью.

```bash
TIMESTAMP=$(date +%s)
NONCE=$(uuidgen)
BODY='{"result": "..."}'
SIGNATURE=$(echo -n "${TIMESTAMP}.${NONCE}.${BODY}" | openssl dgst -sha256 -hmac "${WEBHOOK_SECRET}" -binary | base64)

curl -X POST http://app:8000/webhooks/my-service \
  -H "Content-Type: application/json" \
  -H "X-Signature: ${SIGNATURE}" \
  -H "X-Timestamp: ${TIMESTAMP}" \
  -H "X-Nonce: ${NONCE}" \
  -d "${BODY}"
```

---

## 6. Верификация JWT через JWKS

Если ваш сервис получает JWT-токены напрямую (например, от фронтенда):

```
JWKS URL: http://app:8000/.well-known/jwks.json
Algorithm: RS256
```

Используйте стандартную JWKS-библиотеку вашего языка для верификации.

---

## 7. Доступные OAuth Scopes

| Scope | Описание |
|-------|----------|
| `lessons:read` | Чтение данных уроков |
| `lessons:write` | Создание и изменение уроков |
| `homework:read` | Чтение домашних заданий |
| `homework:write` | Создание и изменение ДЗ |
| `ai:invoke` | Вызов AI-ассистента |
| `ai:write` | Запись результатов AI |
| `classroom:token:issue` | Выпуск токена для virtual classroom |
| `internal:outbox:read` | Чтение очереди outbox |
| `internal:metrics:read` | Чтение метрик |

---

## 8. Docker Compose

Используйте шаблон `docker-compose.override.yml` из этой директории для быстрого
подключения к Docker-сети edusfera. Ваш сервис получит доступ к:
- `app:8000` — HTTP API ядра
- `redis:6379` — Redis (Streams + кэш)

---

## Checklist запуска нового микросервиса

- [ ] Создать Passport client: `php artisan passport:client --client`
- [ ] Настроить `CORE_CLIENT_ID` и `CORE_CLIENT_SECRET` в env
- [ ] Получить токен и проверить `GET /api/internal/v1/health`
- [ ] Подключиться к Redis Streams (создать consumer group)
- [ ] Реализовать обработку конверта событий
- [ ] Настроить health check в Dockerfile
- [ ] Добавить сервис в docker-compose ядра

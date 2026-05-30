# Ротация RSA-ключей Passport (RS256)

> Документ описывает процедуру ротации ключевой пары RSA, используемой для подписи
> OAuth2 access-токенов через Passport (алгоритм RS256).
> Процедура обеспечивает **zero-downtime rotation** — микросервисы продолжают
> валидировать токены, подписанные старым ключом, пока все выданные токены не истекут.

---

## Архитектура ключей

| Переменная окружения              | Назначение                                                                 |
|-----------------------------------|----------------------------------------------------------------------------|
| `PASSPORT_PRIVATE_KEY_PATH`       | Путь к текущему приватному ключу (подпись новых токенов)                   |
| `PASSPORT_PUBLIC_KEY_PATH`        | Путь к текущему публичному ключу (публикуется в JWKS как `kid=current`)    |
| `PASSPORT_PREVIOUS_PUBLIC_KEY_PATH` | Путь к предыдущему публичному ключу (публикуется в JWKS как `kid=previous`) |

JWKS endpoint: `GET /api/v1/.well-known/jwks.json`

---

## Когда нужна ротация

- Плановая ротация (рекомендуется раз в 6–12 месяцев).
- Подозрение на компрометацию приватного ключа.
- Смена алгоритма или длины ключа.

---

## Процедура ротации (zero-downtime)

### Шаг 1. Сгенерировать новую ключевую пару

```bash
# Генерация нового приватного ключа (4096 бит)
openssl genrsa -out storage/oauth-private.key.new 4096

# Извлечение публичного ключа
openssl rsa -in storage/oauth-private.key.new -pubout -out storage/oauth-public.key.new

# Установить права доступа
chmod 600 storage/oauth-private.key.new
chmod 644 storage/oauth-public.key.new
```

### Шаг 2. Опубликовать оба публичных ключа в JWKS

Обновите `.env` (или секрет-стор), **не меняя** текущий приватный ключ:

```dotenv
# Текущий ключ остаётся для подписи
PASSPORT_PRIVATE_KEY_PATH=storage/oauth-private.key
PASSPORT_PUBLIC_KEY_PATH=storage/oauth-public.key

# Новый публичный ключ публикуется как "previous" (временно)
PASSPORT_PREVIOUS_PUBLIC_KEY_PATH=storage/oauth-public.key.new
```

> На этом шаге JWKS будет содержать оба ключа. Микросервисы, кэширующие JWKS,
> должны обновить кэш (обычно TTL 5–15 минут).

Перезапустите приложение / сделайте rolling deploy.

### Шаг 3. Переключить подпись на новый ключ

После того как все микросервисы обновили JWKS-кэш (подождите TTL + запас):

```dotenv
# Новый ключ становится текущим
PASSPORT_PRIVATE_KEY_PATH=storage/oauth-private.key.new
PASSPORT_PUBLIC_KEY_PATH=storage/oauth-public.key.new

# Старый публичный ключ остаётся в JWKS для валидации уже выданных токенов
PASSPORT_PREVIOUS_PUBLIC_KEY_PATH=storage/oauth-public.key
```

Перезапустите приложение.

### Шаг 4. Дождаться истечения старых токенов

TTL access-токенов — **30 минут** (см. `AppServiceProvider::boot()`).
После истечения всех токенов, подписанных старым ключом, можно убрать `PASSPORT_PREVIOUS_PUBLIC_KEY_PATH`.

### Шаг 5. Убрать предыдущий ключ из JWKS

```dotenv
PASSPORT_PRIVATE_KEY_PATH=storage/oauth-private.key.new
PASSPORT_PUBLIC_KEY_PATH=storage/oauth-public.key.new
PASSPORT_PREVIOUS_PUBLIC_KEY_PATH=   # пусто
```

Перезапустите приложение. Ротация завершена.

### Шаг 6. Переименовать файлы (опционально)

```bash
mv storage/oauth-private.key.new storage/oauth-private.key
mv storage/oauth-public.key.new  storage/oauth-public.key
```

Обновите `.env` соответственно.

---

## Хранение ключей

- **Никогда** не коммитьте приватный ключ в git. Файл `storage/oauth-private.key` добавлен в `.gitignore`.
- В production используйте секрет-стор (AWS Secrets Manager, HashiCorp Vault, Kubernetes Secrets).
- Альтернатива файлам: задайте содержимое ключа напрямую через `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY` (PEM-строка с `\n` вместо переносов строк).

---

## Проверка JWKS после ротации

```bash
# Проверить, что оба ключа опубликованы
curl -s https://your-domain.com/api/v1/.well-known/jwks.json | jq '.keys | length'
# Ожидаемый результат: 2 (во время ротации) или 1 (в штатном режиме)

# Проверить kid ключей
curl -s https://your-domain.com/api/v1/.well-known/jwks.json | jq '.keys[].kid'
```

---

## Связанные требования

- **Требование 3.2** — токены подписываются RS256 с приватным ключом из ENV.
- **Требование 3.4** — JWKS endpoint публикует текущий публичный ключ.
- **Требование 3.7** — поддержка ротации без даунтайма через публикацию двух ключей.

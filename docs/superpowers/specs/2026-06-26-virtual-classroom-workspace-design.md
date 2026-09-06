# Спецификация: Микросервис интерактивного рабочего пространства (Workspace/Kanban)

Этот документ описывает детальный архитектурный дизайн, схему данных и протокол обмена событиями реального времени для микросервиса интерактивного рабочего пространства (`edusfera-workspace`) виртуального класса платформы Edusfera.

---

## 1. Архитектурный обзор

Микросервис `edusfera-workspace` разработан на языке Go и функционирует как изолированный сервис реального времени. Он берет на себя всю логику по ведению состояния интерактивных Kanban-досок в классах, WebSocket-синхронизации и прямому сохранению в базу данных PostgreSQL.

```
                  ┌──────────────────────────────────────────────┐
                  │              API Gateway (Nginx)             │
                  └──────────────┬────────────────────────┬──────┘
                                 │                        │
               Websockets /ws/workspace/       REST /api/v1/workspace/
                                 │                        │
                                 ▼                        ▼
                  ┌──────────────────────────────────────────────┐
                  │              edusfera-workspace              │
                  │                   (Go)                       │
                  └──────────────┬────────────────────────┬──────┘
                                 │                        │
                              TCP 6379                 TCP 5432
                                 │                        │
                                 ▼                        ▼
                          ┌─────────────┐          ┌─────────────┐
                          │    Redis    │          │ PostgreSQL  │
                          │(Active Rooms│          │ (Persisted  │
                          │  & Pub/Sub) │          │ Workspaces) │
                          └─────────────┘          └─────────────┘
```

---

## 2. Модель данных и структура хранения

Для хранения состояния интерактивной доски используется персистентная таблица в PostgreSQL и кэширующий слой в Redis для снижения нагрузки.

### 2.1 Схема PostgreSQL: `classroom_workspaces`

Создается новая таблица, связанная с сессиями классов:

```sql
CREATE TABLE classroom_workspaces (
    id BIGSERIAL PRIMARY KEY,
    classroom_session_id BIGINT NOT NULL UNIQUE REFERENCES classroom_sessions(id) ON DELETE CASCADE,
    board_state JSONB NOT NULL DEFAULT '{"columns": []}',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL
);

CREATE INDEX idx_classroom_workspaces_session ON classroom_workspaces(classroom_session_id);
```

### 2.2 Схема Redis

* **Кэш состояния доски:** 
  * Ключ: `workspace:{room_id}`
  * Тип: `String` (сериализованный JSON состояния доски)
  * TTL: 24 часа.
* **Pub/Sub для горизонтального масштабирования:**
  * Канал: `pubsub:workspace:{room_id}`
  * Содержимое: JSON-события мутации доски, транслируемые между инстансами бэкенда Go.

### 2.3 JSON-схема состояния доски (`board_state`)

```json
{
  "columns": [
    {
      "id": "col-1",
      "title": "План урока",
      "cards": [
        {
          "id": "card-1",
          "type": "note",
          "title": "Markdown заметка",
          "content": "### Тема: Производная функции\n1. Определение\n2. Геометрический смысл"
        },
        {
          "id": "card-2",
          "type": "checklist",
          "title": "Задачи на закрепление",
          "items": [
            { "id": "item-1", "text": "Найти производную y = x^2", "completed": true },
            { "id": "item-2", "text": "Найти производную y = sin(x)", "completed": false }
          ]
        }
      ]
    }
  ]
}
```

---

## 3. Протокол событий реального времени (WebSocket)

Клиенты подключаются по адресу: `/ws/workspace/{room_id}`.

### 3.1 Входящие и исходящие WebSocket-события

Все сообщения оборачиваются в единый конверт:
```json
{
  "event": "event_name",
  "payload": { ... }
}
```

#### 1. `workspace.sync` (Исходящее от сервера)
Отправляется клиенту сразу после успешного рукопожатия. Содержит полную копию состояния доски.
* **Payload:** Полный JSON `board_state`.

#### 2. `column.add` (Двустороннее)
Добавление новой колонки.
* **Payload:** `{ "columnId": "col-uuid", "title": "Новая колонка" }`

#### 3. `column.move` (Двустороннее)
Изменение порядка колонок.
* **Payload:** `{ "columnId": "col-uuid", "index": 2 }`

#### 4. `column.delete` (Двустороннее)
Удаление колонки и всех её карточек.
* **Payload:** `{ "columnId": "col-uuid" }`

#### 5. `card.add` (Двустороннее)
Добавление новой карточки в колонку.
* **Payload:** 
  ```json
  {
    "columnId": "col-uuid",
    "card": {
      "id": "card-uuid",
      "type": "note|checklist|code|timer",
      "title": "Название",
      "content": "...",
      "items": []
    }
  }
  ```

#### 6. `card.update` (Двустороннее)
Обновление содержимого карточки (текст, состояние чек-листа и т.д.).
* **Payload:** 
  ```json
  {
    "cardId": "card-uuid",
    "data": {
      "content": "Новое содержимое Markdown...",
      "items": [ ... ]
    }
  }
  ```

#### 7. `card.move` (Двустороннее)
Перемещение карточки внутри одной колонки или между колонками.
* **Payload:** 
  ```json
  {
    "cardId": "card-uuid",
    "fromColumnId": "col-1",
    "toColumnId": "col-2",
    "index": 1
  }
  ```

#### 8. `card.delete` (Двустороннее)
Удаление карточки.
* **Payload:** `{ "cardId": "card-uuid", "columnId": "col-uuid" }`

---

## 4. Интеграция с ядром (Laravel / AI-ассистент)

Поскольку генерация JSON-изменений ИИ-ассистентом происходит в Laravel-монолите, Go-сервис предоставляет защищенный REST-эндпоинт для применения этих изменений.

* **Эндпоинт:** `POST /api/v1/workspace/{room_id}/apply-ai-patch`
* **Авторизация:** `Authorization: Bearer <internal_secret>` (timing-safe сверка с `config/classroom.internal_secret`).
* **Тело запроса:** Массив изменений Kanban-доски в формате JSON Patch или готовая полная структура новой колонки/карточки.
  ```json
  {
    "actions": [
      {
        "action": "column.add",
        "payload": {
          "columnId": "ai-col-1",
          "title": "ИИ: План подготовки к ЦЭ"
        }
      },
      {
        "action": "card.add",
        "payload": {
          "columnId": "ai-col-1",
          "card": {
            "id": "ai-card-1",
            "type": "checklist",
            "title": "Чек-лист от ИИ",
            "items": [
              { "id": "ai-item-1", "text": "Повторить тригонометрические формулы", "completed": false },
              { "id": "ai-item-2", "text": "Решить 10 задач B-части", "completed": false }
            ]
          }
        }
      }
    ]
  }
  ```
* **Действие:** Go-сервис парсит и валидирует входящие команды, применяет их к активной доске в Redis, транслирует соответствующие WebSocket-события всем подключенным клиентам в комнате и возвращает успешный статус.

---

## 5. Структура Go-проекта (`edusfera-workspace`)

Проект наследует стандартную архитектуру микросервисов Edusfera:

* `cmd/server/main.go` — точка входа, инициализация логгера, роутера и HTTP/WS сервера.
* `internal/config/config.go` — загрузка конфигурации из переменных окружения.
* `internal/db/db.go` — соединение с PostgreSQL, миграции структуры таблицы `classroom_workspaces`.
* `internal/redis/redis.go` — клиент Redis для кэширования состояний и Pub/Sub трансляций.
* `internal/workspace/model.go` — структуры Go для представления колонок, карточек, чек-листов и событий.
* `internal/workspace/board.go` — логика мутации состояния доски в памяти (добавление, перемещение карточек, применение ИИ-патчей, сброс состояния в Postgres).
* `internal/websocket/pool.go` — пул активных вебсокет-соединений по комнатам.
* `internal/websocket/handler.go` — апгрейд HTTP до WebSocket, чтение/запись событий, отправка `workspace.sync`.

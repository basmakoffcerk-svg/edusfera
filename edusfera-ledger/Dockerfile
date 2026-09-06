# ─── Сборщик ─────────────────────────────────────────────────────────────
FROM golang:1.22-alpine AS builder

WORKDIR /src

# Установка необходимых системных утилит
RUN apk add --no-cache git ca-certificates tzdata

# Копирование зависимостей и кэширование
COPY go.mod ./
RUN go mod download

# Копирование исходного кода
COPY . .

# Сборка оптимизированного бинарного файла
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-w -s" -o /bin/ledger .

# ─── Рантайм ─────────────────────────────────────────────────────────────
FROM alpine:3.19

# Установка curl для прохождения health check
RUN apk add --no-cache curl ca-certificates tzdata

WORKDIR /app

# Копируем скомпилированный бинарный файл из builder
COPY --from=builder /bin/ledger /app/ledger

EXPOSE 8080

# Запуск приложения
ENTRYPOINT ["/app/ledger"]

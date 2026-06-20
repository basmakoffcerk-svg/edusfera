# Makefile — edusfera.by
# Локальные утилиты для разработки и CI-проверок.

.PHONY: help openapi-check openapi-install openapi-diff pint test arch-test backup backup-restore health

# ─── Переменные ──────────────────────────────────────────────────────────────

OPENAPI_SPEC     := docs/api/openapi.yaml
OPENAPI_BASE_REF ?= main
OASDIFF_BIN      := $(shell which oasdiff 2>/dev/null || echo "oasdiff")

# ─── Справка ─────────────────────────────────────────────────────────────────

help: ## Показать список доступных команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}'

# ─── OpenAPI ─────────────────────────────────────────────────────────────────

openapi-install: ## Установить oasdiff (macOS/Linux)
	@if command -v oasdiff >/dev/null 2>&1; then \
		echo "oasdiff уже установлен: $$(oasdiff version)"; \
	elif command -v brew >/dev/null 2>&1; then \
		echo "Устанавливаем oasdiff через Homebrew..."; \
		brew install tufin/tufin/oasdiff; \
	else \
		echo "Устанавливаем oasdiff через go install..."; \
		go install github.com/tufin/oasdiff@latest; \
	fi

openapi-check: ## Проверить обратную совместимость openapi.yaml с main веткой
	@echo "Проверка обратной совместимости $(OPENAPI_SPEC) с веткой $(OPENAPI_BASE_REF)..."
	@if ! command -v oasdiff >/dev/null 2>&1; then \
		echo "oasdiff не найден. Запустите: make openapi-install"; \
		exit 1; \
	fi
	@# Получаем версию файла из base-ветки во временный файл
	@TMPFILE=$$(mktemp /tmp/openapi-base-XXXXXX.yaml); \
	if git show $(OPENAPI_BASE_REF):$(OPENAPI_SPEC) > "$$TMPFILE" 2>/dev/null; then \
		echo "Сравниваем с $(OPENAPI_BASE_REF):$(OPENAPI_SPEC)"; \
		oasdiff breaking "$$TMPFILE" $(OPENAPI_SPEC) --format text; \
		EXIT_CODE=$$?; \
		rm -f "$$TMPFILE"; \
		if [ $$EXIT_CODE -ne 0 ]; then \
			echo ""; \
			echo "❌ Обнаружены breaking changes!"; \
			echo "В рамках v1 поля только добавляются. Удаление поля = bump до v2 + заголовок Deprecation минимум 2 релиза."; \
			exit 1; \
		else \
			echo "✅ Breaking changes не обнаружены."; \
		fi; \
	else \
		echo "Файл $(OPENAPI_SPEC) не найден в ветке $(OPENAPI_BASE_REF) — пропускаем проверку (первое добавление)."; \
	fi

openapi-diff: ## Показать полный diff openapi.yaml с main веткой (без блокировки)
	@if ! command -v oasdiff >/dev/null 2>&1; then \
		echo "oasdiff не найден. Запустите: make openapi-install"; \
		exit 1; \
	fi
	@TMPFILE=$$(mktemp /tmp/openapi-base-XXXXXX.yaml); \
	if git show $(OPENAPI_BASE_REF):$(OPENAPI_SPEC) > "$$TMPFILE" 2>/dev/null; then \
		oasdiff diff "$$TMPFILE" $(OPENAPI_SPEC) --format text || true; \
		rm -f "$$TMPFILE"; \
	else \
		echo "Файл $(OPENAPI_SPEC) не найден в ветке $(OPENAPI_BASE_REF)."; \
	fi

# ─── PHP ─────────────────────────────────────────────────────────────────────

pint: ## Запустить Laravel Pint (форматирование PHP-кода)
	vendor/bin/pint

test: ## Запустить все тесты (PHPUnit)
	php artisan test

arch-test: ## Запустить архитектурные тесты изоляции слоёв (требование 14)
	php artisan test --testsuite=Architecture

# ─── Health & Backup ──────────────────────────────────────────────────────────

health: ## Проверить здоровье приложения (DB, Redis, queue, disk)
	php artisan health:check

health-json: ## Проверить здоровье приложения (JSON output)
	php artisan health:check --json

backup: ## Создать бэкап PostgreSQL
	@./scripts/backup/backup-db.sh manual

backup-daily: ## Создать ежедневный бэкап PostgreSQL
	@./scripts/backup/backup-db.sh daily

backup-restore: ## Восстановить из бэкапа (usage: make backup-restore FILE=path/to/backup.sql.gz)
	@./scripts/backup/restore-db.sh $(FILE)

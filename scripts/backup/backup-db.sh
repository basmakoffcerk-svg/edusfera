#!/bin/bash
# backup-db.sh — PostgreSQL backup script for Edusfera
# Usage: ./backup-db.sh [daily|weekly|manual]

set -euo pipefail

# Load environment
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

if [ -f "$PROJECT_DIR/.env" ]; then
    set -a
    source "$PROJECT_DIR/.env"
    set +a
fi

# Configuration
BACKUP_DIR="${BACKUP_DIR:-$PROJECT_DIR/storage/backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-edusfera}"
DB_USERNAME="${DB_USERNAME:-postgres}"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Generate filename
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_TYPE="${1:-manual}"
FILENAME="${DB_DATABASE}_${BACKUP_TYPE}_${TIMESTAMP}.sql.gz"
BACKUP_PATH="$BACKUP_DIR/$FILENAME"

echo "Starting backup: $FILENAME"
echo "Database: $DB_DATABASE @ $DB_HOST:$DB_PORT"

# Create backup
pg_dump \
    -h "$DB_HOST" \
    -p "$DB_PORT" \
    -U "$DB_USERNAME" \
    -d "$DB_DATABASE" \
    --no-owner \
    --no-privileges \
    --clean \
    --if-exists \
    | gzip > "$BACKUP_PATH"

# Verify backup
if [ -f "$BACKUP_PATH" ] && [ -s "$BACKUP_PATH" ]; then
    SIZE=$(du -h "$BACKUP_PATH" | cut -f1)
    echo "✓ Backup created: $BACKUP_PATH ($SIZE)"
else
    echo "✗ Backup failed: file is empty or missing"
    exit 1
fi

# Cleanup old backups
echo "Cleaning backups older than $RETENTION_DAYS days..."
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +"$RETENTION_DAYS" -delete 2>/dev/null || true

# List current backups
echo ""
echo "Current backups:"
ls -lh "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -5

echo ""
echo "Backup completed successfully."

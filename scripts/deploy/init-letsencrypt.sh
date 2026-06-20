#!/bin/bash
set -euo pipefail

# ─── edusfera.by — Docker Certbot/SSL Bootstrapping Script ────────────────
# Use this script to initialize Let's Encrypt SSL certificates in Docker mode.
# Run on the production server as root.

DOMAINS=("edusfera.by" "www.edusfera.by")
EMAIL="edusferaby@gmail.com"
TEST_MODE=0 # Set to 1 to test registration with staging authority

if [ "$EUID" -ne 0 ]; then
  echo "❌ Please run as root."
  exit 1
fi

echo "🚀 Bootstrapping SSL certificates for: ${DOMAINS[*]}"

# Create directories
mkdir -p ./data/certbot/conf
mkdir -p ./data/certbot/www

# Link to docker volumes or local paths
CONF_PATH="/var/lib/docker/volumes/edusfera_certbot-conf/_data"
WWW_PATH="/var/lib/docker/volumes/edusfera_certbot-www/_data"

# Check if volumes exist, fallback to relative paths if compose hasn't run
if docker volume inspect edusfera_certbot-conf >/dev/null 2>&1; then
  CONF_PATH=$(docker volume inspect edusfera_certbot-conf --format '{{.Mountpoint}}')
  WWW_PATH=$(docker volume inspect edusfera_certbot-www --format '{{.Mountpoint}}')
else
  echo "⚠️ Docker volumes not found. Running docker compose up first is recommended."
  CONF_PATH="./data/certbot/conf"
  WWW_PATH="./data/certbot/www"
  mkdir -p "$CONF_PATH" "$WWW_PATH"
fi

if [ -e "$CONF_PATH/live/${DOMAINS[0]}" ]; then
  echo "✅ Certificates already exist. Skipping initialization."
  exit 0
fi

echo "🔑 Creating dummy certificate..."
path="/etc/letsencrypt/live/${DOMAINS[0]}"
mkdir -p "$CONF_PATH/live/${DOMAINS[0]}"

# Generate dummy key and cert
docker run --rm \
  -v "$CONF_PATH:/etc/letsencrypt" \
  -v "$WWW_PATH:/var/www/certbot" \
  certbot/certbot certonly --register-unsafely-without-email --agree-tos \
  --standalone -d "${DOMAINS[0]}" --test-cert || true

echo "🐳 Starting Nginx..."
docker compose -f docker-compose.prod.yml up --force-recreate -d nginx

echo "🗑️ Deleting dummy certificate..."
docker run --rm \
  -v "$CONF_PATH:/etc/letsencrypt" \
  -v "$WWW_PATH:/var/www/certbot" \
  alpine rm -rf "/etc/letsencrypt/live/${DOMAINS[0]}" "/etc/letsencrypt/archive/${DOMAINS[0]}" "/etc/letsencrypt/renewal/${DOMAINS[0]}.conf"

echo "🔒 Requesting real Let's Encrypt certificate..."
domain_args=""
for domain in "${DOMAINS[@]}"; do
  domain_args="$domain_args -d $domain"
done

email_arg="--email $EMAIL"
if [ -z "$EMAIL" ]; then
  email_arg="--register-unsafely-without-email"
fi

test_arg=""
if [ "$TEST_MODE" -ne 0 ]; then
  test_arg="--test-cert"
fi

docker run --rm \
  -v "$CONF_PATH:/etc/letsencrypt" \
  -v "$WWW_PATH:/var/www/certbot" \
  certbot/certbot certonly --webroot -w /var/www/certbot \
  $domain_args \
  $email_arg \
  --agree-tos \
  --no-eff-email \
  $test_arg \
  --force-renewal

echo "🔄 Reloading Nginx with new certificate..."
docker compose -f docker-compose.prod.yml exec nginx nginx -s reload

echo "✅ SSL Certificate setup complete!"

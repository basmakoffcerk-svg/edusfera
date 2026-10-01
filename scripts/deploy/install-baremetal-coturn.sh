#!/usr/bin/env bash
set -e

# ==============================================================================
# EDUSFERA.BY — ПОЛНОСТЬЮ АВТОМАТИЧЕСКАЯ УСТАНОВКА COTURN (БЕЗ DOCKER)
# ==============================================================================
# Запуск на сервере Edusfera (Ubuntu / Debian):
#   sudo bash scripts/deploy/install-baremetal-coturn.sh
# ==============================================================================

if [ "$EUID" -ne 0 ]; then
  echo "❌ Пожалуйста, запустите скрипт с правами root: sudo bash $0"
  exit 1
fi

echo "🚀 [1/5] Установка пакета Coturn через apt..."
apt-get update -q
apt-get install -y coturn curl

# Определение внешнего IP-адреса сервера (критично для обхода симметричного 4G NAT)
PUBLIC_IP=$(curl -s -m 5 https://api.ipify.org || curl -s -m 5 https://ifconfig.me || hostname -I | awk '{print $1}')
echo "🌐 Обнаружен внешний IP сервера: ${PUBLIC_IP}"

echo "⚙️ [2/5] Активация демона в /etc/default/coturn..."
if [ -f "/etc/default/coturn" ]; then
    sed -i 's/#TURNSERVER_ENABLED=1/TURNSERVER_ENABLED=1/g' /etc/default/coturn
    sed -i 's/TURNSERVER_ENABLED=0/TURNSERVER_ENABLED=1/g' /etc/default/coturn
    if ! grep -q "TURNSERVER_ENABLED=1" /etc/default/coturn; then
        echo "TURNSERVER_ENABLED=1" >> /etc/default/coturn
    fi
fi

# Проверка наличия SSL сертификатов Let's Encrypt для шифрованного TURNS (порт 5349)
SSL_CONFIG=""
CERT_FILE=""
KEY_FILE=""

for domain in "edusfera.by" "turn.edusfera.by"; do
    if [ -f "/etc/letsencrypt/live/${domain}/fullchain.pem" ]; then
        CERT_FILE="/etc/letsencrypt/live/${domain}/fullchain.pem"
        KEY_FILE="/etc/letsencrypt/live/${domain}/privkey.pem"
        break
    fi
done

if [ -n "$CERT_FILE" ]; then
    echo "🔒 Найдены SSL-сертификаты Let's Encrypt (${CERT_FILE}), активируем TURNS (5349)..."
    SSL_CONFIG="cert=${CERT_FILE}
pkey=${KEY_FILE}"
else
    echo "ℹ️ SSL-сертификаты не найдены, будет работать стандартный TURN (порт 3478 UDP/TCP)"
fi

echo "📝 [3/5] Создание конфигурации /etc/turnserver.conf..."
cat << EOF > /etc/turnserver.conf
# ==============================================================================
# Coturn configuration for Edusfera.by (Production Bare-Metal)
# ==============================================================================
listening-port=3478
tls-listening-port=5349
min-port=49152
max-port=49200

# Внешний IP для корректного ретранслирования медиапотоков (4G/LTE)
external-ip=${PUBLIC_IP}

fingerprint
lt-cred-mech
realm=edusfera.by
user=edusfera:change-me-strong-password

# Ограничения и квоты
total-quota=200
bps-capacity=0
stale-nonce=600

# Логирование
log-file=/var/log/turnserver.log
no-cli
no-multicast-peers

# Безопасность
no-tlsv1
no-tlsv1_1

${SSL_CONFIG}
EOF

echo "🛡️ [4/5] Настройка фаервола (UFW / iptables)..."
if command -v ufw &> /dev/null; then
    ufw allow 3478/tcp comment 'Coturn TURN TCP' || true
    ufw allow 3478/udp comment 'Coturn TURN UDP' || true
    ufw allow 5349/tcp comment 'Coturn TURNS TLS' || true
    ufw allow 5349/udp comment 'Coturn TURNS TLS' || true
    ufw allow 49152:49200/udp comment 'Coturn Relay UDP' || true
    echo "   ✓ Правила UFW добавлены (порты 3478, 5349, 49152-49200)"
fi

echo "🔄 [5/5] Перезапуск и включение службы Coturn в автозагрузку..."
systemctl daemon-reload
systemctl enable coturn
systemctl restart coturn

echo ""
echo "🎉 ============================================================================"
echo "✅ COTURN УСПЕШНО УСТАНОВЛЕН И ЗАПУЩЕН НА EDUSFERA.BY!"
echo "============================================================================"
echo "Параметры подключения:"
echo "   - Домен: edusfera.by"
echo "   - Порт: 3478 (UDP и TCP)"
echo "   - Логин: edusfera"
echo "   - Пароль: change-me-strong-password"
echo "   - Realm: edusfera.by"
echo "============================================================================"
echo ""
echo "Проверка работы портов на сервере:"
ss -tulpn | grep -E '3478|5349' || true

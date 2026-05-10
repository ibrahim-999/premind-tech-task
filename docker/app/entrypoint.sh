#!/usr/bin/env bash
set -euo pipefail

wait_for() {
    local host="$1"
    local port="$2"
    local name="$3"
    local timeout=60
    local elapsed=0

    until nc -z "$host" "$port" >/dev/null 2>&1; do
        if [ "$elapsed" -ge "$timeout" ]; then
            echo "[entrypoint] timed out waiting for $name at $host:$port"
            exit 1
        fi
        echo "[entrypoint] waiting for $name at $host:$port..."
        sleep 1
        elapsed=$((elapsed + 1))
    done
    echo "[entrypoint] $name is ready"
}

cd /var/www/html

wait_for "${DB_HOST:-mysql}" "3306" "MySQL"
wait_for "${REDIS_HOST:-redis}" "6379" "Redis"

if [ ! -f /etc/nginx/certs/premind.crt ] || [ ! -f /etc/nginx/certs/premind.key ]; then
    echo "[entrypoint] generating self-signed TLS cert for localhost"
    openssl req -x509 -newkey rsa:4096 -nodes -days 365 \
        -keyout /etc/nginx/certs/premind.key \
        -out /etc/nginx/certs/premind.crt \
        -subj "/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" 2>/dev/null
    chmod 644 /etc/nginx/certs/premind.crt
    chmod 640 /etc/nginx/certs/premind.key
fi

if [ -d storage ] && [ -d bootstrap/cache ]; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
fi

if [ -f artisan ] && [ -f .env ]; then
    if ! grep -qE "^APP_KEY=base64:.+" .env; then
        echo "[entrypoint] generating APP_KEY"
        php artisan key:generate --force --no-interaction || true
    fi

    if php artisan list 2>/dev/null | grep -q "jwt:secret"; then
        if ! grep -qE "^JWT_SECRET=.+" .env; then
            echo "[entrypoint] generating JWT_SECRET"
            php artisan jwt:secret --force --no-interaction || true
        fi
    fi

    echo "[entrypoint] running migrations (idempotent)"
    php artisan migrate --force --no-interaction || true

    if [ "${RUN_SEED:-false}" = "true" ]; then
        echo "[entrypoint] running seeders (idempotent)"
        php artisan db:seed --force --no-interaction || true
    fi
fi

echo "[entrypoint] handing off to supervisord"
exec "$@"

#!/bin/sh
set -e

mkdir -p /var/log/supervisor

echo "Waiting for pgsql..."
for i in $(seq 1 30); do
    if nc -z pgsql 5432 2>/dev/null; then
        echo "pgsql is ready"
        break
    fi
    sleep 2
done

echo "Waiting for redis..."
for i in $(seq 1 30); do
    if nc -z redis 6379 2>/dev/null; then
        echo "redis is ready"
        break
    fi
    sleep 2
done

if [ ! -f .env ]; then
  cp .env.docker .env
fi

if ! grep -q '^APP_KEY=.$' .env; then
  php artisan key:generate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

echo "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf

#!/bin/sh
set -e

echo "Waiting for pgsql..."
until pg_isready -h pgsql -U "${DB_USERNAME:-laravel}" -d "${DB_DATABASE:-laravel}" >/dev/null 2>&1; do
  sleep 2
done

echo "Waiting for redis..."
until redis-cli -h redis ping >/dev/null 2>&1; do
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

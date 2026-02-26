#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chmod -R ug+rwx storage bootstrap/cache || true

# Non-fatal if link already exists or filesystem differs by environment.
php artisan storage:link >/dev/null 2>&1 || true

# Clear stale cached config/routes/views so updated Render env values are applied.
php artisan optimize:clear >/dev/null 2>&1 || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"

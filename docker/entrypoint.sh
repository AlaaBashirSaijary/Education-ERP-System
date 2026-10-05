#!/bin/sh
set -eu
cd /var/www/html

# Many platforms inject PORT; Apache must listen on it.
PORT="${PORT:-80}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "${APP_KEY:-}" ]; then
  if [ "${SCHOOL_DEMO_MODE:-}" = "true" ]; then
    # Demo only: a throw-away key is fine (visitors just sign in again after a restart).
    export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    echo "SCHOOL_DEMO_MODE: generated a temporary APP_KEY" >&2
  else
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2; exit 1
  fi
fi

as_app() { runuser -u www-data -- "$@"; }

as_app php artisan migrate --force
if [ "${SCHOOL_DEMO_MODE:-}" = "true" ]; then
  as_app php artisan demo:reset --if-empty
else
  as_app php artisan school:create-admin --if-missing
fi
as_app php artisan config:cache
as_app php artisan route:cache
as_app php artisan view:cache

# Background workers: parent notifications (queue) and the daily jobs (scheduler). Restarted if they exit.
( while true; do as_app php artisan queue:work --tries=3 --max-time=3600 --sleep=3 || true; sleep 2; done ) &
( while true; do as_app php artisan schedule:run --no-interaction || true; sleep 60; done ) &

exec apache2-foreground

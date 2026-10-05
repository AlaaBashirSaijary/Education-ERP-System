#!/usr/bin/env bash
# One-time setup for a Codespace: SQLite database with demo data. Demo only, never for production.
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || cp .env.example .env
composer install --no-interaction --prefer-dist
php artisan key:generate --force

# The Codespace URL is https behind GitHub's proxy.
if [ -n "${CODESPACE_NAME:-}" ]; then
  DOMAIN="${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
  sed -i "s#^APP_URL=.*#APP_URL=https://${CODESPACE_NAME}-8000.${DOMAIN}#" .env
  grep -q '^TRUSTED_PROXIES=' .env || echo 'TRUSTED_PROXIES=*' >> .env
fi

grep -q '^SCHOOL_DEMO_LOGINS=' .env || echo 'SCHOOL_DEMO_LOGINS=true' >> .env
touch database/database.sqlite
php artisan migrate:fresh --force
php artisan db:seed --class=DemoSeeder --force

npm ci --no-audit --no-fund
npm run build

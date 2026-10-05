#!/usr/bin/env bash
# Pull the latest code and rebuild everything that changes with it.
# Skipping `npm run build` is the usual reason the site keeps its OLD look after `git pull`.
set -euo pipefail
cd "$(dirname "$0")/.."

git pull
composer install --no-interaction --prefer-dist
npm ci --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan optimize:clear
echo
echo "Updated. Restart 'php artisan serve' if it is running, then hard-refresh the browser (Cmd+Shift+R)."

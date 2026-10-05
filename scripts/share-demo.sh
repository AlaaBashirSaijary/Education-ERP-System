#!/usr/bin/env bash
# Share a LIVE trial of the school system from this computer with a public https link.
# No hosting account and no card needed. The link works while this window stays open.
#
#   DEMO_WHATSAPP=962791234567 bash scripts/share-demo.sh
#
# Uses its own database file (database/demo.sqlite), so your normal data is never touched.
# Public link: uses `cloudflared` if installed (brew install cloudflared), otherwise ssh + localhost.run.
set -euo pipefail
cd "$(dirname "$0")/.."

PORT="${PORT:-8000}"

if [ -z "${DEMO_WHATSAPP:-}" ] && [ -t 0 ]; then
  read -r -p "Your WhatsApp number with country code and no + (e.g. 962791234567), or press Enter to skip: " DEMO_WHATSAPP || true
fi

# Demo mode only for this run (environment variables beat .env, so .env stays as it was).
export SCHOOL_DEMO_MODE=true APP_ENV=local APP_DEBUG=false TRUSTED_PROXIES='*' ASSUME_HTTPS=true
export SCHOOL_NAME="${SCHOOL_NAME:-مدرستنا}" DEMO_WHATSAPP="${DEMO_WHATSAPP:-}" DEMO_EMAIL="${DEMO_EMAIL:-}"
export DB_CONNECTION=sqlite DB_DATABASE="$PWD/database/demo.sqlite"
export SESSION_DRIVER=database QUEUE_CONNECTION=database CACHE_STORE=database MESSAGING_CHANNEL=log
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"   # several visitors at once

[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force >/dev/null
[ -f public/build/manifest.json ] || { echo "Building the interface (first run)..."; npm ci --no-audit --no-fund >/dev/null && npm run build >/dev/null; }

touch database/demo.sqlite
php artisan migrate --force >/dev/null
php artisan demo:reset --if-empty

php artisan queue:work --sleep=2 >/dev/null 2>&1 &      # parent messages (logged only)
php artisan schedule:work >/dev/null 2>&1 &              # periodic reset of the demo data
php artisan serve --host=127.0.0.1 --port="$PORT" >/dev/null 2>&1 &
trap 'kill $(jobs -p) 2>/dev/null || true' EXIT INT TERM
sleep 2

echo
echo "  Running locally:  http://127.0.0.1:$PORT   (demo mode, data resets every ${DEMO_RESET_HOURS:-6} hours)"
if [ "${NO_TUNNEL:-}" = "1" ]; then wait; exit 0; fi

echo "  Creating the public link... (keep this window open; press Ctrl+C to stop sharing)"
echo
if ! command -v cloudflared >/dev/null 2>&1 && command -v brew >/dev/null 2>&1 && [ -t 0 ]; then
  read -r -p "  cloudflared gives a faster, steadier link. Install it now with Homebrew? [Y/n] " yn || true
  case "${yn:-Y}" in [Yy]*|"") brew install cloudflared || true ;; esac
fi
if command -v cloudflared >/dev/null 2>&1; then
  cloudflared tunnel --no-autoupdate --url "http://127.0.0.1:$PORT"
else
  echo "  (cloudflared not found, using ssh + localhost.run. For a steadier link: brew install cloudflared)"
  ssh -o StrictHostKeyChecking=accept-new -o ServerAliveInterval=30 -R "80:127.0.0.1:$PORT" nokey@localhost.run
fi

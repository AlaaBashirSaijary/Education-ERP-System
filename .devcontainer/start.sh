#!/usr/bin/env bash
# Starts the web server and the queue worker (parent messages are logged, not really sent).
cd "$(dirname "$0")/.."
pkill -f "artisan serve" 2>/dev/null || true
pkill -f "artisan queue:work" 2>/dev/null || true
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/serve.log 2>&1 &
nohup php artisan queue:work --sleep=2 > /tmp/queue.log 2>&1 &
cat <<'MSG'

  School ERP is running on port 8000 (open the "Ports" tab and click the globe icon).
  Keep the port Private: the demo accounts below have known passwords.

    admin@school.test        change-me-now   (admin)
    teacher@school.test      password        (teacher)
    accountant@school.test   password        (accountant)
    parent@school.test       password        (parent of "سارة أحمد")

MSG

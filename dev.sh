#!/usr/bin/env bash
# KoriePay dev console — one-command environment recovery + server start.
#
# The Arena sandbox rebuilds between sessions: system PHP, Composer and
# vendor/ do NOT persist (workspace files do). When the preview "hangs", the
# usual cause is a dead `php artisan serve` process and/or a wiped vendor/.
# This script repairs both and serves on :8088.
#
# Usage:  ./dev.sh            (start / recover the dev server)
#         ./dev.sh check      (only verify the environment, no server)
set -euo pipefail

cd "$(dirname "$0")"
PORT="${PORT:-8088}"

echo "== 1/4 PHP =="
if ! command -v php >/dev/null 2>&1; then
    echo "PHP missing — installing (needs sudo, one time per session)..."
    sudo apt-get update -qq >/dev/null
    sudo apt-get install -y -qq php-cli php-mbstring php-sqlite3 php-xml php-zip php-curl php-bcmath php-intl php-gd >/dev/null
fi
php -v | head -1

echo "== 2/4 Composer =="
if ! command -v composer >/dev/null 2>&1; then
    if [ -f /home/user/composer-setup.php ]; then
        echo "Installing from composer-setup.php..."
        php /home/user/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    else
        echo "composer-setup.php not found — downloading..."
        php -r "copy('https://getcomposer.org/installer', '/tmp/cs.php');" \
            && php /tmp/cs.php --install-dir=/usr/local/bin --filename=composer --quiet
    fi
fi
composer --version | head -1

echo "== 3/4 vendor =="
if [ ! -f vendor/autoload.php ] || [ ! -f vendor/composer/autoload_real.php ]; then
    echo "vendor incomplete — composer install..."
    composer install --no-interaction --no-progress 2>&1 | tail -2
fi
php artisan --version | head -1

if [ "${1:-}" = "check" ]; then
    echo "== environment OK — not starting server =="
    exit 0
fi

echo "== 4/4 server =="
if pgrep -f "artisan serve" >/dev/null 2>&1; then
    echo "Server already running on :${PORT}"
else
    echo "Starting php artisan serve on 0.0.0.0:${PORT}..."
    exec php artisan serve --host=0.0.0.0 --port="${PORT}"
fi

#!/bin/sh
set -e

# One image runs three roles. Compose starts the web app, the queue worker and
# the scheduler as separate containers so a slow extraction never blocks a page.
role="${1:-web}"

php artisan config:cache
php artisan route:cache
php artisan view:cache

case "$role" in
    web)
        php artisan migrate --force
        exec frankenphp php-server --root /app/public --listen "${SERVER_NAME:-:8080}"
        ;;
    worker)
        exec php artisan queue:work --tries=3 --max-time=3600
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac

#!/usr/bin/env bash
set -e

if [[ -n "${APP_KEY_BASE64:-}" ]]; then
    export APP_KEY="base64:${APP_KEY_BASE64}"
fi

if [[ -z "${APP_KEY:-}" ]]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo "APP_KEY generada automaticamente para esta instancia."
fi

mkdir -p \
    /var/www/html/database \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord -n -c /etc/supervisord.conf

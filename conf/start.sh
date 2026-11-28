#!/usr/bin/env bash
set -euo pipefail

if [[ -z "${APP_KEY:-}" && -n "${APP_KEY_BASE64:-}" ]]; then
    export APP_KEY="base64:${APP_KEY_BASE64}"
fi

if [[ -z "${APP_KEY:-}" ]]; then
    echo "Configura APP_KEY o APP_KEY_BASE64 para conservar las sesiones entre despliegues." >&2
    exit 1
fi
unset APP_KEY_BASE64

if ! php -r '
    $key = getenv("APP_KEY");
    if (str_starts_with($key, "base64:")) {
        $key = base64_decode(substr($key, 7), true);
    }
    exit(is_string($key) && strlen($key) === 32 ? 0 : 1);
'; then
    echo "APP_KEY debe contener una clave válida de 32 bytes." >&2
    exit 1
fi

if [[ -n "${RENDER_EXTERNAL_URL:-}" ]]; then
    export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"
    export SESSION_SECURE_COOKIE=true
fi

case "${DB_CONNECTION:-}" in
    pgsql|mysql|mariadb)
        if [[ -z "${DB_URL:-}" && ( -z "${DB_HOST:-}" || -z "${DB_DATABASE:-}" || -z "${DB_USERNAME:-}" || -z "${DB_PASSWORD:-}" ) ]]; then
            echo "Configura DB_URL o las credenciales de la base persistente de la aplicación." >&2
            exit 1
        fi
        ;;
    sqlite)
        if [[ -n "${DB_URL:-}" || ! "${DB_DATABASE:-}" =~ ^/var/data/[[:alnum:]_.-]+\.sqlite$ ]] ||
            ! awk '$5 == "/var/data" { found = 1 } END { exit !found }' /proc/self/mountinfo; then
            echo "SQLite requiere un disco montado en /var/data y DB_DATABASE=/var/data/minutas.sqlite." >&2
            exit 1
        fi

        touch "$DB_DATABASE"
        chown www-data:www-data /var/data "$DB_DATABASE"
        chmod 700 /var/data
        chmod 600 "$DB_DATABASE"
        ;;
    *)
        echo "Configura DB_CONNECTION con pgsql, mysql, mariadb o sqlite con disco persistente." >&2
        exit 1
        ;;
esac

mkdir -p \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache

php artisan migrate --force --no-interaction
php artisan minutas:admin-inicial --no-interaction
unset BOOTSTRAP_ADMIN_NAME BOOTSTRAP_ADMIN_EMAIL BOOTSTRAP_ADMIN_PASSWORD
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

exec /usr/bin/supervisord -n -c /etc/supervisord.conf

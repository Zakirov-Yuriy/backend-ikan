#!/bin/sh
# Запуск бэкенда в контейнере (Render). Диск у бесплатного Render не
# сохраняется: при каждом запуске база создаётся заново и заполняется
# текстами и фото с сайта (SiteContentSeeder), правки из админки живут до
# следующего засыпания или перезапуска.
set -e

# Адрес бэкенда для ссылок на фото: Render сам передаёт RENDER_EXTERNAL_URL
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:${PORT:-8000}}}"

# Ключ шифрования. Если не задан в настройках, создаётся на этот запуск
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY
fi

touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force

# Тестовые апартаменты: DEMO_APARTMENTS=l3 (или l3,l4) в настройках Render.
# Диск у бесплатного Render не сохраняется, поэтому они создаются при каждом запуске
if [ -n "$DEMO_APARTMENTS" ]; then
    for complex in $(echo "$DEMO_APARTMENTS" | tr ',' ' '); do
        php artisan demo:apartments "$complex" || true
    done
fi

# Пользователь админки из настроек Render (ADMIN_EMAIL и ADMIN_PASSWORD)
if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    php artisan admin:user "$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"
else
    echo "ADMIN_EMAIL и ADMIN_PASSWORD не заданы: войти в админку не получится"
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --no-reload

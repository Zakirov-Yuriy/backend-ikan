# Бэкенд ИКАН для Render (и любого хостинга с Docker).
# Render сам собирает образ по этому файлу из репозитория на GitHub.
# Запуск: docker/start.sh (база, загрузка текстов и фото, пользователь админки, сервер).
FROM php:8.4-cli

# unzip нужен Composer'у; SQLite, mbstring и остальное уже есть в образе PHP
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Сначала только список пакетов: слой с vendor пересобирается, лишь когда он меняется
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && sed -i 's/\r$//' docker/start.sh \
    && chmod +x docker/start.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    PHP_CLI_SERVER_WORKERS=4

CMD ["docker/start.sh"]

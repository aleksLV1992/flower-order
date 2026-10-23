FROM dunglas/frankenphp:1-php8.5-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql pcov opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "date.timezone=UTC" > "$PHP_INI_DIR/conf.d/tz.ini" \
    && echo "pcov.enabled=0" > "$PHP_INI_DIR/conf.d/zz-pcov.ini"

WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --optimize \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup \
    && chmod -R 777 var

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SERVER_NAME=:8000 \
    FRANKENPHP_CONFIG="worker /app/public/index.php"
EXPOSE 8000
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# syntax=docker/dockerfile:1

FROM node:22-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-progress --prefer-dist --optimize-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize

FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache nginx supervisor icu-libs libpq postgresql-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql opcache \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /build/vendor ./vendor
COPY --from=assets /build/public/build ./public/build
COPY . .

COPY deploy/docker/nginx.conf /etc/nginx/http.d/default.conf
COPY deploy/docker/supervisord.conf /etc/supervisord.conf
COPY deploy/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY deploy/docker/php.ini /usr/local/etc/php/conf.d/app.ini

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && rm -rf /var/www/html/.env /var/www/html/storage/logs/*.log \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD wget -qO- http://127.0.0.1:8080/up >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]

# syntax=docker/dockerfile:1

# Node is only used to compile Vite assets, not in the PHP runtime image.
FROM node:24-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.mjs ./
COPY resources ./resources
RUN npm run build


# Start from a Laravel-oriented PHP-FPM + nginx image. It listens on 8080 and
# includes Composer, MySQL/PostgreSQL PDO drivers, Redis, OPcache, and ZIP.
FROM serversideup/php:8.4-fpm-nginx AS php-base

USER root
RUN install-php-extensions bcmath gd intl
USER www-data


# Install PHP dependencies separately so Docker can cache them until the
# Composer manifests change. The full application is needed because Composer
# runs Laravel's package discovery script after installation.
FROM php-base AS php-dependencies

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader


FROM php-base AS runtime

ENV PHP_OPCACHE_ENABLE=1 \
    PHP_DISPLAY_ERRORS=Off \
    PHP_POST_MAX_SIZE=50M \
    PHP_UPLOAD_MAX_FILE_SIZE=50M

WORKDIR /var/www/html

USER root

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=php-dependencies /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=frontend /app/public ./public

RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage \
    && chmod -R ug+rwX bootstrap/cache storage

USER www-data

EXPOSE 8080

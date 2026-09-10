# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Frontend assets
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js tsconfig.json components.json ./
RUN npm run build

# ---------------------------------------------------------------------------
# PHP dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

# The autoloader is dumped once the application source is present so that the
# classmap covers app/ as well as vendor/.
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------------------------------------------------------------------------
# Runtime
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4 AS runtime

# poppler-utils supplies pdftotext, which reads the text layer of PDF uploads.
RUN apt-get update \
    && apt-get install --no-install-recommends -y poppler-utils \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pdo_pgsql pgsql bcmath intl opcache zip

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY . .

# The build context excludes the contents of these directories, so recreate the
# tree the framework expects to write into.
RUN mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Caddy keeps its own state under these paths and the container does not run
# as root.
RUN mkdir -p /data/caddy /config/caddy \
    && chown -R www-data:www-data /data/caddy /config/caddy

USER www-data

ENV SERVER_NAME=:8080
EXPOSE 8080

ENTRYPOINT ["entrypoint"]
CMD ["web"]

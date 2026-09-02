# ═══════════════════════════════════════════════════════════════════
#  Aegis-X — Multi-stage Dockerfile
#  Target: PHP 8.3 + Swoole + Laravel Octane
#  Image size target: < 150 MB (production stage)
# ═══════════════════════════════════════════════════════════════════

# ── Stage 1: Composer dependencies ─────────────────────────────────
FROM composer:2.7 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# Install production deps only; no dev tools in the final image
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --prefer-dist

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ── Stage 2: Node.js assets ────────────────────────────────────────
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN npm run build

# ── Stage 3: Production runtime ────────────────────────────────────
FROM php:8.3-cli-alpine AS production

LABEL maintainer="Aegis-X Team"
LABEL description="Quiz-Pro (Aegis-X) — Laravel Octane + Swoole"

# ── System dependencies ────────────────────────────────────────────
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    redis \
    shadow \
    supervisor \
    unzip \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pcntl \
        sockets \
        zip \
    && pecl install swoole redis \
    && docker-php-ext-enable swoole redis \
    && rm -rf /var/cache/apk/* /tmp/*

# ── PHP Configuration ──────────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-aegis.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/98-opcache.ini

# ── App user (non-root) ────────────────────────────────────────────
RUN addgroup -g 1000 aegis && \
    adduser -u 1000 -G aegis -s /bin/sh -D aegis

WORKDIR /var/www/html

# ── Copy application ───────────────────────────────────────────────
COPY --from=vendor /app/vendor          ./vendor
COPY --from=vendor /app/composer.json   ./composer.json
COPY --from=assets /app/public/build    ./public/build
COPY --chown=aegis:aegis . .

# ── Fix permissions ────────────────────────────────────────────────
RUN chown -R aegis:aegis /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Production caches (baked into image) ──────────────────────────
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan event:cache \
    && php artisan view:cache

USER aegis

EXPOSE 8000

# ── Health check ───────────────────────────────────────────────────
HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD curl -sf http://localhost:8000/up || exit 1

# ── Start Octane (Swoole) ──────────────────────────────────────────
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=auto", "--task-workers=auto", "--max-requests=500"]

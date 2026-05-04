# =============================================================================
# MOKA Laravel Application — Staging Dockerfile
# Base: PHP 8.1-FPM on Alpine for a lean, fast image
# =============================================================================

# --- Stage 1: Composer dependency resolution ---
FROM composer:2.6 AS composer_deps

WORKDIR /app

# Copy only the files Composer needs first so this layer is cached
# unless composer.json or composer.lock actually changes.
COPY composer.json composer.lock ./

# Install production dependencies only, no scripts yet (app code not copied)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Now copy the full application source and generate the optimized autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev


# --- Stage 2: Final runtime image ---
FROM php:8.1-fpm-alpine3.17

# Labels for traceability
LABEL maintainer="DevOps <devops@moka.app>" \
      app="moka" \
      env="staging"

# ---------------------------------------------------------------------------
# System dependencies
# ---------------------------------------------------------------------------
# libpng / libjpeg / freetype → GD image processing
# icu-dev                     → intl extension
# libzip-dev                  → zip extension
# libxml2-dev                 → xml/soap extensions
# oniguruma-dev               → mbstring extension
# bash curl git unzip         → general tooling
RUN apk add --no-cache \
        bash \
        curl \
        git \
        unzip \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        libxml2-dev \
        oniguruma-dev \
        icu-dev \
        icu-libs \
        linux-headers

# ---------------------------------------------------------------------------
# PHP extensions
# ---------------------------------------------------------------------------
# Configure GD with JPEG + FreeType support before building
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg

# Install all required extensions in a single RUN layer to keep image small
RUN docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        curl \
        zip \
        gd \
        bcmath \
        intl \
        opcache \
        pcntl

# Redis PECL extension (not available via docker-php-ext-install)
RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

# ---------------------------------------------------------------------------
# PHP-FPM & OPcache tuning for staging
# ---------------------------------------------------------------------------
COPY deployment/staging/php/php-fpm-staging.conf /usr/local/etc/php-fpm.d/zz-staging.conf
COPY deployment/staging/php/opcache-staging.ini  /usr/local/etc/php/conf.d/opcache-staging.ini

# ---------------------------------------------------------------------------
# Application setup
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

# Copy vendor directory from the composer stage (optimized autoloader)
COPY --from=composer_deps /app/vendor ./vendor

# Copy the full application source
COPY . .

# Laravel requires write access to storage/ and bootstrap/cache/
# Use www-data (the php-fpm runtime user) as owner
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Ensure the storage directory structure that Laravel expects exists
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# PHP-FPM listens on TCP port 9000 (consumed by Nginx upstream)
EXPOSE 9000

# Run as www-data for least privilege
USER www-data

CMD ["php-fpm"]

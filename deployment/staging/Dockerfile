# =============================================================================
# MOKA Laravel Application — Staging Dockerfile
# =============================================================================

# --- Stage 1: Composer dependency resolution ---
FROM composer:2 AS composer_deps

WORKDIR /app

COPY composer.json ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs


# --- Stage 2: Final runtime image ---
FROM php:8.2-fpm

LABEL maintainer="DevOps <devops@moka.app>" app="moka" env="staging"

# System dependencies
RUN apt-get update && apt-get install -y \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libxml2-dev \
        libonig-dev \
        libicu-dev \
        curl \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        bcmath \
        intl \
        opcache \
        pcntl

# Redis
RUN pecl install redis && docker-php-ext-enable redis

# PHP-FPM & OPcache config
COPY deployment/staging/php/php-fpm-staging.conf /usr/local/etc/php-fpm.d/zz-staging.conf
COPY deployment/staging/php/opcache-staging.ini  /usr/local/etc/php/conf.d/opcache-staging.ini

WORKDIR /var/www/html

COPY --from=composer_deps /app/vendor ./vendor
COPY . .

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

EXPOSE 9000
USER www-data
CMD ["php-fpm"]

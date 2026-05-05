FROM php:8.2-fpm

LABEL maintainer="DevOps <devops@moka.app>" app="moka" env="staging"

# System dependencies + PHP extensions in one layer
RUN apt-get update && apt-get install -y \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libzip-dev libxml2-dev libonig-dev libicu-dev \
        curl git unzip zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql mbstring xml zip gd bcmath intl opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
COPY deployment/staging/php/php-fpm-staging.conf /usr/local/etc/php-fpm.d/zz-staging.conf
COPY deployment/staging/php/opcache-staging.ini  /usr/local/etc/php/conf.d/opcache-staging.ini

WORKDIR /var/www/html

# Copy app source
COPY . .

# Install dependencies
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize --no-dev --no-scripts

# Storage & permissions
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

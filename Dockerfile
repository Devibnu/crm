# ========================
# Stage 1: Composer Build
# ========================
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ========================
# Stage 2: PHP 8.4 FPM
# ========================
FROM php:8.4-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    bash \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
    postgresql-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl

WORKDIR /var/www

# Copy app
COPY . .

# Copy vendor
COPY --from=vendor /app/vendor /var/www/vendor

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

USER www-data

CMD ["php-fpm"]

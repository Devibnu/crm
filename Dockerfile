FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN chown -R www-data:www-data /var/www

FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libonig-dev libsodium-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_pgsql mbstring sodium curl \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=dependencies /app/vendor /var/www/html/vendor
COPY docker-entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

EXPOSE 10000
CMD ["app-entrypoint"]
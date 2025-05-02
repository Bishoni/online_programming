FROM php:8.2-fpm

WORKDIR /var/www/html

RUN apt-get update \
 && apt-get install -y libzip-dev libpq-dev unzip \
 && docker-php-ext-install zip pdo_pgsql \
 && docker-php-ext-enable zip pdo_pgsql

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 9000
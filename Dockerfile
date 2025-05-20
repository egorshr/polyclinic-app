FROM php:8.2-apache


RUN apt-get update && apt-get install -y \
    && docker-php-ext-install pdo pdo_mysql



COPY composer.json /var/www/
WORKDIR /var/www


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


RUN composer install --no-dev --optimize-autoloader


COPY ./src /var/www/html


RUN chown -R www-data:www-data /var/www/html


RUN cp -r /var/www/vendor /var/www/html/
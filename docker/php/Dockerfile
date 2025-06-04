FROM php:8.2-apache


RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev libpng-dev libxml2-dev libonig-dev libxslt-dev g++ \
 && docker-php-ext-install intl pdo_mysql zip gd xml mbstring xsl \
 && a2enmod rewrite


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html


RUN chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \;

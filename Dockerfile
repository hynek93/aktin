FROM php:8.2-apache

RUN sed -i "s|/var/www/html|/var/www/html/www|g" /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN docker-php-ext-install pdo pdo_mysql
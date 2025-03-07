FROM php:8.2-apache

# Nastavení správného DocumentRoot
RUN sed -i "s|/var/www/html|/var/www/html/www|g" /etc/apache2/sites-available/000-default.conf

# Aktivace mod_rewrite (potřebné pro Nette)
RUN a2enmod rewrite

# Nastavení oprávnění pro Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Instalace základních PHP rozšíření
RUN docker-php-ext-install pdo pdo_mysql
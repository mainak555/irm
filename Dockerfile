FROM php:8.2-apache

# PDO MySQL extension — only runtime dependency beyond vanilla PHP
RUN docker-php-ext-install pdo_mysql \
 && a2enmod rewrite

COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy project files (.env is excluded via .dockerignore — supply at runtime)
COPY . .

# Web server needs read access; no write needed (DB handles all mutations)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

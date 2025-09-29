FROM php:8.2-apache

# Cài pdo_pgsql và pgsql
RUN docker-php-ext-install pgsql pdo pdo_pgsql

# Bật mod_rewrite nếu cần
RUN a2enmod rewrite

# Copy source code
COPY . /var/www/html/

# Quyền
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

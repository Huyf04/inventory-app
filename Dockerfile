FROM php:8.2-apache

# Cài thư viện cần thiết để build pgsql
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql

# Bật mod_rewrite nếu cần
RUN a2enmod rewrite

# Copy source code
# copy toàn bộ code vào /var/www/html
COPY src/ /var/www/html/


# Quyền cho apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

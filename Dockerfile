# Dockerfile
FROM php:8.1-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable rewrite (if cần)
RUN a2enmod rewrite

# Copy app
COPY src/ /var/www/html/

# Set permissions (nếu cần)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]

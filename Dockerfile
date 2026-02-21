# Use official PHP with Apache
FROM php:8.2-apache

# Enable Apache rewrite module (optional but useful)
RUN a2enmod rewrite

# Copy project files into Apache web directory
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80
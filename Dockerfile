FROM php:8.1-apache

# Install required extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create and set permissions for data files
RUN touch /var/www/html/users.json && \
    chmod 666 /var/www/html/users.json && \
    touch /var/www/html/error.log && \
    chmod 666 /var/www/html/error.log

# Set proper ownership
RUN chown -R www-data:www-data /var/www/html/

# Create custom PHP configuration
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 60" >> /usr/local/etc/php/conf.d/uploads.ini

# Expose port
EXPOSE 80

CMD ["apache2-foreground"]
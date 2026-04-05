FROM php:8.1-apache

# Enable mod_rewrite untuk CI3
RUN a2enmod rewrite

# Install libcurl dulu (dependency), baru install php extension curl
RUN apt-get update \
    && apt-get install -y libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install curl

# Konfigurasi Apache: izinkan .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Copy seluruh project CI3
COPY . /var/www/html/

# Set permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/application/cache \
    && chmod -R 755 /var/www/html/application/logs

EXPOSE 80

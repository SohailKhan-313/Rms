FROM php:8.2-apache

# Install required Linux packages and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix Railway AH00534 MPM conflict and enable required Apache modules
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite headers alias

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Set proper ownership and permissions
RUN chown -R www-data:www-data /var/www/html

# Setup entrypoint script and guarantee Unix LF line endings
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN sed -i 's/\r$//' /docker-entrypoint.sh && chmod +x /docker-entrypoint.sh

# Default port
EXPOSE 8080 80

ENTRYPOINT ["/docker-entrypoint.sh"]

FROM php:8.2-apache

# Install required Linux packages, embedded MariaDB server, and PHP extensions
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    mariadb-server \
    mariadb-client \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    bash \
    procps \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure ultra-lightweight MariaDB footprint for free cloud container limits (~40MB RAM)
RUN mkdir -p /etc/mysql/mariadb.conf.d \
    && printf "[mysqld]\nperformance_schema = OFF\ninnodb_buffer_pool_size = 32M\ninnodb_log_buffer_size = 4M\nkey_buffer_size = 8M\nmax_connections = 30\nskip-name-resolve = 1\nbind-address = 0.0.0.0\n" > /etc/mysql/mariadb.conf.d/99-rms-lowmem.cnf

# Ensure MariaDB runtime directories have correct ownership
RUN mkdir -p /var/lib/mysql /var/run/mysqld \
    && chown -R mysql:mysql /var/lib/mysql /var/run/mysqld \
    && chmod 777 /var/run/mysqld

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

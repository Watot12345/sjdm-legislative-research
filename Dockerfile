FROM php:8.2-apache

# Install system dependencies and PHP extensions required by the app
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy all application code
COPY . /var/www/html/

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create necessary directories and set appropriate permissions
RUN mkdir -p /var/www/html/uploads /var/www/html/logs /var/www/html/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/uploads /var/www/html/logs /var/www/html/cache

# Copy entrypoint script and set permissions
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose ports
EXPOSE 8000 8080 7860

# Run dynamic entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]

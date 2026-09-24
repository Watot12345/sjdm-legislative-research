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

# Copy dependency definition files first for layer caching
COPY composer.json composer.lock* /var/www/html/

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application code with proper ownership directly (prevents layer bloat)
COPY --chown=www-data:www-data . /var/www/html/

# Create necessary directories and set appropriate permissions
RUN mkdir -p /var/www/html/uploads /var/www/html/logs /var/www/html/cache && \
    chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs /var/www/html/cache && \
    chmod -R 775 /var/www/html/uploads /var/www/html/logs /var/www/html/cache

# Change Apache default listening port from 80 to 8000
RUN sed -i 's/80/8000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Expose port 8000
EXPOSE 8000

# Start Apache server in foreground
CMD ["apache2-foreground"]

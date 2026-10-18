# Stage 1: Build dependencies
FROM composer:2 AS build-stage
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist

# Stage 2: Final PHP Environment
FROM php:8.2-fpm
WORKDIR /var/www

# Install required system packages
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Copy vendor from build stage
COPY --from=build-stage /app/vendor /var/www/vendor
COPY --from=build-stage /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --no-dev --optimize

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000

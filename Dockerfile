# Stage 1: Dependencies build
FROM composer:2 AS build-stage
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist

# Stage 2: Final Runtime (Updated to PHP 8.3)
FROM php:8.3-fpm
WORKDIR /var/www

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Copy vendor & composer
COPY --from=build-stage /app/vendor /var/www/vendor
COPY --from=build-stage /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Create required Laravel directories
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache

# Generate autoloader ignoring scripts
RUN composer dump-autoload --no-dev --optimize --no-scripts

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000

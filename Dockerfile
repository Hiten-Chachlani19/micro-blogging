FROM php:8.2-fpm

# System dependencies aur required PHP extensions install karo
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Composer ko latest image se copy karo
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Pehle dependencies copy aur install karo (Memory issue aur cache optimization ke liye)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Baaki project code copy karo
COPY . .

# Final autoloader generate karo
RUN composer dump-autoload --optimize

# Storage permissions set karo
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000

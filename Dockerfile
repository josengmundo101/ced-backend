# Use official PHP image with FPM
FROM php:8.2-fpm

# Install required system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_pgsql gd mbstring exif pcntl bcmath zip \
 && apt-get clean && rm -rf /var/lib/apt/lists/*
# Copy Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy all source code
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Cache Laravel configs safely (Render has no .env until runtime)
RUN php artisan config:clear || true \
    php artisan cache:clear || true \
    php artisan route:clear || true \
    php artisan view:clear || true

# Permissions fix for storage
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose Laravel's port
EXPOSE 8000

# Start the Laravel API server
CMD php artisan serve --host=0.0.0.0 --port=8000

# -----------------------------
# 1. Base PHP image with FPM
# -----------------------------
FROM php:8.2-fpm

# -----------------------------
# 2. Install system dependencies
# -----------------------------
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# -----------------------------
# 3. Copy Composer from official image
# -----------------------------
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# -----------------------------
# 4. Set working directory
# -----------------------------
WORKDIR /var/www

# -----------------------------
# 5. Copy project files
# -----------------------------
COPY . .

# -----------------------------
# 6. Install PHP dependencies
# -----------------------------
RUN composer install --no-dev --optimize-autoloader

# -----------------------------
# 7. Cache Laravel configuration
# -----------------------------
RUN php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache || true

# -----------------------------
# 8. Fix permissions
# -----------------------------
RUN chown -R www-data:www-data storage bootstrap/cache

# -----------------------------
# 9. Expose port & run app
# -----------------------------
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

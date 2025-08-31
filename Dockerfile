# Use official PHP 8.2 FPM Alpine image
FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache --update \
    iputils \
    git unzip curl \
    libpng-dev jpeg-dev freetype-dev \
    oniguruma-dev libxml2-dev libzip-dev \
    postgresql-dev \
    autoconf g++ make openssl-dev

# Configure PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mbstring zip pdo pdo_mysql pdo_pgsql bcmath pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy application files
COPY . .

# Finish composer setup
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
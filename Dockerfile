FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    mysql-client \
    oniguruma-dev

# Install PHP extensions (without Redis for now)
RUN docker-php-ext-install pdo pdo_mysql bcmath

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application
COPY . .

# Set permissions
RUN chown -R www-data:www-data /app

EXPOSE 9000

CMD ["php-fpm"]

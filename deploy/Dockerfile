FROM php:8.3-fpm-bookworm

# Install system deps + Python3 + nginx
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip libonig-dev libzip-dev libpng-dev libjpeg-dev \
    nginx supervisor \
    python3 python3-pip python3-venv \
    && docker-php-ext-install pdo pdo_mysql bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Python face recognition deps
RUN pip3 install --break-system-packages --no-cache-dir \
    mediapipe opencv-python-headless numpy

# Create app structure (mirrors SynergyGig layout for Python import paths)
RUN mkdir -p /srv/web_synergygig /srv/python

# Copy face recognition service (parent python dir)
COPY python/ /srv/python/

# Copy PHP app
WORKDIR /srv/web_synergygig
COPY web_synergygig/composer.json web_synergygig/composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts 2>&1 || true

COPY web_synergygig/ ./
RUN composer dump-autoload --optimize --no-dev 2>&1 || true

# Ensure var/ directories exist with right perms
RUN mkdir -p var/cache var/log var/sessions \
    && chown -R www-data:www-data /srv/web_synergygig

# Copy nginx config
COPY deploy/nginx.conf /etc/nginx/sites-available/default

# Copy supervisor config
COPY deploy/supervisord.conf /etc/supervisor/conf.d/app.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]

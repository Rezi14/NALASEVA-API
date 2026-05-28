FROM php:8.4.14-fpm-alpine

# Install system dependencies & PostgreSQL driver
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpq-dev \
    bash \
    & \
    docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install dependencies laravel
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Setup permissions untuk Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy konfigurasi Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy konfigurasi Nginx bawaan untuk mengarah ke folder public Laravel
RUN printf '%s\n' \
    'server {' \
    '    listen 80;' \
    '    root /var/www/html/public;' \
    '    index index.php index.html;' \
    '    charset utf-8;' \
    '    location / {' \
    '        try_files $uri $uri/ /index.php?$query_string;' \
    '    }' \
    '    location ~ \.php$ {' \
    '        fastcgi_pass 127.0.0.1:9000;' \
    '        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' \
    '        include fastcgi_params;' \
    '    }' \
    '}' > /etc/nginx/http.d/default.conf

# Jalankan optimasi internal Laravel & otomatis migrasi saat container menyala
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan migrate --force && \
    /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
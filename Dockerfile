# ============================================================
# Stage 1 — PHP-FPM Backend
# ============================================================
FROM php:8.2-fpm-alpine AS backend

RUN apk add --no-cache \
    git curl zip unzip bash \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libxml2-dev oniguruma-dev libzip-dev icu-dev \
    # Redis client library
    && apk add --no-cache $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    # Install Redis PHP extension via PECL
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# PHP-FPM & OPcache optimization
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY inventory-app/composer.json inventory-app/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY inventory-app/ .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]

# ============================================================
# Stage 2 — Frontend Build (Vue 3)
# ============================================================
FROM node:20-alpine AS frontend-build

WORKDIR /app
COPY inventory-ui/package*.json ./
RUN npm ci --prefer-offline

COPY inventory-ui/ .

# API di-proxy oleh Nginx
ENV VITE_API_BASE_URL=/api
RUN npm run build-only

# ============================================================
# Stage 3 — Nginx
# ============================================================
FROM nginx:1.25-alpine AS nginx

# Enable gzip
RUN echo 'gzip on; gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript; gzip_min_length 1000;' \
    > /etc/nginx/conf.d/gzip.conf

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend-build /app/dist /usr/share/nginx/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]

# ============================================================
# Stage 1 — PHP-FPM Backend
# ============================================================
FROM php:8.2-fpm-alpine AS backend

RUN apk add --no-cache \
    git curl zip unzip bash \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libxml2-dev oniguruma-dev libzip-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

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
RUN npm ci

COPY inventory-ui/ .

# API akan di-proxy oleh Nginx, jadi pakai /api
ENV VITE_API_BASE_URL=/api
RUN npm run build-only

# ============================================================
# Stage 3 — Nginx (serves frontend SPA + proxy to PHP backend)
# ============================================================
FROM nginx:1.25-alpine AS nginx

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend-build /app/dist /usr/share/nginx/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]

#!/bin/sh
set -e

# Hanya php-fpm yang jalankan migrate & seed
# queue & scheduler langsung jalankan command mereka sendiri
if [ "$1" = "php" ] && [ "$2" = "artisan" ]; then
  exec "$@"
fi

echo "⏳ Waiting for MySQL..."
until php artisan db:monitor --max=1 2>/dev/null; do
  echo "  MySQL not ready, retrying in 3s..."
  sleep 3
done
echo "✅ MySQL is up!"

echo "🔄 Running migrations..."
php artisan migrate --force

echo "🌱 Seeding database (if fresh)..."
php artisan db:seed --force 2>/dev/null || echo "  Seeding skipped (already seeded)"

echo "⚡ Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🚀 Starting PHP-FPM..."
exec php-fpm

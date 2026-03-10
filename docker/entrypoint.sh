#!/bin/sh
set -e

# queue & scheduler langsung exec command mereka, skip migrate
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

# Seed hanya saat fresh install (cek user tabel kosong)
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$USER_COUNT" = "0" ]; then
  echo "🌱 Seeding database..."
  php artisan db:seed --force
  echo "💵 Normalizing customer credits to USD..."
  php artisan db:seed --class=CreditNormalizationSeeder --force
else
  echo "⏭️  Seeding skipped (data already exists)"
  # Tetap jalankan credit normalization jika ada customer dengan kredit Rupiah
  echo "💵 Running credit normalization..."
  php artisan db:seed --class=CreditNormalizationSeeder --force 2>/dev/null || true
fi

echo "⚡ Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear stale OPcache
php artisan optimize

echo "🚀 Starting PHP-FPM..."
exec php-fpm

#!/bin/sh
set -e

echo "Initializing Hospital Management System..."

# Clear and cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link --force

# Set correct permissions
chmod -R 775 storage bootstrap/cache public/images

echo "Starting services..."

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"

#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Starting build process..."

# Install Composer dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Cache Laravel configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force --no-interaction

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

# Set proper permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache

# Generate Swagger documentation (if needed)
if php artisan list | grep -q "l5-swagger:generate"; then
    echo "Generating API documentation..."
    php artisan l5-swagger:generate || true
fi

echo "Build completed successfully!"


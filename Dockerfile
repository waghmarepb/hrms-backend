# Use PHP 8.1 with Apache
FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set permissions
RUN chmod -R 775 storage bootstrap/cache

# Expose port
EXPOSE 8080

# Create startup script
RUN echo '#!/bin/bash\n\
set -e\n\
echo "Running migrations..."\n\
php artisan migrate --force --no-interaction\n\
echo "Caching configuration..."\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
echo "Creating storage link..."\n\
php artisan storage:link || true\n\
echo "Generating API documentation..."\n\
php artisan l5-swagger:generate || true\n\
echo "Starting server on port $PORT..."\n\
php -S 0.0.0.0:$PORT -t public\n\
' > /var/www/start.sh && chmod +x /var/www/start.sh

# Start the application
CMD ["/var/www/start.sh"]


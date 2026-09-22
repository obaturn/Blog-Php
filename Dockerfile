FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip pdo

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock /var/www/html/

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts --no-progress --ignore-platform-reqs

# Copy Laravel application
COPY . .

# Install Node dependencies and build frontend
WORKDIR /var/www/html/frontend
RUN npm ci --no-audit --no-fund && npm run build

# Copy built frontend to Laravel public directory
WORKDIR /var/www/html
RUN rm -rf public/* \
    && cp -r frontend/dist/* public/ \
    && cp frontend/dist/index.html public/404.html

# Install Laravel package assets
WORKDIR /var/www/html
RUN composer dump-autoload

# Generate application key if not set
RUN if [ ! -f .env ] || [ "x$(grep -c '^APP_KEY=base64:' .env)" = "x0" ]; then cp .env.example .env && php artisan key:generate --force; fi

# Create storage directories
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache && chmod -R 777 storage bootstrap/cache

# Configure Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache modules
RUN a2enmod rewrite headers

EXPOSE 80

# Run migrations on startup
CMD ["sh", "-c", "php artisan migrate --force && apache2-foreground"]

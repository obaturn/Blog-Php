FROM php:8.3-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip pkg-config \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 20
RUN apt-get update && apt-get install -y --no-install-recommends curl ca-certificates gnupg \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock /var/www/html/
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts --no-progress --ignore-platform-reqs

COPY . .

WORKDIR /var/www/html/frontend
RUN npm ci --no-audit --no-fund && npm run build

WORKDIR /var/www/html
RUN rm -rf public/favicon.ico public/robots.txt \
    && cp -r frontend/dist/* public/ \
    && cp frontend/dist/index.html public/404.html

WORKDIR /var/www/html
RUN composer dump-autoload

RUN if [ ! -f .env ] || [ "x$(grep -c '^APP_KEY=base64:' .env)" = "x0" ]; then cp .env.example .env && php artisan key:generate --force; fi
RUN if [ -f .env ]; then sed -i '/^DB_/d' .env; fi

RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

EXPOSE 80

CMD ["sh", "-c", "php artisan migrate --force && apache2-foreground"]

FROM php:8.2-apache

# ── System dependencies ──
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    libonig-dev libxml2-dev libicu-dev default-mysql-client stockfish \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring xml gd zip intl bcmath pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# ── Composer ──
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Node.js 20 LTS ──
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# ── Apache config ──
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' \
    /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# ── PHP config ──
RUN echo "upload_max_filesize=100M\npost_max_size=100M\nmemory_limit=256M" \
    > /usr/local/etc/php/conf.d/app.ini

# ── Project files ──
WORKDIR /var/www/html
COPY . .

# ── Bootstrap .env + storage dirs (before composer, which needs storage/ to exist) ──
RUN cp .env.example .env \
    && mkdir -p storage/framework/{sessions,views,cache/data} storage/logs storage/app/public

# ── Install dependencies ──
# Use composer update (not install) because the lock file may be stale
# with respect to composer.json additions like laravel/reverb.
RUN composer update --no-dev --optimize-autoloader --no-interaction \
    && npm install && npm run build \
    && rm -f public/hot \
    && rm -rf node_modules

# ── Generate APP_KEY + clear build-time cache (entrypoint re-caches with real DB) ──
RUN php artisan key:generate --force --no-interaction \
    && php artisan config:clear && php artisan route:clear && php artisan view:clear

# ── Permissions ──
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Entrypoint ──
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

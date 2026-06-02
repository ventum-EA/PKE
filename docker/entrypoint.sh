#!/bin/bash
set -e

echo "--- Chess Platform Entrypoint ---"

# CRITICAL: remove Vite dev server marker — forces @vite() to use built assets
rm -f /var/www/html/public/hot

# Create .env if missing (key:generate needs a file to write to)
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
    # Override with docker-compose environment variables
    sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-mysql}|" .env
    sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-3306}|" .env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-chess_platform}|" .env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-chess}|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-secret}|" .env
    sed -i "s|^MAIL_HOST=.*|MAIL_HOST=${MAIL_HOST:-mailpit}|" .env
    sed -i "s|^CACHE_STORE=.*|CACHE_STORE=${CACHE_STORE:-database}|" .env
    sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}|" .env
    sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=${SESSION_DRIVER:-database}|" .env
fi

# Wait for MySQL
echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
for i in $(seq 1 30); do
    if mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; then
        echo "MySQL is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "WARNING: MySQL not responding after 60s, continuing anyway..."
    fi
    sleep 2
done

# Generate app key if missing
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force --no-interaction
fi

# Run migrations + seed
echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning (may be OK on first run)"
echo "Seeding database..."
php artisan db:seed --force --no-interaction 2>&1 || echo "Seed warning (may be OK if already seeded)"

# Cache (clear first to avoid stale state)
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions (entrypoint runs as root, Apache runs as www-data)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Verify Vite build assets exist
if [ -f public/build/.vite/manifest.json ]; then
    echo "Vite manifest: public/build/.vite/manifest.json"
elif [ -f public/build/manifest.json ]; then
    echo "Vite manifest: public/build/manifest.json"
else
    echo "WARNING: No Vite manifest found — frontend assets may not load!"
fi

echo "Platform ready at http://localhost"
exec "$@"

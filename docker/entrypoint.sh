#!/bin/bash
set -e

echo "⏳ Waiting for MySQL..."
until mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    sleep 2
done
echo "✅ MySQL is ready"

# Generate app key if missing
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    php artisan key:generate --force --no-interaction
fi

# Run migrations + seed (--force for production)
php artisan migrate --force --no-interaction 2>/dev/null || true
php artisan db:seed --force --no-interaction 2>/dev/null || true

# Cache config for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🚀 Platform ready at http://localhost"
exec "$@"

#!/bin/bash
set -e

echo "--- Chess Platform Entrypoint ---"

# CRITICAL: remove Vite dev server marker — forces @vite() to use built assets
rm -f /var/www/html/public/hot

# Create .env if missing
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

# ALWAYS override with docker-compose environment variables (handles restarts)
sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-mysql}|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-3306}|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-chess_platform}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-chess}|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-secret}|" .env
sed -i "s|^MAIL_HOST=.*|MAIL_HOST=${MAIL_HOST:-mailpit}|" .env
sed -i "s|^CACHE_STORE=.*|CACHE_STORE=${CACHE_STORE:-database}|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}|" .env
sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=${SESSION_DRIVER:-database}|" .env

# Session + Sanctum — ensure present (critical for HTTP localhost auth)
grep -q "^SESSION_DOMAIN=" .env 2>/dev/null || echo "SESSION_DOMAIN=${SESSION_DOMAIN:-localhost}" >> .env
grep -q "^SESSION_SECURE_COOKIE=" .env 2>/dev/null || echo "SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE:-false}" >> .env
grep -q "^SANCTUM_STATEFUL_DOMAINS=" .env 2>/dev/null || echo "SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost,localhost:80,127.0.0.1}" >> .env
# Force correct values even if keys already exist
sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=${SESSION_DOMAIN:-localhost}|" .env
sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE:-false}|" .env
sed -i "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost,localhost:80,127.0.0.1}|" .env

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

# Run migrations + seed (with visible errors)
echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1
echo "Seeding database..."
php artisan db:seed --force --no-interaction 2>&1 || echo "SEED FAILED — see error above"

# Fallback: ensure admin user exists even if seeder failed
echo "Ensuring admin user exists..."
php artisan tinker --execute="
    use App\Models\User;
    use Illuminate\Support\Facades\Hash;
    if (!User::where('email', 'admin@chess.local')->exists()) {
        \$u = User::create(['name'=>'admin','email'=>'admin@chess.local','password'=>Hash::make('password'),'elo_rating'=>2000]);
        try { \$u->assignRole('admin'); } catch (\Throwable \$e) { echo 'Role assign skipped: '.\$e->getMessage(); }
        echo 'Admin created: admin@chess.local';
    } else {
        echo 'Admin already exists';
    }
" 2>&1

# ALWAYS clear and rebuild cache (ensures env changes take effect)
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Verify Vite manifest
if [ -f public/build/.vite/manifest.json ]; then
    echo "Vite manifest: OK"
elif [ -f public/build/manifest.json ]; then
    echo "Vite manifest: OK (legacy path)"
else
    echo "WARNING: No Vite manifest — frontend may not load"
fi

echo "Session: domain=${SESSION_DOMAIN:-?}, secure=${SESSION_SECURE_COOKIE:-?}"
echo "Sanctum: ${SANCTUM_STATEFUL_DOMAINS:-not set}"
echo "Platform ready at http://localhost"
exec "$@"

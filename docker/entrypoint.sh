#!/bin/bash
set -e

a2dismod mpm_event 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

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

# Reverb (WebSocket broadcasting)
sed -i "s|^BROADCAST_CONNECTION=.*|BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-reverb}|" .env
grep -q "^REVERB_APP_ID=" .env 2>/dev/null || echo "REVERB_APP_ID=${REVERB_APP_ID:-chess-platform}" >> .env
grep -q "^REVERB_APP_KEY=" .env 2>/dev/null || echo "REVERB_APP_KEY=${REVERB_APP_KEY:-chess-platform-key}" >> .env
grep -q "^REVERB_APP_SECRET=" .env 2>/dev/null || echo "REVERB_APP_SECRET=${REVERB_APP_SECRET:-chess-platform-secret}" >> .env
grep -q "^REVERB_HOST=" .env 2>/dev/null || echo "REVERB_HOST=${REVERB_HOST:-127.0.0.1}" >> .env
grep -q "^REVERB_PORT=" .env 2>/dev/null || echo "REVERB_PORT=${REVERB_PORT:-8080}" >> .env
grep -q "^REVERB_SCHEME=" .env 2>/dev/null || echo "REVERB_SCHEME=${REVERB_SCHEME:-http}" >> .env
grep -q "^REVERB_SERVER_HOST=" .env 2>/dev/null || echo "REVERB_SERVER_HOST=${REVERB_SERVER_HOST:-0.0.0.0}" >> .env
grep -q "^REVERB_SERVER_PORT=" .env 2>/dev/null || echo "REVERB_SERVER_PORT=${REVERB_SERVER_PORT:-8080}" >> .env
sed -i "s|^BROADCAST_CONNECTION=.*|BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-reverb}|" .env
sed -i "s|^REVERB_APP_ID=.*|REVERB_APP_ID=${REVERB_APP_ID:-chess-platform}|" .env
sed -i "s|^REVERB_APP_KEY=.*|REVERB_APP_KEY=${REVERB_APP_KEY:-chess-platform-key}|" .env
sed -i "s|^REVERB_APP_SECRET=.*|REVERB_APP_SECRET=${REVERB_APP_SECRET:-chess-platform-secret}|" .env
sed -i "s|^REVERB_HOST=.*|REVERB_HOST=${REVERB_HOST:-127.0.0.1}|" .env
sed -i "s|^REVERB_PORT=.*|REVERB_PORT=${REVERB_PORT:-8080}|" .env
sed -i "s|^REVERB_SCHEME=.*|REVERB_SCHEME=${REVERB_SCHEME:-http}|" .env
sed -i "s|^REVERB_SERVER_HOST=.*|REVERB_SERVER_HOST=${REVERB_SERVER_HOST:-0.0.0.0}|" .env
sed -i "s|^REVERB_SERVER_PORT=.*|REVERB_SERVER_PORT=${REVERB_SERVER_PORT:-8080}|" .env

# Session + Sanctum — ensure present (critical for HTTP localhost auth)
grep -q "^SESSION_DOMAIN=" .env 2>/dev/null || echo "SESSION_DOMAIN=${SESSION_DOMAIN:-localhost}" >> .env
grep -q "^SESSION_SECURE_COOKIE=" .env 2>/dev/null || echo "SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE:-false}" >> .env
grep -q "^SANCTUM_STATEFUL_DOMAINS=" .env 2>/dev/null || echo "SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost,localhost:80,127.0.0.1}" >> .env
# Force correct values even if keys already exist
sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=${SESSION_DOMAIN:-localhost}|" .env
sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE:-false}|" .env
sed -i "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost,localhost:80,127.0.0.1}|" .env

# Wait for MySQL (PHP PDO check — same driver as Laravel, no mysqladmin needed)
echo "Waiting for MySQL..."
for i in $(seq 1 15); do
    if php -r "try{new PDO('mysql:host='.\$_SERVER['DB_HOST'].';port='.(\$_SERVER['DB_PORT']??3306),\$_SERVER['DB_USERNAME'],\$_SERVER['DB_PASSWORD']);echo 'ok';}catch(\Exception \$e){exit(1);}" 2>/dev/null | grep -q ok; then
        echo "MySQL is ready"
        break
    fi
    [ $i -eq 15 ] && echo "WARNING: MySQL not responding, continuing..."
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

# ── Start Laravel Reverb (WebSocket server) in background ──
if [ "${BROADCAST_CONNECTION:-reverb}" = "reverb" ]; then
    echo "Starting Reverb WebSocket server on 0.0.0.0:8080..."
    php artisan reverb:start --host=0.0.0.0 --port=8080 &
    REVERB_PID=$!
    echo "Reverb started (PID $REVERB_PID)"
fi

echo "Platform ready at http://localhost"
exec "$@"

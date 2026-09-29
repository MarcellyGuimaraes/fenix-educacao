#!/bin/bash
set -e

cd /var/www/html

echo "==> Installing PHP dependencies (if needed)..."
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Ensure .env exists
if [ ! -f ".env" ]; then
    echo "==> Creating .env from .env.example"
    cp .env.example .env
fi

# Generate app key if missing
if ! grep -q "^APP_KEY=base64" .env; then
    echo "==> Generating application key"
    php artisan key:generate --force
fi

echo "==> Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)getenv('DB_PORT')) ? 0 : 1);" 2>/dev/null; do
    sleep 2
    echo "   ...still waiting for database"
done
echo "==> Database is up."

echo "==> Running migrations and seeders..."
php artisan migrate --force --seed

echo "==> Clearing config cache..."
php artisan config:clear

echo "==> Generating API docs (Swagger)..."
php artisan l5-swagger:generate || echo "!! Swagger generation skipped"

echo "==> Starting: $@"
exec "$@"

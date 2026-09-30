#!/bin/bash
set -e

cd /var/www/html

# No modo padrão o vendor/ já vem da imagem. No modo dev, o volume
# backend_vendor começa vazio e é preenchido aqui na primeira subida.
if [ ! -f "vendor/autoload.php" ]; then
    echo "==> Installing PHP dependencies..."
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

# Remove caches de uma subida anterior antes de rodar comandos que leem a config
php artisan optimize:clear > /dev/null

echo "==> Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)getenv('DB_PORT')) ? 0 : 1);" 2>/dev/null; do
    sleep 2
    echo "   ...still waiting for database"
done
echo "==> Database is up."

echo "==> Running migrations and seeders..."
php artisan migrate --force --seed

echo "==> Generating API docs (Swagger)..."
php artisan l5-swagger:generate || echo "!! Swagger generation skipped"

# O cache de config congela as variáveis de ambiente: por isso roda depois do
# .env/APP_KEY e das migrations. APP_OPTIMIZE=false (modo dev) mantém sem cache.
if [ "${APP_OPTIMIZE:-false}" = "true" ]; then
    echo "==> Caching config, routes, events and views (optimize)..."
    php artisan optimize
else
    echo "==> Clearing caches (optimize:clear)..."
    php artisan optimize:clear
fi

# Arquivos gerados como root no boot precisam ser graváveis pelo php-fpm
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "==> Starting: $@"
exec "$@"

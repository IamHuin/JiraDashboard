#!/bin/bash
set -o errexit
set -o nounset

wait_for_db=${WAIT_FOR_DB:-"true"}
max_retries=${DB_WAIT_RETRIES:-30}

echo "Starting entrypoint..."

if [ "$wait_for_db" = "true" ]; then
    echo "Waiting for database connection..."
    retry_count=0
    until php -r "
        try {
            new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    "; do
        retry_count=$((retry_count + 1))
        if [ "$retry_count" -ge "$max_retries" ]; then
            echo "ERROR: Could not connect to database after $max_retries attempts. Exiting."
            exit 1
        fi
        echo "Database not ready, retrying in 2s... (attempt $retry_count/$max_retries)"
        sleep 2
    done
    echo "Database connected!"
fi

php artisan config:cache

echo "Running migrations..."
php artisan migrate --force

echo "Seeding admin account..."
php artisan db:seed --force

php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Starting queue worker for jira-sync..."
php artisan queue:work --queue=jira-sync --tries=3 --timeout=600 &

echo "Entrypoint finished, starting Apache..."

exec "$@"
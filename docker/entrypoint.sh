#!/bin/bash
set -o errexit
set -o nounset

run_seed=${RUN_SEED:-"false"}
wait_for_db=${WAIT_FOR_DB:-"true"}

echo "Starting entrypoint..."

# Chờ DB sẵn sàng (bật/tắt qua biến môi trường WAIT_FOR_DB)
if [ "$wait_for_db" = "true" ]; then
    echo "Waiting for database connection..."
    until php artisan db:show > /dev/null 2>&1; do
        echo "Database not ready, retrying in 2s..."
        sleep 2
    done
    echo "Database connected!"
fi

# Cache config (giúp Laravel chạy nhanh hơn ở production)
php artisan config:cache

# Chạy migrate
echo "Running migrations..."
php artisan migrate --force

echo "Seeding admin account..."
php artisan db:seed --force

# Cache route & view
php artisan route:cache
php artisan view:cache

# Đảm bảo storage vẫn đúng quyền (phòng khi mount volume đè lên)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Entrypoint finished, starting Apache..."

# Chạy tiếp lệnh CMD gốc (apache2-foreground)
exec "$@"
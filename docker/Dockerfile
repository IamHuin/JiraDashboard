# --- Giai đoạn 1: Build Frontend bằng Node.js ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app

# Đứng từ thư mục gốc, copy package.json vào để install
COPY package*.json ./
RUN npm ci

# Copy toàn bộ mã nguồn ở thư mục gốc vào container để build Vite
COPY . .
RUN npm run build

# --- Giai đoạn 2: Chạy ứng dụng với PHP 8.3 & Nginx ---
FROM php:8.3-fpm-alpine

# Cài đặt công cụ hệ thống và extension PHP cho Laravel 13
RUN apk add --no-cache nginx supervisor libpng-dev libxml2-dev zip unzip git oniguruma-dev linux-headers
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Lấy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy toàn bộ source code từ thư mục gốc vào container chạy
COPY . .

# Lấy phần Frontend tĩnh đã build từ Giai đoạn 1 ném vào public/build
COPY --from=frontend-builder /app/public/build ./public/build

# Cài đặt dependencies PHP sạch
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Phân quyền cho Laravel ghi log và cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ĐÈ cấu hình Web Server (Lấy từ chính thư mục docker/ đang chứa file này trên máy vào hệ thống)
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
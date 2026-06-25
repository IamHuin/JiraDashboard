# --- Giai đoạn 1: Build Frontend bằng Node.js ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# --- Giai đoạn 2: Chạy ứng dụng với PHP 8.4 & Nginx ---
FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx supervisor libpng-dev libxml2-dev zip unzip git oniguruma-dev linux-headers
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy toàn bộ source code vào trước
COPY . .

# Lấy phần Frontend tĩnh đã build từ Giai đoạn 1 ném vào public/build
COPY --from=frontend-builder /app/public/build ./public/build

# Cài đặt dependencies PHP sạch
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Phân quyền cho Laravel ghi log và cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# SỬA TẠI ĐÂY: Di chuyển file cấu hình từ thư mục docker đã được copy vào hệ thống
RUN cp docker/nginx.conf /etc/nginx/http.d/default.conf && \
    cp docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
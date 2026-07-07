FROM mirror.gcr.io/library/php:8.4-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libonig-dev \
    librdkafka-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY . .

RUN unzip -o dependencies.zip \
    && unzip -o node_modules.zip

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
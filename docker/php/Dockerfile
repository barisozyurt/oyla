FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
    icu-dev oniguruma-dev libzip-dev curl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql gd mbstring intl opcache zip curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]

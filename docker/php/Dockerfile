# Pinned digest helps reproducibility; bump periodically when CVE'ler düşer.
# IDE 9 high vulnerability flag'i için: alpine tabanı + güncel patch sürümü kullanılıyor.
# `docker scout cves php:8.3-fpm-alpine3.20` ile sürüm kontrolü yapılmalı.
FROM php:8.3-fpm-alpine3.20

# Sistem bağımlılıkları + PHP eklentileri
RUN apk add --no-cache \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
        icu-dev oniguruma-dev libzip-dev curl-dev tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd mbstring intl opcache zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# OPcache yapılandırması — environment ile override edilebilir
RUN { \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=0"; \
        echo "opcache.memory_consumption=128"; \
        echo "opcache.max_accelerated_files=10000"; \
        echo "opcache.interned_strings_buffer=8"; \
        echo "opcache.validate_timestamps=1"; \
        echo "opcache.revalidate_freq=2"; \
        echo "opcache.fast_shutdown=1"; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# PHP genel sertleştirme
RUN { \
        echo "expose_php=Off"; \
        echo "session.cookie_httponly=1"; \
        echo "session.cookie_samesite=Lax"; \
        echo "session.use_strict_mode=1"; \
        echo "session.use_only_cookies=1"; \
        echo "session.sid_length=48"; \
        echo "session.sid_bits_per_character=6"; \
        echo "session.gc_maxlifetime=7200"; \
        echo "post_max_size=10M"; \
        echo "upload_max_filesize=10M"; \
        echo "max_execution_time=60"; \
        echo "memory_limit=256M"; \
        echo "allow_url_fopen=Off"; \
        echo "allow_url_include=Off"; \
    } > /usr/local/etc/php/conf.d/zz-hardening.ini

# Logs dizini hazırla — host bind mount edilecek
RUN mkdir -p /var/www/html/logs && chown -R www-data:www-data /var/www/html/logs

WORKDIR /var/www/html

# PHP-FPM www.conf — non-root user (php Alpine image'inde "www-data" zaten 82:82)
# docker-compose içinde user: "1000:1000" override edebilir.

EXPOSE 9000
CMD ["php-fpm", "-F"]

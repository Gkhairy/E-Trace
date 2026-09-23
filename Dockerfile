# ============ E-Trace — image produksi (Railway / container apa pun) ============
# Dua peran dari SATU image: service "web" (nginx + php-fpm) dan service "worker"
# (scheduler + queue). Bedanya hanya di start command, jadi keduanya selalu
# memakai kode dan dependensi yang identik.

# ---------- Tahap 1: dependensi PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts: artisan belum ada di tahap ini.
# --ignore-platform-reqs: image composer tak memuat gmp/gd/sockets, padahal
# tahap ini hanya mengunduh paket. Ekstensinya dipasang di tahap runtime.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
        --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# ---------- Tahap 2: runtime ----------
FROM php:8.2-fpm-alpine

# gmp & bcmath WAJIB: dipakai AbiEncoder, ChainSigner, dan konversi wei->TLKM.
# Tanpa keduanya pembayaran gagal diam-diam di produksi.
# gd dibutuhkan phpspreadsheet (maatwebsite/excel) dan dompdf; sockets oleh
# php-amqplib, yang tetap terpasang walau antrean memakai driver database.
#
# Pustaka runtime (gmp, libzip, freetype, ...) sengaja dipasang TERPISAH dari
# .build-deps. Kalau ikut virtual package, `apk del` akan ikut membuangnya dan
# ekstensi PHP gagal dimuat saat container start — apk tidak tahu berkas .so
# masih menautinya.
RUN apk add --no-cache nginx supervisor gettext \
        gmp libzip icu-libs freetype libjpeg-turbo libpng \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        gmp-dev libzip-dev icu-dev freetype-dev libjpeg-turbo-dev libpng-dev \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gmp bcmath pdo_mysql zip exif pcntl opcache gd sockets \
    && apk del .build-deps

# OPcache: wajib untuk performa PHP di produksi.
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.max_accelerated_files=10000'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
 && { \
      echo 'upload_max_filesize=12M'; \
      echo 'post_max_size=12M'; \
      echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /app
COPY --from=vendor /app /app
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.web.conf /etc/supervisor/web.conf
COPY docker/supervisord.worker.conf /etc/supervisor/worker.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
 && chown -R www-data:www-data storage bootstrap/cache \
 && mkdir -p /run/nginx

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/web.conf", "-n"]

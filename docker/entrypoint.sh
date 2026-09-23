#!/bin/sh
# Entrypoint bersama untuk service web maupun worker.
set -e

: "${PORT:=8080}"
export PORT
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

UPLOAD_DIRS="product_images banner_images campaign_images store_images"
VOLUME=/app/public/uploads

# Foto unggahan ditulis ke public_path() oleh controller, sedangkan filesystem
# container bersifat sementara — tanpa volume, semua foto hilang tiap deploy.
# Railway hanya mengizinkan satu volume per service, jadi volume di-mount di
# satu titik lalu folder-folder itu di-symlink ke dalamnya.
if [ -d "$VOLUME" ]; then
    for d in $UPLOAD_DIRS; do
        # Pengisian awal: berkas bawaan repo disalin SEKALI, saat folder di
        # volume belum ada. Sesudah itu volume yang jadi sumber kebenaran dan
        # isi dari image tidak pernah menimpanya.
        if [ ! -d "$VOLUME/$d" ]; then
            mkdir -p "$VOLUME/$d"
            [ -d "/app/public/$d" ] && cp -a "/app/public/$d/." "$VOLUME/$d/" 2>/dev/null || true
        fi
        rm -rf "/app/public/$d"
        ln -sfn "$VOLUME/$d" "/app/public/$d"
    done
    chown -R www-data:www-data "$VOLUME"
else
    # Tanpa volume (mis. service worker) folder biasa sudah cukup.
    for d in $UPLOAD_DIRS; do mkdir -p "/app/public/$d"; done
    chown -R www-data:www-data $(for d in $UPLOAD_DIRS; do echo "/app/public/$d"; done)
fi

mkdir -p /app/storage/framework/cache /app/storage/framework/sessions \
         /app/storage/framework/views /app/storage/logs
chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Migrasi hanya dari SATU service (RUN_MIGRATIONS=true di service web saja),
# supaya web dan worker tidak menjalankannya bersamaan saat boot.
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

# Cache konfigurasi dibangun ulang tiap boot supaya perubahan env var di
# Railway langsung terpakai tanpa perlu ubah kode.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Laporkan ke log apakah rahasia yang di-seal terisi & cocok (tanpa mencetak nilainya).
# Variabel sealed tak bisa dilihat siapa pun; ini satu-satunya cara memastikannya.
# `|| true`: pemeriksaan ini tak boleh menggagalkan boot.
php artisan config:check || true

exec "$@"

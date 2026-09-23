#!/bin/sh
# Entrypoint bersama untuk service web maupun worker.
set -e

: "${PORT:=8080}"
export PORT
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Folder unggahan. Bila Railway Volume di-mount ke salah satu path ini, folder
# sudah ada tetapi pemiliknya root — www-data harus bisa menulis ke situ,
# kalau tidak unggah foto produk gagal dengan 500.
for d in public/product_images public/banner_images public/campaign_images \
         storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs; do
    mkdir -p "/app/$d"
done
chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    /app/public/product_images /app/public/banner_images /app/public/campaign_images

# Migrasi hanya dari SATU service (set RUN_MIGRATIONS=true di service web saja),
# supaya web dan worker tidak bertabrakan menjalankannya bersamaan.
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

# Cache konfigurasi: wajib untuk produksi, dan dibangun ulang tiap boot supaya
# perubahan env var di Railway langsung terpakai.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"

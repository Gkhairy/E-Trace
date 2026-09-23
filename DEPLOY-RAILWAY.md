# Deploy E-Trace ke Railway

Satu image, **dua service** dari repo yang sama:

| Service | Start command | Peran |
|---|---|---|
| `web` | *(default image)* | nginx + php-fpm, menerima trafik |
| `worker` | `supervisord -c /etc/supervisor/worker.conf -n` | scheduler + antrean |

Service `worker` **wajib ada**. Tanpa dia: explorer berhenti terindeks
(`transfers:index`), dan auto-refund / auto-selesai tidak pernah jalan
(`settlement:keep`).

## 1. Buat project

1. Railway → New Project → Deploy from GitHub → pilih `Gkhairy/E-Trace`.
2. Tambah **MySQL** dari menu New → Database → MySQL.
3. Duplikat service aplikasi jadi dua: `web` dan `worker`, keduanya dari repo yang sama.
   Pada `worker`, isi **Custom Start Command**:
   `supervisord -c /etc/supervisor/worker.conf -n`

## 2. Volume untuk foto unggahan

Foto produk/banner/donasi disimpan ke disk container lewat `move(public_path(...))`.
Disk container itu **ephemeral** — tanpa Volume, semua foto hilang tiap kali deploy,
sementara nama filenya masih tercatat di database (gambar jadi rusak, bukan kosong).

Di service `web`, tambah Volume dan mount ke:

```
/app/public/product_images
```

Ulangi untuk `/app/public/banner_images` dan `/app/public/campaign_images` bila
kedua fitur itu dipakai. Entrypoint sudah mengatur kepemilikan folder otomatis.

> Solusi jangka panjang yang lebih benar: pindah ke object storage
> (Cloudflare R2 / S3) dan ubah ketiga controller ke `Storage::disk('s3')`.

## 3. Variabel lingkungan

Set di **kedua** service (web dan worker) kecuali disebut lain.

### Wajib
```
APP_NAME=E-Trace
APP_ENV=production
APP_DEBUG=false
APP_KEY=                      # php artisan key:generate --show
APP_URL=https://<domain-railway-kamu>
FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database     # BUKAN rabbitmq — lihat catatan di bawah
FILESYSTEM_DISK=local

RUN_MIGRATIONS=true           # HANYA di service web
```

### Rahasia — jangan pernah masuk repo
```
WALLET_ENC_SECRET=            # kunci enkripsi wallet custodial; JANGAN diubah
                              # setelah ada wallet terbuat (kunci turunan)
KEEPER_ARBITER_PRIVATE_KEY=   # kosong = auto-settlement mati
INSURANCE_POOL_PRIVATE_KEY=
RAJAONGKIR_API_KEY=
TURNSTILE_SECRET_KEY=
```

### Blockchain
```
CHAIN_ID=97
CHAIN_RPC_URL=https://data-seed-prebsc-1-s1.bnbchain.org:8545/
CHAIN_EXPLORER_URL=https://testnet.bscscan.com
CHAIN_LOGS_RPC_URL=https://bsc-testnet-rpc.publicnode.com
ARCHIVE_RPC_URL=https://bsc-testnet.nodereal.io/v1/<key-kamu>
TLKM_ADDRESS=0x...
PAYMENT_GATEWAY_ADDRESS=0x...
DONATION_POOL_ADDRESS=0x...
PAYLATER_ADDRESS=0x...
```

Salin nilai alamat kontrak dari `.env` lokalmu — `.env` tidak ikut ke repo,
jadi nilainya harus diisi manual di dashboard Railway.

### Antrean: jangan pakai RabbitMQ
`.env` lokal memakai `QUEUE_CONNECTION=rabbitmq`. Di Railway itu berarti satu
service lagi yang jalan 24/7 dan menambah biaya, padahal bebannya cuma kirim
OTP/struk/notifikasi. Pakai `database` — tabel `jobs` sudah ada. Paket
`vladimir-yuldashev/laravel-queue-rabbitmq` boleh tetap terpasang, tinggal
tidak dipakai.

## 4. Setelah deploy pertama

Migrasi jalan otomatis di service `web` (`RUN_MIGRATIONS=true`). Yang masih
manual sekali saja — lewat Railway shell di service `worker`:

```
php artisan transfers:index --from=130594296
```

Itu menarik seluruh riwayat transfer TLKM sejak token dibuat (12 Sep 2026).
Tanpa itu, indexer hanya tumbuh maju dan riwayat lama tidak muncul di explorer.

## 5. Yang perlu dicek setelah live

- `/explorer` menampilkan transfer, dan jumlahnya bertambah setelah beberapa menit
  (bukti service `worker` hidup).
- Unggah foto produk, lalu picu satu deploy ulang — foto harus **masih ada**
  (bukti Volume ter-mount benar).
- Checkout testnet berhasil — kalau gagal dengan revert kosong, ekstensi `gmp`
  atau `bcmath` tidak terpasang di image.
- `APP_DEBUG=false`: halaman error tidak membocorkan stack trace.

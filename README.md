# E-Trace

**Marketplace e-commerce berbasis blockchain** — belanja pakai token kripto (TLKM) semudah e-wallet biasa, dengan pembayaran yang dijaga *smart contract escrow* dan setiap transaksi bisa diverifikasi publik di blockchain.

> Status: **prototipe kompetisi (testnet, belum diaudit)**. Jangan digunakan dengan dana sungguhan.

---

## Latar Belakang

Di marketplace konvensional, uang pembeli dipegang oleh perusahaan — pembeli harus percaya platform, penjual menunggu pencairan, dan alur dana tidak transparan. E-Trace memindahkan kepercayaan itu dari perusahaan ke **kode**: dana pembeli ditahan oleh smart contract (escrow) dan baru lepas ke penjual setelah pembeli mengonfirmasi barang diterima. Setiap pembayaran punya jejak on-chain yang tidak bisa diubah siapa pun.

## Fitur Utama

- **Pembayaran escrow via smart contract (token TLKM)** — dana ditahan kontrak, lepas ke penjual saat pembeli konfirmasi terima; bisa refund bila penjual tidak mengirim.
- **Escrow multi-penjual** — satu keranjang berisi banyak penjual; escrow dipisah per item, konfirmasi satu item tidak melepas dana penjual lain.
- **Verifikasi on-chain** — backend membaca kontrak sebagai sumber kebenaran (total, nominal, penjual), tidak mempercayai data dari browser.
- **Embedded wallet (email/HP + PIN)** — pengguna awam tidak perlu paham MetaMask/seed phrase; tanda tangan sekali, transaksi berikutnya cukup PIN.
- **Chatbot AI** — bantuan penggunaan aplikasi + pencarian produk (OpenAI, dibatasi ke ruang lingkup aplikasi).
- **Laporan keuangan penjual** — rekap pembelian otomatis dengan perhitungan HPP, ekspor Excel/PDF.
- **Dompet komunitas / donasi transparan** — alur donasi tercatat on-chain, **tanpa fee (gratis)**.
- **Keamanan akun** — registrasi OTP email (via RabbitMQ) + opsi 2FA authenticator.
- **Peran pengguna** — pembeli, penjual, dan pengawas (dispute).
- **Dwibahasa** — Indonesia & Inggris (Laravel localization).

## Tech Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Blade, Tailwind CSS v4, Vite, ethers.js |
| Database | MySQL |
| Antrean | RabbitMQ (email OTP & notifikasi) |
| Blockchain | Solidity 0.8.20, ERC-20 (TLKM), Ethereum **Sepolia** testnet |
| Integrasi Web3 | web3.php, ethereum-tx, keccak, elliptic-php |
| Lain-lain | google2fa (2FA), bacon-qr-code (QR), dompdf (PDF), maatwebsite/excel |

## Kebutuhan

- PHP 8.2+ dengan Composer
- Node.js 18+ dengan npm
- MySQL
- RabbitMQ (untuk antrean email/OTP)
- MetaMask (opsional, untuk penjual/pengujian on-chain)
- Kunci OpenAI API (untuk fitur chatbot)

## Instalasi

```bash
# 1. Install dependency PHP & JS
composer install
npm install

# 2. Siapkan environment
cp .env.example .env
php artisan key:generate

# 3. Atur .env (lihat bagian Konfigurasi di bawah), lalu migrasi + seed
php artisan migrate --seed

# 4. Build asset frontend
npm run build      # atau: npm run dev (mode pengembangan)

# 5. Jalankan aplikasi
php artisan serve
```

Jalankan **worker antrean** di terminal terpisah agar email OTP & notifikasi terkirim:

```bash
php artisan queue:work
```

## Konfigurasi (.env)

Isi minimal berikut di `.env`:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crypto
DB_USERNAME=root
DB_PASSWORD=

# Antrean (RabbitMQ)
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest

# Email (untuk OTP) — sesuaikan dengan SMTP kamu
MAIL_MAILER=smtp

# Chatbot
OPENAI_API_KEY=sk-...      # RAHASIA — jangan commit ke repo

# Blockchain (Ethereum Sepolia)
CHAIN_ID=11155111
```

Alamat smart contract (TLKM & PaymentGateway) diisi di `resources/views/layouts/app.blade.php`. Cara deploy kontrak ada di **`contracts/PANDUAN-DEPLOY.md`**.

> **Keamanan:** `.env` berisi rahasia (password DB, kunci OpenAI, konfigurasi wallet) dan **tidak** disertakan dalam paket ini. Jangan pernah commit `.env` ke repositori publik.

## Menjadikan Akun Sebagai Penjual

Agar tombol tambah produk muncul:

```bash
php artisan tinker
>>> \App\Models\User::where('email','emailkamu@contoh.com')->update(['role'=>'seller']);
```

## Alur Uji Coba (End-to-End)

1. Daftar / login (email + OTP, atau MetaMask).
2. Sebagai penjual, buat produk (harga dalam TLKM).
3. Sebagai pembeli (wallet punya TLKM), buka produk → **Beli** → approve token → bayar ke escrow.
4. Order tersimpan; cek tx hash di **Etherscan Sepolia** sebagai bukti transparansi.
5. Barang diterima → **Konfirmasi Terima** → dana lepas ke penjual. Bila tidak dikirim → **Refund**.

## Model Bisnis

Pendapatan platform = **fee 1% dari nilai transaksi**, ditanggung penjual (dipotong dari payout, tidak dibebankan ke pembeli). Donasi dan dompet komunitas **gratis (0% fee)** sebagai fitur sosial.

## Catatan Penting

- Berjalan di **testnet (Ethereum Sepolia)** dengan token uji coba — bukan uang sungguhan.
- Smart contract **belum diaudit**; ini prototipe untuk keperluan kompetisi/edukasi.
- Data pribadi (alamat, nomor telepon) disimpan di database dan **tidak** ditaruh on-chain — sejalan dengan UU PDP. Blockchain hanya menyimpan hash/data transaksi.
- Fitur QRIS masih dalam pengembangan (belum live).

## Struktur Ringkas

```
app/          Controller, Model, Service (SepoliaVerifier), Job
contracts/    Smart contract Solidity (TLKMToken, PaymentGateway) + panduan deploy
database/     Migrasi & seeder
resources/    Tampilan Blade + aset frontend
routes/       Definisi rute
lang/         Berkas terjemahan (ID/EN)
```

---

*E-Trace — dibangun sebagai proyek kompetisi. Belanja transparan, dana dijaga kode, bukan janji perusahaan.*

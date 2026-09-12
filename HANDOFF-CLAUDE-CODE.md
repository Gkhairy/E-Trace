# Handoff untuk Claude Code — Crypto E-Commerce (Laravel)

> ⚠️ **PEMBARUAN JARINGAN (migrasi):** Project kini menargetkan **BNB Smart Chain
> Testnet** (chainId **97**, explorer **https://testnet.bscscan.com**, gas **tBNB**).
> Konfigurasi jaringan ada di `config/chain.php` yang membaca `.env`
> (`CHAIN_ID`, `CHAIN_RPC_URL`, `CHAIN_EXPLORER_URL`, `CHAIN_NAME`, `*_ADDRESS`).
> Referensi **Ethereum/Arbitrum Sepolia** di narasi historis di bawah **sudah usang** —
> kontrak di-redeploy ke BSC Testnet. Panduan terbaru: `contracts/PANDUAN-DEPLOY.md`.

Dokumen ini merangkum project, apa yang sudah dikerjakan, dan apa yang belum.
Pakai bagian "PROMPT SIAP PAKAI" di bawah untuk memulai di Claude Code.

---

## 1. Gambaran Project

Toko e-commerce berbasis crypto. Pembeli login dengan wallet (MetaMask), admin
listing produk, pembeli bayar pakai token **TLKM** (token ERC-20 buatan sendiri)
melalui smart contract escrow. Tujuan utama: **transparansi transaksi** — setiap
pembelian tercatat on-chain dan bisa diverifikasi publik lewat block explorer.

**Stack:** Laravel 12 + PHP 8.2 + MySQL (database `crypto`), Blade + Tailwind (CDN),
ethers.js v6 (via CDN), MetaMask.

**Jaringan blockchain:** **Ethereum Sepolia** (chainId 11155111).
> Catatan: awalnya direncanakan Arbitrum Sepolia, tapi kontrak TLKM ternyata
> ter-deploy di Ethereum Sepolia, jadi seluruh project dikunci ke Ethereum Sepolia.

---

## 2. Smart Contract (folder `contracts/`)

- `TLKMToken.sol` — token ERC-20 (OpenZeppelin), 18 desimal, ada fungsi `mint` (owner only).
  **SUDAH DI-DEPLOY** di Ethereum Sepolia:
  `0xFbaa7F02bE3f151920D036cA4Eed2Fb1Ca3e0aEB`
  (supply awal 1.000.000 TLKM, owner = wallet deployer `0x04F5...da323`).

- `PaymentGateway.sol` — escrow: `payWithToken(token, seller, amount, productId, orderId)`
  menahan token, `confirmReceived(orderId)` melepas ke penjual, `refund(orderId)`
  setelah 3 hari. Emit event `Purchase`/`Completed`/`Refunded` untuk transparansi.
  **BELUM DI-DEPLOY.** Harus deploy di Ethereum Sepolia lalu tempel alamatnya.

- `PANDUAN-DEPLOY.md` — panduan deploy via Remix + faucet.

---

## 3. Yang Sudah Dikerjakan

- Kontrak TLKM & PaymentGateway ditulis dari nol.
- Frontend dikunci ke Ethereum Sepolia (`resources/views/layouts/app.blade.php`):
  konfigurasi `TARGET_NETWORK` (chainId 11155111), `TLKM_ADDRESS` sudah diisi,
  `TOKEN_DECIMALS = 18`. Fungsi JS: `connectWallet`, `checkNetwork`, `approveToken`,
  `payProduct`, `confirmReceived`, `refundOrder`.
- Flow pembelian di `resources/views/products/show.blade.php`:
  cek network → approve → pay (escrow) → POST `/order/store` → redirect `/orders`.
  Ada guard validasi (harga kosong, wallet invalid, alamat kontrak masih placeholder).
- `resources/views/products/index.blade.php` ditulis ulang (sebelumnya isinya keliru
  jadi kode routes). Sekarang grid produk + tombol "+ Add Product" (hanya admin).
- Kolom `is_admin` ditambahkan (migration `2026_07_23_000000_add_is_admin_to_users_table.php`),
  + di model User (fillable & cast boolean). Guard admin di `ProductController@create/store`.
- Halaman `resources/views/orders/index.blade.php` (Riwayat Order) — link tx ke
  `sepolia.etherscan.io`, tombol Konfirmasi Terima & Refund. Route `GET /orders`.
- `OrderController@store` diperketat: validasi + cegah duplikat tx_hash/order_id.
- Register diperbaiki: validasi `phone` (string, regex angka, max 15 digit) supaya
  tidak lagi error "Numeric value out of range". Pesan error tampil per-field (`@error`).

---

## 4. MASALAH YANG BELUM SELESAI (prioritas)

### A. `/products` mengembalikan 404 (BLOCKER)
Navigasi ke `http://127.0.0.1:8000/products` menghasilkan **"Not Found"** dari
**PHP built-in server** (bukan halaman 404 Laravel) — artinya request tidak
diteruskan ke Laravel. Route `/products` SUDAH ADA dan benar di `routes/web.php`.
Sudah dicoba `route:clear`, `config:clear`, `migrate` — masih 404.

Yang perlu dicek Claude Code:
1. Pastikan `php artisan serve` dijalankan **dari dalam folder `C:\Web Design\crypto-shop`**
   (bukan folder lain / bukan folder `public`).
2. Cek `php artisan route:list` — apakah `/products` benar terdaftar?
3. Coba `php artisan optimize:clear` (bersihkan semua cache sekaligus).
4. Cek apakah folder fisik `public/products/` (berisi gambar) mengganggu routing
   built-in server. Kalau ya, pertimbangkan rename folder gambar (mis. `public/product_images/`)
   dan sesuaikan path upload di `ProductController@store` + `<img src>` di view.
5. Cek `bootstrap/cache/routes-*.php` — hapus kalau ada route cache basi.

### B. PaymentGateway belum deploy
Setelah A beres: deploy `PaymentGateway.sol` di Ethereum Sepolia (Remix),
lalu isi `PAYMENT_GATEWAY_ADDRESS` di `resources/views/layouts/app.blade.php`
(baris konfigurasi, masih `"0xISI_ALAMAT_PAYMENT_GATEWAY"`).

### C. Belum diuji end-to-end
Setelah A & B: bagikan TLKM ke wallet pembeli uji (fungsi `mint`), lalu tes
approve → bayar → order tersimpan → tampil di /orders → konfirmasi → dana lepas.

### D. (Opsional) Kolom `phone` masih integer di DB
Validasi sudah mencegah error, tapi kalau mau simpan angka 0 di depan nomor HP,
ubah kolom `phone` ke VARCHAR via migration baru.

---

## 5. Akun uji
- Admin: `admin@gmail.com` / password `12341234` (sudah di-set `is_admin=true`).

---

## 6. PROMPT SIAP PAKAI untuk Claude Code

Salin salah satu prompt di bawah ke Claude Code (jalankan dari folder project).

### Prompt utama (mulai dari sini):
```
Ini project Laravel e-commerce crypto. Baca HANDOFF-CLAUDE-CODE.md di root untuk
konteks lengkap. Masalah utama sekarang: GET /products mengembalikan 404 dari PHP
built-in server (bukan 404 Laravel), padahal route /products sudah ada di
routes/web.php dan sudah dijalankan route:clear/config:clear/migrate.

Tolong diagnosa dan perbaiki 404-nya. Mulai dengan menjalankan `php artisan route:list`
dan `php artisan optimize:clear`, pastikan server jalan dari folder project yang benar,
dan cek apakah folder fisik public/products/ mengganggu routing. Jalankan perintah
artisan yang perlu, lalu konfirmasi /products sudah bisa diakses.
```

### Prompt lanjutan (setelah 404 beres):
```
404 /products sudah beres. Sekarang bantu saya:
1. Login sebagai admin@gmail.com dan buat 1 produk uji (harga dalam TLKM).
2. Pastikan tombol "+ Add Product" hanya muncul untuk admin.
3. Deploy PaymentGateway.sol ke Ethereum Sepolia belum dilakukan — ingatkan saya
   isi PAYMENT_GATEWAY_ADDRESS di resources/views/layouts/app.blade.php setelah deploy.
4. Setelah alamat diisi, telusuri flow pembelian di products/show.blade.php dan
   pastikan approve → payProduct → POST /order/store konsisten (desimal 18, chainId 11155111).
```

### Prompt untuk uji end-to-end:
```
Semua alamat kontrak sudah terisi. Bantu saya uji end-to-end pembelian:
verifikasi approve token, pembayaran ke escrow, order tersimpan ke DB, tampil di
/orders dengan link ke sepolia.etherscan.io, lalu confirmReceived melepas dana ke
penjual. Cek juga tidak ada mismatch desimal atau alamat.
```

---

## 7. File penting
- `routes/web.php` — semua route
- `app/Http/Controllers/` — AuthController, ProductController, OrderController, CryptoController
- `resources/views/layouts/app.blade.php` — konfigurasi web3 + fungsi JS pembayaran
- `resources/views/products/show.blade.php` — flow beli
- `resources/views/orders/index.blade.php` — riwayat / transparansi
- `contracts/` — smart contract + panduan deploy
- `.env` — DB `crypto`, `CMC_API_KEY` (dashboard harga crypto)

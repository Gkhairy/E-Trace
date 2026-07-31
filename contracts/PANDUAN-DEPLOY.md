# Panduan Deploy — TLKM Token & PaymentGateway (Arbitrum Sepolia)

Panduan langkah demi langkah untuk pemula. Semua dilakukan di **Remix** dan **MetaMask**.
Jaringan yang dipakai: **Arbitrum Sepolia** (testnet, chainId `421614`).

---

## 0. Siapkan MetaMask untuk Arbitrum Sepolia

Kalau jaringannya belum ada di MetaMask, tambahkan manual (Settings → Networks → Add network):

- **Network name:** Arbitrum Sepolia
- **RPC URL:** `https://sepolia-rollup.arbitrum.io/rpc`
- **Chain ID:** `421614`
- **Currency symbol:** ETH
- **Block explorer:** `https://sepolia.arbiscan.io`

> Frontend kamu sekarang juga sudah bisa menambahkan jaringan ini otomatis saat pertama connect.

---

## 1. Dapatkan gas ETH testnet (gratis)

Deploy kontrak butuh sedikit ETH testnet untuk bayar gas. Ambil dari salah satu faucet ini
(pakai wallet address MetaMask kamu):

- Alchemy — https://www.alchemy.com/faucets/arbitrum-sepolia
- QuickNode — https://faucet.quicknode.com/arbitrum/sepolia
- LearnWeb3 (0.01 ETH/hari) — https://learnweb3.io/faucets/arbitrum_sepolia/
- Chainlink — https://faucets.chain.link/arbitrum-sepolia

> Beberapa faucet minta wallet punya sedikit saldo di Ethereum mainnet dulu (anti-bot).
> Kalau satu faucet menolak, coba yang lain. Cukup 0.01–0.05 ETH sudah lebih dari cukup.

---

## 2. Deploy Token TLKM

1. Buka https://remix.ethereum.org
2. Buat file baru `TLKMToken.sol`, tempel isi dari file **`contracts/TLKMToken.sol`**.
3. Tab **Solidity Compiler**: pilih compiler **0.8.20** (atau lebih baru), klik **Compile**.
4. Tab **Deploy & Run Transactions**:
   - **Environment** → **Injected Provider - MetaMask** (pastikan MetaMask di Arbitrum Sepolia).
   - Pilih kontrak **TLKMToken**.
   - Di sebelah tombol **Deploy** ada input `initialSupplyWholeTokens`. Isi misal `1000000`
     (artinya mint 1.000.000 TLKM ke wallet kamu).
   - Klik **Deploy**, konfirmasi di MetaMask.
5. Setelah sukses, di bagian **Deployed Contracts** salin **alamat kontraknya**.
   👉 Ini **`TLKM_ADDRESS`**. Catat.

**Tips:** untuk menguji pembelian, kirim sebagian TLKM ke wallet pembeli uji coba.
Pakai fungsi `mint(address, amount)` (khusus owner) atau `transfer` biasa dari Remix.

---

## 3. Deploy PaymentGateway

1. Buat file baru `PaymentGateway.sol`, tempel isi dari **`contracts/PaymentGateway.sol`**.
2. **Compile** (0.8.20+).
3. Deploy kontrak **PaymentGateway** (tidak perlu argumen konstruktor).
4. Salin **alamat kontraknya**.
   👉 Ini **`PAYMENT_GATEWAY_ADDRESS`**. Catat.

---

## 4. Masukkan alamat ke frontend

Buka `resources/views/layouts/app.blade.php`, cari bagian KONFIGURASI, isi dua baris ini:

```js
const TLKM_ADDRESS            = "0x....";  // dari langkah 2
const PAYMENT_GATEWAY_ADDRESS = "0x....";  // dari langkah 3
```

Jangan ubah `TOKEN_DECIMALS = 18` (harus sama dengan token).

---

## 5. Jadikan dirimu penjual (agar bisa listing produk)

Jalankan migrasi lalu set peran user jadi `seller` (peran = kolom `role`: `buyer`/`seller`/`supervisor`):

```bash
php artisan migrate

# tandai user sebagai penjual (ganti email sesuai akunmu)
php artisan tinker
>>> \App\Models\User::where('email','emailkamu@contoh.com')->update(['role'=>'seller']);
```

Setelah itu tombol **+ Add Product** akan muncul di halaman Produk.

---

## 6. Alur uji coba end-to-end

1. Login (email/password atau MetaMask).
2. Sebagai admin, buat produk (harga dalam **TLKM**).
3. Sebagai pembeli (wallet yang sudah punya TLKM), buka produk → **Buy Now**:
   - MetaMask minta **approve** dulu → konfirmasi.
   - Lalu **pembayaran** ke escrow → konfirmasi.
   - Order tersimpan, kamu diarahkan ke **Riwayat Order**.
4. Di **Riwayat Order**, klik tx hash → terbuka di **Arbiscan Sepolia** (bukti transparansi).
5. Kalau barang sudah diterima → **Konfirmasi Terima** → dana lepas ke penjual.
   Kalau penjual tak kirim, setelah 3 hari → **Refund**.

---

## Ringkasan variabel penting

| Nama | Dari mana | Dipakai di |
|---|---|---|
| `TLKM_ADDRESS` | Deploy TLKMToken (langkah 2) | `layouts/app.blade.php` |
| `PAYMENT_GATEWAY_ADDRESS` | Deploy PaymentGateway (langkah 3) | `layouts/app.blade.php` |
| `TOKEN_DECIMALS` | Selalu `18` | `layouts/app.blade.php` |
| Jaringan | Arbitrum Sepolia (`421614`) | frontend + MetaMask |

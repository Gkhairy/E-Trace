# Panduan Deploy — TLKM Token & PaymentGateway (BNB Smart Chain Testnet)

Panduan langkah demi langkah untuk pemula. Semua dilakukan di **Remix** dan **MetaMask**.
Jaringan yang dipakai: **BNB Smart Chain Testnet** (testnet, chainId `97`).

---

## 0. Siapkan MetaMask untuk BSC Testnet

Kalau jaringannya belum ada di MetaMask, tambahkan manual (Settings → Networks → Add network):

- **Network name:** BNB Smart Chain Testnet
- **RPC URL:** `https://data-seed-prebsc-1-s1.bnbchain.org:8545/`
  (cadangan: `https://bsc-testnet.publicnode.com`)
- **Chain ID:** `97` (hex `0x61`)
- **Currency symbol:** tBNB
- **Block explorer:** `https://testnet.bscscan.com`

> Frontend E-Trace juga sudah bisa menambahkan jaringan ini otomatis saat pertama connect
> (fungsi `checkNetwork()` di `layouts/app.blade.php`, dibaca dari `config/chain.php`).

---

## 1. Dapatkan gas tBNB testnet (gratis)

Deploy kontrak butuh sedikit **tBNB** testnet untuk bayar gas. Ambil gratis dari faucet resmi
(pakai wallet address MetaMask kamu):

- **Faucet BNB Testnet** — https://testnet.bnbchain.org/faucet-smart

> tBNB testnet **gratis**, bukan uang nyata. Cukup 0.1–0.5 tBNB sudah lebih dari cukup untuk deploy.

---

## 2. Deploy Token TLKM

1. Buka https://remix.ethereum.org
2. Buat file baru `TLKMToken.sol`, tempel isi dari file **`contracts/TLKMToken.sol`**.
3. Tab **Solidity Compiler**: pilih compiler **0.8.20** (atau lebih baru), klik **Compile**.
4. Tab **Deploy & Run Transactions**:
   - **Environment** → **Injected Provider - MetaMask** (pastikan MetaMask di **BSC Testnet / chainId 97**).
   - Pilih kontrak **TLKMToken**.
   - Di sebelah tombol **Deploy** ada input `initialSupplyWholeTokens`. Isi misal `1000000`
     (artinya mint 1.000.000 TLKM ke wallet kamu).
   - Klik **Deploy**, konfirmasi di MetaMask.
5. Setelah sukses, di bagian **Deployed Contracts** salin **alamat kontraknya**.
   👉 Ini **`TLKM_ADDRESS`**. Catat.

**Tips:** untuk menguji pembelian, kirim sebagian TLKM ke wallet pembeli uji coba.
Pakai fungsi `mint(address, amount)` (khusus owner) atau `transfer` biasa dari Remix.

---

## 3. Deploy PaymentGateway (escrow)

1. Buat file baru `PaymentGatewayV3.sol`, tempel isi dari **`contracts/PaymentGatewayV3.sol`**.
2. **Compile** (0.8.20+).
3. Deploy kontrak **PaymentGatewayV3** (ikuti argumen konstruktor bila ada di file kontrak).
4. Salin **alamat kontraknya**.
   👉 Ini **`PAYMENT_GATEWAY_ADDRESS`**. Catat.

*(Opsional)* Deploy juga **`DonationPool.sol`** untuk fitur donasi → **`DONATION_POOL_ADDRESS`**.

---

## 4. Masukkan alamat ke `.env` (BUKAN ke blade)

Alamat kontrak kini dibaca dari **`config/chain.php`** yang mengambil nilai dari `.env`
(satu sumber kebenaran). Buka `.env`, isi:

```env
CHAIN_ID=97
CHAIN_RPC_URL=https://data-seed-prebsc-1-s1.bnbchain.org:8545/
CHAIN_EXPLORER_URL=https://testnet.bscscan.com
CHAIN_NAME="BNB Smart Chain Testnet"

TLKM_ADDRESS=0x....              # dari langkah 2
PAYMENT_GATEWAY_ADDRESS=0x....   # dari langkah 3
DONATION_POOL_ADDRESS=0x....     # dari langkah 3 (opsional)
```

Lalu bersihkan cache config agar nilai baru terbaca:

```bash
php artisan config:clear
```

`TOKEN_DECIMALS` tetap **18** (harus sama dengan token) — sudah di-hardcode di frontend.

---

## 5. Jadikan dirimu penjual (agar bisa listing produk)

Jalankan migrasi lalu set peran user jadi `seller` (kolom `role`: `buyer`/`seller`/`supervisor`):

```bash
php artisan migrate

# tandai user sebagai penjual (ganti email sesuai akunmu)
php artisan tinker
>>> \App\Models\User::where('email','emailkamu@contoh.com')->update(['role'=>'seller']);
```

Setelah itu tombol **+ Tambah Produk** akan muncul di halaman Produk.

---

## 6. Alur uji coba end-to-end

1. Login (email/password + PIN, atau MetaMask di **BSC Testnet**).
2. Sebagai penjual, buat produk (harga dalam **TLKM**).
3. Sebagai pembeli (wallet yang sudah punya TLKM), buka produk → **Beli Langsung**:
   - MetaMask minta **approve** dulu → konfirmasi.
   - Lalu **pembayaran** ke escrow → konfirmasi.
   - Order tersimpan, kamu diarahkan ke **Riwayat Order**.
4. Di **Riwayat Order**, klik tx hash → terbuka di **BscScan Testnet** (bukti transparansi).
5. Kalau barang sudah diterima → **Konfirmasi Terima** → dana lepas ke penjual.
   Kalau penjual tak kirim → **Refund**.

---

## Ringkasan variabel penting

| Nama | Dari mana | Dipakai di |
|---|---|---|
| `TLKM_ADDRESS` | Deploy TLKMToken (langkah 2) | `.env` → `config/chain.php` |
| `PAYMENT_GATEWAY_ADDRESS` | Deploy PaymentGatewayV3 (langkah 3) | `.env` → `config/chain.php` |
| `DONATION_POOL_ADDRESS` | Deploy DonationPool (opsional) | `.env` → `config/chain.php` |
| `CHAIN_ID` | `97` (BSC Testnet) | `.env` → `config/chain.php` |
| `CHAIN_EXPLORER_URL` | `https://testnet.bscscan.com` | semua tautan explorer |
| `TOKEN_DECIMALS` | Selalu `18` | `layouts/app.blade.php` |
| Jaringan | BNB Smart Chain Testnet (`97`) | frontend + MetaMask |

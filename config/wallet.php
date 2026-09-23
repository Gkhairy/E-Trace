<?php

// Rahasia wallet custodial & kunci platform.
//
// WAJIB lewat config, bukan env() di kode aplikasi: setelah `php artisan config:cache`
// (dijalankan entrypoint produksi), env() di luar folder config mengembalikan NULL.
// Di php-fpm (clear_env) variabel lingkungan proses pun tidak terlihat, jadi
// satu-satunya jalur yang andal adalah nilai yang dibekukan ke cache config.
return [
    // Kunci turunan enkripsi private key (PBKDF2 bersama PIN + salt per-wallet).
    // JANGAN diubah setelah ada wallet terbuat.
    'enc_secret' => env('WALLET_ENC_SECRET'),

    // Wallet platform yang mengisi gas tBNB ke wallet baru/komunitas.
    'gas_private_key' => env('PLATFORM_GAS_PRIVATE_KEY'),
    'gas_drip_amount' => env('GAS_DRIP_AMOUNT', '0.01'),
];

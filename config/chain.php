<?php

return [
    // RPC untuk verifikasi on-chain sisi server (bisa diganti Alchemy/Infura di .env).
    'rpc_url'  => env('SEPOLIA_RPC_URL', 'https://ethereum-sepolia-rpc.publicnode.com'),
    'chain_id' => (int) env('CHAIN_ID', 11155111),

    // Alamat kontrak — HARUS sama dengan yang dipakai frontend (layouts/app.blade.php).
    'gateway'  => env('PAYMENT_GATEWAY_ADDRESS', '0x0D6F824F6734B6369EdeBbfD6db37f965fa55f26'),
    'tlkm'     => env('TLKM_ADDRESS', '0xFbaa7F02bE3f151920D036cA4Eed2Fb1Ca3e0aEB'),

    // Konfirmasi: 1 = terdeteksi, >= paid_confirmations dianggap 'paid',
    // >= finalized_confirmations dianggap final (catatan audit).
    'paid_confirmations'      => (int) env('PAID_CONFIRMATIONS', 6),
    'finalized_confirmations' => (int) env('FINALIZED_CONFIRMATIONS', 12),
];

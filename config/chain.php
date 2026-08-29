<?php

return [
    // RPC untuk verifikasi on-chain sisi server (bisa diganti Alchemy/Infura di .env).
    'rpc_url'  => env('SEPOLIA_RPC_URL', 'https://ethereum-sepolia-rpc.publicnode.com'),
    'chain_id' => (int) env('CHAIN_ID', 11155111),

    // Alamat kontrak — HARUS sama dengan yang dipakai frontend (layouts/app.blade.php).
    'gateway'  => env('PAYMENT_GATEWAY_ADDRESS', '0x0D6F824F6734B6369EdeBbfD6db37f965fa55f26'),
    'tlkm'     => env('TLKM_ADDRESS', '0xFbaa7F02bE3f151920D036cA4Eed2Fb1Ca3e0aEB'),

    // Kotak donasi (DonationPool.sol) — isi setelah deploy di Remix. Placeholder =
    // fitur donasi nonaktif di UI sampai alamat asli dipasang.
    'donation_pool' => env('DONATION_POOL_ADDRESS', '0x0000000000000000000000000000000000000000'),

    // Konfirmasi: 1 = terdeteksi, >= paid_confirmations dianggap 'paid',
    // >= finalized_confirmations dianggap final (catatan audit).
    // H5: di TESTNET cukup 1-2 konfirmasi agar UX tidak lama (bisa dinaikkan untuk mainnet).
    'paid_confirmations'      => (int) env('PAID_CONFIRMATIONS', 1),
    'finalized_confirmations' => (int) env('FINALIZED_CONFIRMATIONS', 3),

    // ============ BIAYA & PAJAK (sisi PENJUAL — tidak ditampilkan ke pembeli) ============
    // H8: fee platform (basis poin, 100 = 1%) dipotong dari penjual saat dana dilepas.
    'platform_fee_bps' => (int) env('PLATFORM_FEE_BPS', 100),
    // H9: PPN/VAT (basis poin, 1100 = 11%) dihitung atas FEE platform (biaya jasa platform).
    'vat_bps'          => (int) env('VAT_BPS', 1100),

    // Kurs tampilan: Rp per 1 TLKM (mis. Rp1.000 = 1 TLKM). Ongkir dikonversi ke TLKM.
    'rp_per_tlkm' => (int) env('RP_PER_TLKM', 1000),

    // ============ ONGKOS KIRIM (H7) — configurable, produk fisik ============
    // Utama: J&T Tariff API (butuh kredensial). Fallback: jarak garis lurus
    // (haversine) antar kota dari koordinat bawaan (gratis, tanpa API).
    'shipping' => [
        'base_fee'      => (int) env('SHIP_BASE_FEE', 20000),   // untuk 10 km pertama (Rupiah)
        'base_km'       => (int) env('SHIP_BASE_KM', 10),
        'step_km'       => (int) env('SHIP_STEP_KM', 5),        // tiap tambahan 5 km
        'step_fee'      => (int) env('SHIP_STEP_FEE', 5000),    // +Rp5.000 per step
        'fallback_fee'  => (int) env('SHIP_FALLBACK_FEE', 30000), // kota tak dikenal
        'weight_kg'     => (float) env('SHIP_WEIGHT_KG', 1),      // berat default per order

        // J&T Express Tariff API. Aktif hanya jika JNT_API_KEY diisi.
        // Perlu registrasi di developer.jet.co.id + proses mapping area code.
        'jnt' => [
            'enabled' => (bool) env('JNT_API_KEY', false),
            'url'     => env('JNT_TARIFF_URL', 'https://developer.jet.co.id/api/tariff'),
            'api_key' => env('JNT_API_KEY', ''),
            'sender'  => env('JNT_SENDER_CODE', ''), // area code asal (dari mapping J&T)
        ],
    ],
];

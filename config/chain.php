<?php

return [
    // ============ JARINGAN — BNB Smart Chain Testnet (chainId 97) ============
    // SUMBER KEBENARAN TUNGGAL jaringan. Frontend & backend membaca dari sini.
    // RPC untuk verifikasi on-chain sisi server (cadangan: https://bsc-testnet.publicnode.com).
    'rpc_url'      => env('CHAIN_RPC_URL', 'https://data-seed-prebsc-1-s1.bnbchain.org:8545/'),
    'chain_id'     => (int) env('CHAIN_ID', 97),
    'name'         => env('CHAIN_NAME', 'BNB Smart Chain Testnet'),
    'explorer_url' => rtrim(env('CHAIN_EXPLORER_URL', 'https://testnet.bscscan.com'), '/'),

    // Alamat kontrak — HARUS sama dengan yang dipakai frontend (layouts/app.blade.php).
    // Diisi lewat .env setelah redeploy ke BSC Testnet. Default = alamat nol (fitur
    // nonaktif sampai alamat asli dipasang) agar tak menunjuk kontrak jaringan lain.
    'gateway'  => env('PAYMENT_GATEWAY_ADDRESS', '0x0000000000000000000000000000000000000000'),
    'tlkm'     => env('TLKM_ADDRESS', '0x0000000000000000000000000000000000000000'),

    // Kotak donasi (DonationPool.sol) — isi setelah deploy. Placeholder = donasi
    // nonaktif di UI sampai alamat asli dipasang.
    'donation_pool' => env('DONATION_POOL_ADDRESS', '0x0000000000000000000000000000000000000000'),

    // Dompet komunitas on-chain (opsional; alur custodial saat ini tak memakainya).
    'community_wallet' => env('COMMUNITY_WALLET_ADDRESS', '0x0000000000000000000000000000000000000000'),

    // Paylater (kredit berjaminan on-chain DENGAN BUNGA, DEMO testnet). Isi setelah deploy.
    'paylater_address' => env('PAYLATER_ADDRESS'),
    // Rate DEMO untuk estimasi limit di UI: TLKM per 1 tBNB (samakan dgn `rate` kontrak / 1e18).
    'paylater_rate_tlkm_per_bnb' => (int) env('PAYLATER_RATE_TLKM_PER_BNB', 1000000),
    // Bunga flat per pinjaman (basis poin) untuk estimasi UI (samakan dgn `interestBps` kontrak).
    'paylater_interest_bps' => (int) env('PAYLATER_INTEREST_BPS', 300),

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
    // Utama: RajaOngkir (Komerce API v1). Fallback: jarak garis lurus (haversine)
    // antar kota dari koordinat bawaan (gratis, tanpa API).
    'shipping' => [
        'base_fee'      => (int) env('SHIP_BASE_FEE', 20000),   // untuk 10 km pertama (Rupiah)
        'base_km'       => (int) env('SHIP_BASE_KM', 10),
        'step_km'       => (int) env('SHIP_STEP_KM', 5),        // tiap tambahan 5 km
        'step_fee'      => (int) env('SHIP_STEP_FEE', 5000),    // +Rp5.000 per step
        'fallback_fee'  => (int) env('SHIP_FALLBACK_FEE', 30000), // kota tak dikenal
        'weight_kg'     => (float) env('SHIP_WEIGHT_KG', 1),      // berat default per order

        // RajaOngkir (Komerce). Aktif hanya jika RAJAONGKIR_API_KEY diisi (.env).
        'rajaongkir' => [
            'key'      => env('RAJAONGKIR_API_KEY', ''),
            'base'     => env('RAJAONGKIR_BASE', 'https://rajaongkir.komerce.id/api/v1'),
            'couriers' => env('RAJAONGKIR_COURIERS', 'jne:sicepat:jnt:ide:pos:tiki'),
        ],
    ],
];

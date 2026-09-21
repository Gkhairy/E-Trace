<?php

return [
    // ============ JARINGAN — BNB Smart Chain Testnet (chainId 97) ============
    // SUMBER KEBENARAN TUNGGAL jaringan. Frontend & backend membaca dari sini.
    // RPC untuk verifikasi on-chain sisi server (cadangan: https://bsc-testnet.publicnode.com).
    'rpc_url'      => env('CHAIN_RPC_URL', 'https://data-seed-prebsc-1-s1.bnbchain.org:8545/'),
    'chain_id'     => (int) env('CHAIN_ID', 97),
    'name'         => env('CHAIN_NAME', 'BNB Smart Chain Testnet'),
    'explorer_url' => rtrim(env('CHAIN_EXPLORER_URL', 'https://testnet.bscscan.com'), '/'),

    // Feed "Transfer TLKM" di Explorer — baca event Transfer via eth_getLogs.
    // RPC utama (data-seed) memblokir getLogs, jadi pakai RPC khusus yang mengizinkannya
    // (publicnode). Tanpa API key. Lookback dibatasi karena getLogs peka rentang.
    'logs_rpc_url'              => env('CHAIN_LOGS_RPC_URL', 'https://bsc-testnet-rpc.publicnode.com'),
    'transfers_lookback_blocks' => (int) env('EXPLORER_TRANSFERS_LOOKBACK', 5000),

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
        // Wallet penerima ongkir (custodial platform/logistik). Ongkir dibayar sebagai
        // transfer TLKM TERPISAH dari escrow produk. Kosong = pakai wallet pool asuransi
        // (sama-sama custodial platform); bila keduanya kosong, ongkir tetap estimasi non-on-chain.
        'fee_wallet'    => env('SHIPPING_FEE_WALLET'),
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

    // ============ AI AUTO-SETTLEMENT + GARANSI TEPAT WAKTU (DEMO testnet) ============
    // Fitur asuransi pengiriman parametrik + penyelesaian escrow otomatis oleh AI.
    // Aman-nonaktif: bila insurance.enabled=false ATAU pool_wallet kosong, seluruh
    // alur asuransi mati; bila arbiter_key kosong, auto-settlement mati (order 'held').
    'insurance' => [
        'enabled'               => (bool) env('INSURANCE_ENABLED', false),
        'premium_tlkm'          => (float) env('INSURANCE_PREMIUM_TLKM', 2),
        // Wallet custodial platform pembayar klaim (menampung premi & membayar payout).
        'pool_wallet'           => env('INSURANCE_POOL_ADDRESS'),
        // Private key wallet pool (server menandatangani payout via ChainSigner).
        'pool_key'              => env('INSURANCE_POOL_PRIVATE_KEY'),
        'eta_buffer_days'       => (int) env('INSURANCE_ETA_BUFFER_DAYS', 3),   // promised = ETA + buffer
        'grace_days'            => (int) env('INSURANCE_GRACE_DAYS', 2),        // telat hanya jika now > promised + grace
        'payout'                => env('INSURANCE_PAYOUT', 'shipping_refund'),   // kompensasi = refund ongkir
        'payout_cap_tlkm'       => (float) env('INSURANCE_PAYOUT_CAP_TLKM', 30), // batas per klaim
        'daily_payout_cap_tlkm' => (float) env('INSURANCE_DAILY_PAYOUT_CAP_TLKM', 500), // circuit breaker harian
    ],
    'ai' => [
        'min_confidence'       => (float) env('AI_MIN_CONFIDENCE', 0.8),        // ambang auto-eksekusi
        'max_auto_amount_tlkm' => (float) env('AI_MAX_AUTO_AMOUNT_TLKM', 1000), // di atas ini → eskalasi pengawas
    ],

    // Aturan penyelesaian DETERMINISTIK (tanpa AI) yang dijalankan keeper.
    'settlement' => [
        // Auto-refund bila penjual TAK mengirim dalam N hari (soal penjual mengirim,
        // jadi TIDAK bergantung jarak). 0 = matikan aturan ini.
        'ship_deadline_days'  => (int) env('SETTLE_SHIP_DEADLINE_DAYS', 3),
        // Auto-selesai (rilis ke penjual) bila barang sudah DITERIMA tapi pembeli tak
        // konfirmasi dalam M hari. 0 = matikan.
        'auto_complete_days'  => (int) env('SETTLE_AUTO_COMPLETE_DAYS', 3),
        // Tujuan JAUH (eta_days >= nilai ini) TIDAK di-auto-selesaikan — beri pembeli
        // remote waktu lebih; biar dikonfirmasi manual / lewat pengawas.
        'far_eta_days'        => (int) env('SETTLE_FAR_ETA_DAYS', 10),
    ],

    // Kunci ARBITER (pengawas otomatis) untuk AI auto-settlement lewat ChainSigner.
    // Wallet ini WAJIB sudah dipasang via PaymentGatewayV3.setArbiter(). Kosong =
    // auto-settlement mati (keputusan AI tetap dicatat, order ditandai 'held').
    'arbiter_key' => env('KEEPER_ARBITER_PRIVATE_KEY'),
];

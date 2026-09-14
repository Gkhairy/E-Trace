<?php

return [
    // Checkout — checkbox garansi
    'checkout_title' => 'Garansi Tepat Waktu',
    'checkout_desc'  => 'kompensasi ongkir bila paket telat karena penjual/kurir.',
    'eta_label'      => 'Estimasi tiba',
    'terms_short'    => 'Klaim berlaku bila telat ≥ :grace hari & penyebabnya penjual/kurir (bukan alamat salah / force majeure). Maks :cap TLKM.',
    'demo_note'      => 'Demo testnet — parameter premi/kompensasi contoh; pool disubsidi platform.',

    // Status penyelesaian (settlement)
    'settlement' => [
        'pending'  => 'Menunggu penilaian AI',
        'released' => 'Diselesaikan otomatis oleh AI',
        'refunded' => 'Dana dikembalikan otomatis oleh AI',
        'held'     => 'Ditahan — ditinjau pengawas',
    ],

    // Status asuransi
    'status' => [
        'none'     => 'Tanpa garansi',
        'active'   => 'Garansi aktif',
        'paid'     => 'Klaim garansi dibayar',
        'rejected' => 'Klaim tidak berlaku',
    ],

    // Penyebab telat (dari AI)
    'cause' => [
        'seller_courier' => 'penjual/kurir',
        'buyer'          => 'sisi pembeli',
        'force_majeure'  => 'force majeure',
        'unknown'        => 'tidak diketahui',
    ],

    'ai_badge'     => 'Keputusan AI',
    'reason_label' => 'Alasan',
    'payout_label' => 'Kompensasi',
    'promised'     => 'Estimasi tiba',
    'view_tx'      => 'Lihat transaksi',
    'demo_sim'     => 'data tracking simulasi (demo)',
];

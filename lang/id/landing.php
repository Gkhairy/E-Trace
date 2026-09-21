<?php

return [
    'nav' => [
        'catalog' => 'Lihat Katalog',
        'login'   => 'Masuk Toko',
        'how'     => 'Cara Kerja',
    ],

    'hero' => [
        'badge'     => 'Web3, dipakai di kehidupan nyata',
        'title'     => 'Belanja, pinjam, dan berdonasi —',
        'title_hl'  => 'semuanya on-chain.',
        'subtitle'  => 'Marketplace dengan pembayaran dijaga smart-contract escrow. Dana baru lepas ke penjual setelah kamu konfirmasi barang diterima, dan setiap transaksi bisa diverifikasi siapa saja di blockchain.',
        'cta'       => 'Masuk Toko',
        'cta2'      => 'Lihat Cara Kerja',
        'scroll'    => 'SCROLL',
    ],

    'value' => [
        'title' => 'Satu aplikasi untuk transaksi digital yang transparan, aman, dan berkelanjutan.',
    ],

    'how' => [
        'eyebrow' => 'Cara Kerja',
        'title'   => 'Aman tanpa harus saling percaya.',
        'subtitle'=> 'Bukan platform yang memegang danamu, tapi kode. Beginilah satu pembelian berjalan:',
        'steps'   => [
            ['t' => 'Bayar dengan TLKM', 'd' => 'Pembeli membayar memakai token TLKM langsung dari wallet.'],
            ['t' => 'Ditahan escrow',    'd' => 'Dana masuk ke smart contract, ditahan — belum ke penjual.'],
            ['t' => 'Barang dikirim',    'd' => 'Penjual mengirim; status pengiriman terpantau.'],
            ['t' => 'Konfirmasi terima', 'd' => 'Pembeli menekan "Konfirmasi Terima" saat barang sampai.'],
            ['t' => 'Dana lepas',        'd' => 'Baru saat itu dana diteruskan ke penjual. Gagal kirim? Dana kembali.'],
        ],
        'note'    => 'Ada masalah (mis. salah kirim)? Ajukan sengketa dengan bukti — diputus pengawas, bukan refund sepihak.',
    ],

    'features' => [
        'eyebrow'  => 'Fitur Lengkap',
        'title'    => 'Semua yang kamu butuhkan, di atas satu infrastruktur on-chain.',
        'subtitle' => 'Dari belanja sampai kredit dan donasi — dijelaskan dengan bahasa sederhana.',
        'items' => [
            ['t' => 'Escrow Trustless',        'd' => 'Dana ditahan smart contract, lepas hanya saat pembeli konfirmasi terima.'],
            ['t' => 'Keranjang Multi-Penjual', 'd' => 'Satu keranjang banyak penjual; escrow dipisah per item.'],
            ['t' => 'Token TLKM',              'd' => 'Alat bayar BEP-20 di jaringan BNB Smart Chain Testnet.'],
            ['t' => 'Wallet + PIN',            'd' => 'Daftar pakai email/HP tanpa seed phrase, atau MetaMask. Bayar cukup PIN.'],
            ['t' => 'Paylater — Pinjam & Danai','d' => 'Belanja bayar nanti, atau danai pool likuiditas & panen bagi hasil (fleksibel/30/90 hari).'],
            ['t' => 'AI Auto-Settlement',      'd' => 'Tak dikirim 3 hari → dana otomatis kembali; diterima & lupa konfirmasi → otomatis selesai.'],
            ['t' => 'Garansi Tepat Waktu',     'd' => 'Asuransi ongkir: telat karena penjual/kurir → ongkir diganti dari pool. Estimasi dari jarak.'],
            ['t' => 'Explorer Transparansi',   'd' => 'Lihat transaksi + transfer TLKM on-chain, toko teratas, dan entitas terverifikasi.'],
            ['t' => 'Donasi & Dompet Komunitas','d' => 'Donasi tercatat on-chain (0% fee); dana komunitas butuh persetujuan multisig.'],
            ['t' => 'EVA — Asisten AI',        'd' => 'Bantu pemakaian aplikasi & jelaskan blockchain untuk orang awam.'],
            ['t' => 'Keamanan Berlapis',       'd' => 'OTP email, 2FA, gerbang PIN, dan proteksi anti-bot.'],
            ['t' => 'Laporan Penjual',         'd' => 'Rekap penjualan otomatis + HPP, ekspor Excel/PDF.'],
            ['t' => 'Peran & Dwibahasa',       'd' => 'Pembeli, penjual, pengawas — tersedia Bahasa Indonesia & Inggris.'],
        ],
    ],

    'sustain' => [
        'eyebrow'  => 'Berkelanjutan',
        'title'    => 'Dibangun untuk bertahan — ekonomi, energi, dan dampak sosial.',
        'subtitle' => 'Bukan sekadar transaksi, tapi infrastruktur blockchain yang menghidupi dirinya dan lingkungannya.',
        'items' => [
            ['t' => 'Ekonomi yang Mandiri',    'd' => 'Escrow, pool Paylater yang menghasilkan bagi hasil, dan pool asuransi membentuk lingkaran ekonomi yang menopang dirinya sendiri.'],
            ['t' => 'Hemat Energi',            'd' => 'Berjalan di BNB Smart Chain (Proof-of-Stake) — jejak energi jauh lebih kecil dibanding blockchain Proof-of-Work.'],
            ['t' => 'Dampak Sosial',           'd' => 'Transparansi menekan penyelewengan, donasi tersalur utuh, dan inklusi keuangan terbuka untuk lebih banyak orang.'],
        ],
        'asset_note' => 'Aset 3D',
    ],

    'transparency' => [
        'title' => 'Jangan percaya kami — buktikan sendiri.',
        'desc'  => 'Setiap transaksi punya jejak on-chain. Buka Explorer untuk melihat volume, status escrow, dan transfer TLKM secara langsung.',
        'cta'   => 'Buka Explorer',
    ],

    'personas' => [
        'title' => 'Untuk siapa E-Trace?',
        'items' => [
            ['t' => 'Pembeli', 'd' => 'Belanja tenang — dana aman di escrow sampai barang benar-benar diterima.'],
            ['t' => 'Penjual', 'd' => 'Jangkauan lebih luas, pembayaran pasti, laporan otomatis, fee ringan 1%.'],
            ['t' => 'Komunitas & Donatur', 'd' => 'Kumpulkan & salurkan dana secara transparan, tercatat on-chain, tanpa fee.'],
        ],
    ],

    'cta' => [
        'title'      => 'Siap mencoba marketplace yang benar-benar transparan?',
        'desc'       => 'Masuk, hubungkan wallet, dan rasakan transaksi on-chain yang aman.',
        'button'     => 'Mulai Sekarang',
        'disclaimer' => 'Berjalan di BNB Smart Chain Testnet dengan token uji coba — bukan uang sungguhan. Smart contract belum diaudit; ini prototipe untuk kompetisi/edukasi.',
    ],

    'footer' => [
        'tagline' => 'Marketplace on-chain yang transparan sepenuhnya.',
    ],
];

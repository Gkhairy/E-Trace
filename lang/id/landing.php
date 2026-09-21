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
        'eyebrow'  => 'Kemampuan',
        'title'    => 'Semua kebutuhanmu, dirangkum jadi empat pilar.',
        'subtitle' => 'Satu aplikasi, empat pilar yang saling menopang — tanpa bikin bingung.',
        'groups' => [
            [
                't' => 'Belanja Aman',
                'intro' => 'Transaksi tanpa harus saling percaya.',
                'items' => [
                    ['t' => 'Escrow trustless',        'd' => 'Dana ditahan smart contract, lepas saat konfirmasi terima.'],
                    ['t' => 'Keranjang multi-penjual', 'd' => 'Banyak penjual dalam satu keranjang, escrow terpisah per item.'],
                    ['t' => 'Verifikasi on-chain',     'd' => 'Smart contract jadi sumber kebenaran, bukan data browser.'],
                ],
            ],
            [
                't' => 'Keuangan On-Chain',
                'intro' => 'Bukan sekadar alat bayar.',
                'items' => [
                    ['t' => 'Wallet + PIN',             'd' => 'Daftar pakai email/HP tanpa seed phrase, bayar cukup PIN.'],
                    ['t' => 'Token TLKM',               'd' => 'Alat bayar BEP-20 di BNB Smart Chain Testnet.'],
                    ['t' => 'Paylater — Pinjam & Danai','d' => 'Belanja bayar nanti, atau danai pool & panen bagi hasil.'],
                ],
            ],
            [
                't' => 'Otomatis & Terlindungi',
                'intro' => 'Dijaga sistem, bukan sekadar janji.',
                'items' => [
                    ['t' => 'AI Auto-Settlement',   'd' => 'Tak dikirim 3 hari → refund; lupa konfirmasi → auto-selesai.'],
                    ['t' => 'Garansi Tepat Waktu',  'd' => 'Asuransi ongkir bila telat karena penjual/kurir.'],
                    ['t' => 'Keamanan berlapis',    'd' => 'OTP, 2FA, gerbang PIN, dan proteksi anti-bot.'],
                ],
            ],
            [
                't' => 'Transparan & Sosial',
                'intro' => 'Terbuka untuk siapa saja.',
                'items' => [
                    ['t' => 'Explorer transparansi',    'd' => 'Lihat transaksi & transfer TLKM langsung on-chain.'],
                    ['t' => 'Donasi & dompet komunitas','d' => 'Tercatat on-chain, 0% fee, dana komunitas multisig.'],
                    ['t' => 'EVA & laporan penjual',    'd' => 'Asisten AI + rekap penjualan otomatis (Excel/PDF).'],
                ],
            ],
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

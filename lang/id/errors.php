<?php

// Halaman error kustom (resources/views/errors). Teks menyebut masalahnya dan
// cara pulih, dalam bahasa produk — bukan kode teknis.
return [
    'home'   => 'Ke beranda',
    'back'   => 'Kembali',
    'retry'  => 'Coba lagi',
    'search' => 'Cari',
    'search_placeholder' => 'Cari produk atau toko…',
    'trace'  => 'Jejak permintaan',
    'explorer' => 'Buka Explorer',

    '404' => [
        'title' => 'Halaman ini tidak ada',
        'lead'  => 'Tautannya mungkin salah ketik, atau produknya sudah dihapus penjual. Coba cari lewat kotak di bawah.',
    ],
    '403' => [
        'title' => 'Kamu tidak punya akses ke sini',
        'lead'  => 'Halaman ini hanya untuk akun dengan peran tertentu. Kalau menurutmu ini keliru, masuk dengan akun yang benar.',
    ],
    '419' => [
        'title'  => 'Sesimu sudah kedaluwarsa',
        'lead'   => 'Halamannya terlalu lama terbuka, jadi formulirnya tidak bisa dikirim lagi demi keamanan. Buka ulang halamannya, lalu isi kembali.',
        'reopen' => 'Buka ulang halaman',
    ],
    '429' => [
        'title' => 'Terlalu banyak permintaan',
        'lead'  => 'Kamu mengirim permintaan lebih cepat dari batas yang diizinkan. Tunggu sebentar, lalu coba lagi.',
        'wait'  => 'Bisa dicoba lagi dalam :seconds detik.',
        'ready' => 'Sudah bisa dicoba lagi.',
    ],
    '500' => [
        'title'   => 'Ada yang rusak di sisi kami',
        'lead'    => 'Permintaanmu tidak bisa diproses karena kesalahan server. Ini bukan kesalahanmu.',
        'payment' => 'Sedang membayar? Jangan langsung bayar ulang. Kalau transaksinya sempat terkirim, ia tetap tercatat di blockchain meski halaman ini gagal — cek Pesanan atau Explorer dulu.',
        'orders'  => 'Cek pesanan',
    ],
    '503' => [
        'title'  => 'E-Trace sedang dalam pemeliharaan',
        'lead'   => 'Kami sedang memperbarui sistem dan akan segera kembali.',
        'escrow' => 'Dana yang ditahan escrow tetap aman: dana itu disimpan di smart contract, bukan di server ini.',
    ],
    '4xx' => [
        'title' => 'Permintaan tidak bisa diproses',
        'lead'  => 'Ada yang tidak beres dengan permintaan ini. Kembali ke halaman sebelumnya dan coba lagi.',
    ],
    '5xx' => [
        'title' => 'Server sedang bermasalah',
        'lead'  => 'Coba lagi dalam beberapa saat.',
    ],
];

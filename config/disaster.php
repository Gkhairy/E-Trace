<?php

// Radar Bencana AI: memantau bencana nyata di Indonesia dan membuka campaign donasi.
return [
    // Wallet Lembaga Donasi E-Trace (penerima dana campaign otomatis). Kosong = campaign
    // tidak dibuka otomatis; kejadian tetap masuk antrean pengawas.
    'recipient_wallet' => env('DISASTER_RECIPIENT_WALLET'),

    // Skor AI ≥ auto_min_severity dibuka otomatis (sumber mana pun, dibatasi per hari);
    // skor review_min_severity..69 menunggu persetujuan pengawas.
    'auto_min_severity'   => (int) env('DISASTER_AUTO_MIN_SEVERITY', 70),  // skor AI minimal (0-100)
    'review_min_severity' => (int) env('DISASTER_REVIEW_MIN_SEVERITY', 45), // di bawah ini ditolak
    'bmkg_scan_magnitude' => 5.0,                     // gempa lebih kecil tidak dinilai AI sama sekali
    'max_auto_per_day'    => (int) env('DISASTER_MAX_AUTO_PER_DAY', 3),
    'lookback_days'       => 7,                       // kejadian lebih lama diabaikan
    'campaign_days'       => 30,                      // masa buka campaign otomatis
    'max_ai_per_scan'     => 25,                      // batas panggilan AI per scan (scan sekali sehari)

    // Pencarian berita (Google News RSS, bahasa Indonesia, 1 hari terakhir).
    'news_query' => 'kebakaran OR banjir OR longsor OR "gempa bumi" OR erupsi OR "puting beliung" OR tsunami',
    // Judul berita yang mengandung kata ini jelas bukan kejadian bencana (apel, sosialisasi, ...).
    'news_skip_words' => ['apel', 'sosialisasi', 'simulasi', 'pelatihan', 'antisipasi', 'webinar', 'seminar', 'lomba', 'gladi', 'kesiapsiagaan', 'mitigasi', 'prakiraan', 'peringatan dini'],
];

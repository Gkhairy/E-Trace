<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Indexer on-chain: sinkron status order/escrow dari blockchain tiap menit.
// Aktif jika `php artisan schedule:work` berjalan (atau cron memanggil schedule:run).
Schedule::command('chain:index')->everyMinute()->withoutOverlapping();

// AI Auto-Settlement + klaim Garansi Tepat Waktu — jalan DI BELAKANG, cek HARIAN.
// Tenggat penyelesaian dihitung dalam hari, jadi cukup sekali sehari; ini juga menekan
// biaya token LLM (tak memanggil AI berulang untuk order yang sama). Pengawas bisa memicu
// manual kapan saja lewat tombol di /orders. Aman-nonaktif bila belum dikonfigurasi.
Schedule::command('settlement:keep')->daily()->withoutOverlapping();

// Indeks transfer TLKM on-chain ke DB supaya Explorer bisa menampilkan riwayat
// penuh per alamat (masuk dari siapa, keluar ke mana) tanpa membebani RPC saat
// halaman dibuka. Resumable lewat `indexer_cursors`.
Schedule::command('transfers:index')->everyMinute()->withoutOverlapping();

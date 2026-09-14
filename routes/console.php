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

// AI Auto-Settlement + klaim Garansi Tepat Waktu — nilai order escrow aktif tiap 5 menit.
// Memanggil LLM (biaya token) → tidak tiap menit. Aman-nonaktif bila belum dikonfigurasi.
Schedule::command('settlement:keep')->everyFiveMinutes()->withoutOverlapping();

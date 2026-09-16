<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan estimasi ETA (hari, berbasis jarak) saat checkout — dipakai keeper untuk
 * aturan "auto-selesai": tujuan JAUH (eta_days tinggi) tidak di-auto agar pembeli
 * remote punya waktu lebih (sesuai SLA jarak Garansi Tepat Waktu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('eta_days')->nullable()->after('promised_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('eta_days');
        });
    }
};

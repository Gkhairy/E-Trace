<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Email kontak toko untuk notifikasi order — dipakai kalau penjual
            // bukan user terdaftar (atau ingin email berbeda dari akun login).
            $table->string('contact_email')->nullable()->after('payout_wallet');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('contact_email');
        });
    }
};

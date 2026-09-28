<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notifikasi disimpan sebagai template + parameter, lalu diterjemahkan saat tampil,
        // supaya ikut bahasa antarmuka pembaca (bukan bahasa saat notifikasi dibuat).
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->json('params')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', fn (Blueprint $t) => $t->dropColumn('params'));
    }
};

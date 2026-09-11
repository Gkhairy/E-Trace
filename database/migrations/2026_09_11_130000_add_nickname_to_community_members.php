<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nickname PRIBADI untuk dompet komunitas: label yang hanya dilihat oleh anggota
 * yang menyetelnya (per-user, per-wallet). Tidak dibagikan ke anggota lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_members', function (Blueprint $table) {
            $table->string('nickname', 60)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('community_members', function (Blueprint $table) {
            $table->dropColumn('nickname');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nickname PRIBADI per anggota: setiap penatap (viewer) bisa memberi nama julukan
 * ke anggota lain di sebuah dompet komunitas, dan HANYA penatap itu yang melihatnya.
 * (Menggantikan pendekatan nickname-per-dompet sebelumnya.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Buang kolom nickname-per-dompet yang salah tafsir.
        if (Schema::hasColumn('community_members', 'nickname')) {
            Schema::table('community_members', function (Blueprint $table) {
                $table->dropColumn('nickname');
            });
        }

        Schema::create('community_member_nicknames', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('community_wallet_id');
            $table->unsignedBigInteger('viewer_id');       // yang memberi & melihat nickname
            $table->unsignedBigInteger('target_user_id');  // anggota yang diberi nickname
            $table->string('nickname', 60);
            $table->timestamps();
            $table->unique(['community_wallet_id', 'viewer_id', 'target_user_id'], 'cmn_unique');
            $table->index(['community_wallet_id', 'viewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_member_nicknames');
        Schema::table('community_members', function (Blueprint $table) {
            $table->string('nickname', 60)->nullable()->after('user_id');
        });
    }
};

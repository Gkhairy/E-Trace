<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Label entitas terverifikasi (ala Arkham) — hanya diisi pengawas.
        Schema::create('wallet_labels', function (Blueprint $table) {
            $table->id();
            $table->string('address')->unique();           // lowercase 0x...
            $table->string('label');                        // "US GOV", "Bank Indonesia", dll
            $table->string('category')->nullable();         // government|institution|exchange|individual
            $table->boolean('verified')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Nama publik (pseudonim) + kontrol tampil di explorer. Nama ASLI tetap privat.
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_name')->nullable()->after('name');
            $table->boolean('explorer_public')->default(true)->after('public_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_labels');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['public_name', 'explorer_public']);
        });
    }
};

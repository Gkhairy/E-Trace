<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Blind index (HMAC) untuk pencarian nomor telepon TANPA mendekripsi.
            // Kolom `phone` sendiri dienkripsi (cast 'encrypted' di model).
            $table->string('phone_hash', 64)->nullable()->index()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_hash');
        });
    }
};

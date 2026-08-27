<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_members', function (Blueprint $table) {
            // Mode B (multisig): apakah anggota ini termasuk penanda tangan wajib.
            // Default true agar dompet lama tetap berperilaku sama (semua anggota = signer).
            $table->boolean('is_signer')->default(true)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('community_members', function (Blueprint $table) {
            $table->dropColumn('is_signer');
        });
    }
};

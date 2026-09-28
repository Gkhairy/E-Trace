<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bonus TLKM uji coba untuk akun baru: sekali per akun, dicatat di sini.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tlkm_granted_at')->nullable()->after('gas_dripped_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('tlkm_granted_at'));
    }
};

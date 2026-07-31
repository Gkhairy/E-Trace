<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Konsolidasi peran ke satu sumber kebenaran: kolom `role`.
 * - `is_admin` (boolean warisan) sudah tidak dipakai otorisasi (semua gate pakai
 *   role via isSeller()/isSupervisor()) → dibuang biar tidak ada dua sumber kebenaran.
 * - `role` diubah jadi ENUM sejati agar hanya nilai valid (buyer/seller/supervisor)
 *   yang bisa tersimpan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Jaga-jaga: pastikan tidak ada role kosong/tak valid sebelum jadi enum.
        DB::table('users')
            ->whereNull('role')
            ->orWhereNotIn('role', ['buyer', 'seller', 'supervisor'])
            ->update(['role' => 'buyer']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['buyer', 'seller', 'supervisor'])->default('buyer')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('wallet_address');
        });

        // Pulihkan is_admin dari role (seller/supervisor dulunya admin).
        DB::table('users')
            ->whereIn('role', ['seller', 'supervisor'])
            ->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('buyer')->change();
        });
    }
};

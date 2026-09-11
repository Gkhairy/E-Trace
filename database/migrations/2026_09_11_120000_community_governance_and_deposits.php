<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tata-kelola dompet komunitas (Mode B/multisig):
 * - owner_id: pemilik yang boleh mengusulkan undang/kick & transfer kepemilikan
 *   (default = pembuat). Semua aksi tetap butuh persetujuan BULAT semua signer.
 * - proposals: tambah `type` (transfer|add_member|remove_member|transfer_ownership)
 *   + target_user_id + meta, agar satu alur usulan/approval dipakai semua aksi.
 * - community_deposits: mutasi siapa yang menyetor ke kas (lewat tombol Setor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_wallets', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable()->after('created_by');
        });
        // Backfill: pemilik awal = pembuat.
        DB::table('community_wallets')->update(['owner_id' => DB::raw('created_by')]);

        Schema::table('community_proposals', function (Blueprint $table) {
            $table->string('type', 20)->default('transfer')->after('proposer_id'); // transfer|add_member|remove_member|transfer_ownership
            $table->unsignedBigInteger('target_user_id')->nullable()->after('to_name'); // untuk add/remove/transfer_ownership
            $table->json('meta')->nullable()->after('note'); // mis. {"as_signer":true} untuk add_member
        });

        Schema::create('community_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('community_wallet_id');
            $table->unsignedBigInteger('user_id')->nullable();   // penyetor (null bila tak dikenal)
            $table->string('from_wallet', 42)->nullable();
            $table->decimal('amount', 30, 6);
            $table->string('tx_hash', 66)->nullable();
            $table->timestamps();
            $table->index('community_wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_deposits');
        Schema::table('community_proposals', function (Blueprint $table) {
            $table->dropColumn(['type', 'target_user_id', 'meta']);
        });
        Schema::table('community_wallets', function (Blueprint $table) {
            $table->dropColumn('owner_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Model komunitas MUDAH (dikelola app): teman + undang, dompet komunitas custodial
 * (kunci dienkripsi dgn secret server, ditandatangani backend). Aturan (jatah/ambang)
 * & approval dikelola di DB; anggota mengonfirmasi aksi dengan PIN masing-masing.
 * (Prototipe/testnet — custodial. Kontrak Solidity on-chain tetap tersimpan di /contracts.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Daftar teman (kontak) per user.
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('friend_id');
            $table->timestamps();
            $table->unique(['user_id', 'friend_id']);
        });

        // Perluas community_wallets jadi dompet yang dikelola app.
        Schema::table('community_wallets', function (Blueprint $table) {
            $table->boolean('managed')->default(false)->after('mode'); // true = dikelola app
            $table->text('wallet_enc')->nullable();
            $table->string('wallet_salt')->nullable();
            $table->string('wallet_iv')->nullable();
            $table->string('wallet_tag')->nullable();
            $table->unsignedTinyInteger('threshold')->nullable(); // Mode B: M dari N
            $table->timestamp('gas_dripped_at')->nullable();
        });

        Schema::create('community_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('community_wallet_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('monthly_limit', 30, 6)->nullable(); // Mode A
            $table->decimal('spent', 30, 6)->default(0);
            $table->timestamp('period_start')->nullable();
            $table->timestamps();
            $table->unique(['community_wallet_id', 'user_id']);
        });

        Schema::create('community_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('community_wallet_id');
            $table->unsignedBigInteger('proposer_id');
            $table->string('to_wallet', 42);
            $table->string('to_name')->nullable();
            $table->decimal('amount', 30, 6);
            $table->string('note')->nullable();
            $table->string('status')->default('open'); // open | executed
            $table->string('tx_hash', 66)->nullable();
            $table->timestamps();
        });

        Schema::create('community_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['proposal_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_approvals');
        Schema::dropIfExists('community_proposals');
        Schema::dropIfExists('community_members');
        Schema::table('community_wallets', function (Blueprint $table) {
            $table->dropColumn(['managed', 'wallet_enc', 'wallet_salt', 'wallet_iv', 'wallet_tag', 'threshold', 'gas_dripped_at']);
        });
        Schema::dropIfExists('friends');
    }
};

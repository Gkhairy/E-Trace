<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nonce login wallet: beri masa berlaku (cegah replay signature lama).
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('nonce_expires_at')->nullable()->after('nonce');
        });

        // Integritas order + pelacakan konfirmasi/finality (dipakai indexer Fase 2).
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('block_number')->nullable()->after('tx_hash');
            $table->unsignedInteger('confirmations')->default(0)->after('block_number');
            $table->timestamp('finalized_at')->nullable()->after('status');

            // Cegah duplikat/replay: satu order_id & satu tx_hash hanya sekali.
            $table->unique('order_id');
            $table->unique('tx_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nonce_expires_at');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
            $table->dropUnique(['tx_hash']);
            $table->dropColumn(['block_number', 'confirmations', 'finalized_at']);
        });
    }
};

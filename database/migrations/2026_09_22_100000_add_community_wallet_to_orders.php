<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order yang dibayar dari DANA KOMUNITAS (multisig Mode B).
 *
 * Saat dibayar lewat dompet komunitas, `payCart` dipanggil oleh dompet komunitas,
 * sehingga PEMBELI on-chain = alamat dompet komunitas. Kontrak mewajibkan
 * confirmItem/refundItem/disputeItem dipanggil pembeli, jadi kita perlu tahu order
 * mana yang harus ditandatangani memakai kunci dompet komunitas (custodial).
 *
 * `user_id` tetap = pengusul (pembeli yang menerima barang) agar order muncul di /orders miliknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('community_wallet_id')->nullable()->after('user_id');
            $table->index('community_wallet_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['community_wallet_id']);
            $table->dropColumn('community_wallet_id');
        });
    }
};

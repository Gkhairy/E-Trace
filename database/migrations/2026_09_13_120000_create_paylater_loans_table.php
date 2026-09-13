<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cermin (mirror) aksi Paylater on-chain untuk riwayat & tampilan cepat.
 * Kebenaran posisi tetap dari chain (PaylaterVerifier); tabel ini hanya jejak aksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paylater_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('wallet_address', 42);
            $table->string('action', 12); // deposit | borrow | repay | withdraw | seize
            $table->decimal('amount', 30, 6); // jumlah human (tBNB utk deposit/withdraw, TLKM utk borrow/repay)
            $table->string('tx_hash', 66)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paylater_loans');
    }
};

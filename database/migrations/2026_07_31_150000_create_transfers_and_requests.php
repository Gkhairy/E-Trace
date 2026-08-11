<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permintaan uang (Minta Uang) — link/QR yang bisa dibayar orang lain.
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();       // kode pendek utk link/QR
            $table->unsignedBigInteger('user_id');      // pembuat (penerima uang)
            $table->decimal('amount', 30, 6)->nullable(); // nominal diminta (opsional)
            $table->string('note')->nullable();
            $table->string('status')->default('open');  // open (reusable, seperti ShopeePay)
            $table->timestamps();
        });

        // Transfer TLKM P2P (terverifikasi on-chain).
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('from_wallet', 42)->index();
            $table->string('to_wallet', 42)->index();
            $table->decimal('amount', 30, 6);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('request_id')->nullable();  // bila via Minta Uang
            $table->string('tx_hash', 66)->unique();
            $table->unsignedBigInteger('block_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('payment_requests');
    }
};

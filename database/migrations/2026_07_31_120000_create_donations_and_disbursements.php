<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Donasi masuk (terverifikasi on-chain).
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('donor_wallet', 42)->index();
            $table->decimal('amount', 30, 6);          // TLKM
            $table->string('tx_hash', 66)->unique();
            $table->unsignedBigInteger('block_number')->nullable();
            $table->timestamps();
        });

        // Penyaluran keluar oleh validator (terverifikasi on-chain).
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->string('to_address', 42)->index();
            $table->decimal('amount', 30, 6);          // TLKM
            $table->string('by_wallet', 42);           // wallet validator pemicu
            $table->string('tx_hash', 66)->unique();
            $table->unsignedBigInteger('block_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursements');
        Schema::dropIfExists('donations');
    }
};

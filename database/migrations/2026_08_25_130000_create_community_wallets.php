<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metadata dompet komunitas. Kontrak di-deploy manual di Remix; alamatnya
 * didaftarkan di sini agar frontend bisa memuat & berinteraksi via ethers.js.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mode', 1);            // 'A' = jatah bulanan, 'B' = multisig
            $table->string('address', 42)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_wallets');
    }
};

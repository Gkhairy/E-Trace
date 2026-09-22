<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks transfer TLKM on-chain (event Transfer) agar Explorer bisa menampilkan
 * riwayat PENUH sebuah alamat — masuk dari siapa, keluar ke mana — sejak token lahir.
 *
 * Kenapa diindeks, bukan dibaca langsung saat halaman dibuka: node membatasi
 * getLogs 50.000 blok/query sedangkan BSC testnet ~0,45 detik/blok, sehingga
 * membaca live hanya menjangkau ~beberapa jam dan butuh belasan detik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('tx_hash', 66);
            $table->unsignedInteger('log_index');
            $table->unsignedBigInteger('block_number');
            $table->timestamp('block_time')->nullable();
            $table->string('from_address', 42);
            $table->string('to_address', 42);
            $table->decimal('amount', 38, 18);   // nominal TLKM (desimal penuh)
            $table->timestamps();

            $table->unique(['tx_hash', 'log_index']);   // idempoten saat indeks ulang
            $table->index(['from_address', 'block_number']);
            $table->index(['to_address', 'block_number']);
            $table->index('block_number');
        });

        // Penanda sampai blok mana sudah diindeks (resumable).
        Schema::create('indexer_cursors', function (Blueprint $table) {
            $table->string('name', 60)->primary();
            $table->unsignedBigInteger('block_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_transfers');
        Schema::dropIfExists('indexer_cursors');
    }
};

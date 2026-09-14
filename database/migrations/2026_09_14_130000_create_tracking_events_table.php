<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat tracking pengiriman (lintas kurir, tidak terstruktur) — bahan baku
 * keputusan DeliveryAI + jejak audit. Sumber kebenaran keputusan tetap dihitung
 * server dari baris-baris ini, bukan dari browser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->text('raw_text');                                   // teks tracking mentah (apa adanya)
            $table->enum('source', ['courier', 'simulated'])->default('courier');
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};

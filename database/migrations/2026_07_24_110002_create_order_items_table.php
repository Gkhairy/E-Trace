<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 order (header) = banyak item. Tiap item = sub-escrow terpisah on-chain
        // (dikunci pakai order_id string milik header + item_index).
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_ref_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('seller_wallet');
            $table->decimal('amount', 18, 6);
            $table->unsignedInteger('item_index');           // index item di array on-chain
            $table->string('status')->default('paid');       // paid | completed | refunded
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

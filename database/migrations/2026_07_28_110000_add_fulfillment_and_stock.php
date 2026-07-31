<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Logistik per item (OFF-CHAIN): pending -> processing -> shipped(+resi) -> delivered.
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('amount');
            $table->string('fulfillment_status')->default('pending')->after('status'); // pending|processing|shipped|delivered
            $table->string('tracking_number')->nullable()->after('fulfillment_status');
            $table->string('courier')->nullable()->after('tracking_number');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });

        // Stok produk. NULL = tak dibatasi (unlimited).
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->nullable()->after('price_usdc');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'fulfillment_status', 'tracking_number', 'courier', 'shipped_at', 'delivered_at']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};

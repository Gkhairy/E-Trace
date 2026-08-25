<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk kolom yang sering difilter/urut — biar cepat walau data ribuan.
 * Nama index eksplisit agar tidak bentrok dengan index lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'orders_created_at_idx');
            $table->index('status', 'orders_status_idx');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['seller_wallet', 'status'], 'oi_seller_status_idx');
            $table->index('status', 'oi_status_idx');
            $table->index('created_at', 'oi_created_at_idx');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index('created_at', 'products_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropIndex('orders_created_at_idx'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropIndex('orders_status_idx'));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropIndex('oi_seller_status_idx'));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropIndex('oi_status_idx'));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropIndex('oi_created_at_idx'));
        Schema::table('products', fn (Blueprint $t) => $t->dropIndex('products_created_at_idx'));
    }
};

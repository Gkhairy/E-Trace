<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom baru untuk header order multi-item.
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total', 18, 6)->nullable()->after('amount');
            $table->foreignId('shipping_address_id')->nullable()->after('total')
                  ->constrained('shipping_addresses')->nullOnDelete();
        });

        // Migrasi AMAN: pindahkan order lama (single) menjadi 1 order_item (index 0),
        // supaya tampil di halaman /orders yang baru (berbasis item).
        $orders = DB::table('orders')->get();
        foreach ($orders as $o) {
            // seller_wallet diambil dari produk terkait (server-side, bukan on-chain).
            $sellerWallet = DB::table('products')->where('id', $o->product_id)->value('seller_wallet') ?? '';

            // Set total header = amount lama.
            DB::table('orders')->where('id', $o->id)->update(['total' => $o->amount]);

            // Hindari duplikat kalau migration dijalankan ulang.
            $already = DB::table('order_items')->where('order_ref_id', $o->id)->exists();
            if (! $already && $o->product_id) {
                DB::table('order_items')->insert([
                    'order_ref_id' => $o->id,
                    'product_id'   => $o->product_id,
                    'seller_wallet'=> $sellerWallet,
                    'amount'       => $o->amount,
                    'item_index'   => 0,
                    'status'       => $o->status ?? 'paid',
                    'created_at'   => $o->created_at,
                    'updated_at'   => $o->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_address_id']);
            $table->dropColumn(['total', 'shipping_address_id']);
        });
    }
};

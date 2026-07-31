<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Role: buyer (default) | seller | supervisor.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('buyer')->after('is_admin');
        });

        // 2) Toko milik penjual.
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade'); // 1 toko / penjual (MVP)
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->text('origin_address')->nullable();
            $table->string('payout_wallet'); // wallet penerima dana escrow (= wallet penjual)
            $table->string('status')->default('active'); // pending | active | suspended
            $table->timestamps();
        });

        // 3) Produk terhubung ke toko.
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // 4) Migrasi aman: admin lama (listing produk) -> seller, buatkan toko, link produk.
        DB::table('users')->where('is_admin', true)->update(['role' => 'seller']);

        foreach (DB::table('users')->where('role', 'seller')->get() as $u) {
            $storeId = DB::table('stores')->insertGetId([
                'user_id'       => $u->id,
                'name'          => $u->name ?: ('Toko ' . $u->id),
                'slug'          => Str::slug(($u->name ?: 'toko-' . $u->id)) . '-' . Str::lower(Str::random(4)),
                'payout_wallet' => $u->wallet_address,
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            // Sambungkan produk yang seller_wallet-nya = wallet penjual ini.
            DB::table('products')->where('seller_wallet', $u->wallet_address)->update(['store_id' => $storeId]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
        Schema::dropIfExists('stores');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

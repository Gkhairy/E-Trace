<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penanda agar email hanya dikirim SEKALI (idempotent, aman untuk retry/re-sync worker).
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('buyer_notified_at')->nullable()->after('status');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('seller_notified_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('buyer_notified_at');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('seller_notified_at');
        });
    }
};

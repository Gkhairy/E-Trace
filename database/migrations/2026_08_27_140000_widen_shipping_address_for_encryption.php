<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom PII dienkripsi -> blob panjang, butuh tipe text.
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->text('recipient_name')->nullable()->change();
            $table->text('phone')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('notes')->nullable()->change();
        });
    }
};

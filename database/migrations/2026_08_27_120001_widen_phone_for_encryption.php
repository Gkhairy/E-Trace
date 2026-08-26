<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom phone dulunya integer — tidak muat blob terenkripsi. Ubah ke text.
        Schema::table('users', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('phone')->nullable()->change();
        });
    }
};

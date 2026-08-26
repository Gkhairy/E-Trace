<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 16)->nullable();  // emoji ikon (ala Tokopedia/Shopee)
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('store_id')
                ->constrained('categories')->nullOnDelete();
        });

        // Kategori default (bisa diedit/ditambah kemudian).
        $now = now();
        $defaults = [
            ['Elektronik', 'elektronik', '📱'],
            ['Fashion', 'fashion', '👗'],
            ['Rumah Tangga', 'rumah-tangga', '🏠'],
            ['Kecantikan', 'kecantikan', '💄'],
            ['Makanan & Minuman', 'makanan-minuman', '🍔'],
            ['Kesehatan', 'kesehatan', '🩺'],
            ['Olahraga', 'olahraga', '⚽'],
            ['Hobi & Mainan', 'hobi-mainan', '🎮'],
            ['Otomotif', 'otomotif', '🚗'],
            ['Buku & Alat Tulis', 'buku-alat-tulis', '📚'],
            ['Bayi & Anak', 'bayi-anak', '🧸'],
            ['Lainnya', 'lainnya', '📦'],
        ];
        $rows = [];
        foreach ($defaults as $i => $d) {
            $rows[] = ['name' => $d[0], 'slug' => $d[1], 'icon' => $d[2], 'sort' => $i, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('categories')->insert($rows);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::dropIfExists('categories');
    }
};

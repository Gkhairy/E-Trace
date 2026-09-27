<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foto kejadian dari sumbernya (artikel berita / peta guncangan BMKG) + kreditnya.
        // Foto tidak disalin ke server: ditampilkan dari situs sumber dengan kredit & link.
        Schema::table('disaster_events', function (Blueprint $table) {
            $table->text('image_url')->nullable()->after('url');
            $table->string('image_credit', 120)->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('disaster_events', fn (Blueprint $t) => $t->dropColumn(['image_url', 'image_credit']));
    }
};

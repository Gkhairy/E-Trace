<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Radar Bencana: kejadian dari BMKG / GDACS / berita, dinilai AI, bisa membuka campaign.
        Schema::create('disaster_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 16);                 // bmkg / gdacs / news / manual
            $table->string('external_id', 191);           // id unik di sumbernya
            $table->string('type', 24)->nullable();       // gempa, banjir, kebakaran, ...
            $table->string('title');
            $table->string('location')->nullable();
            $table->decimal('magnitude', 4, 1)->nullable();
            $table->string('alert_level', 16)->nullable(); // GDACS: Green / Orange / Red
            $table->timestamp('occurred_at')->nullable();
            $table->text('url')->nullable();
            $table->text('summary')->nullable();

            // Penilaian AI
            $table->boolean('ai_is_disaster')->nullable();
            $table->unsignedTinyInteger('ai_severity')->nullable(); // 0..100
            $table->text('ai_reason')->nullable();
            $table->string('ai_title')->nullable();
            $table->text('ai_description')->nullable();

            // new / rejected / pending_review / opened / dismissed / duplicate
            $table->string('status', 20)->default('new')->index();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('duplicate_of')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('origin', 12)->default('manual')->after('status'); // manual / ai
            $table->unsignedBigInteger('disaster_event_id')->nullable()->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', fn (Blueprint $t) => $t->dropColumn(['origin', 'disaster_event_id']));
        Schema::dropIfExists('disaster_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campaign donasi (card) — metadata off-chain; identitas on-chain = keccak256(slug).
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();            // dipakai sbg campaignId on-chain
            $table->text('description')->nullable();
            $table->string('image')->nullable();         // foto card (public/campaign_images)
            $table->string('recipient_wallet', 42);      // wallet penerima donasi
            $table->decimal('goal_amount', 30, 6)->nullable(); // target opsional (progress bar)
            $table->string('status')->default('active'); // active / closed
            $table->unsignedBigInteger('created_by');    // user pembuat (pengawas)
            $table->timestamps();
        });

        // Tautkan donasi & penyaluran ke campaign.
        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('id')->index();
        });
        Schema::table('disbursements', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('disbursements', fn (Blueprint $t) => $t->dropColumn('campaign_id'));
        Schema::table('donations', fn (Blueprint $t) => $t->dropColumn('campaign_id'));
        Schema::dropIfExists('campaigns');
    }
};

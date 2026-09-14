<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Auto-Settlement + Garansi Tepat Waktu (asuransi parametrik, DEMO testnet).
 * Menambah kolom keputusan AI & status asuransi ke tabel orders. Tidak menyentuh
 * kolom/logika escrow yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Penyelesaian escrow (mengikuti keputusan AI / pengawas). 'pending' = belum diproses keeper.
            $table->enum('settlement_status', ['pending', 'released', 'refunded', 'held'])
                ->default('pending')->after('status');
            $table->json('ai_decision')->nullable()->after('settlement_status'); // output DeliveryAI (audit)
            $table->text('ai_reason')->nullable()->after('ai_decision');

            // Garansi Tepat Waktu (asuransi pengiriman).
            $table->decimal('shipping_tlkm', 18, 6)->nullable()->after('ai_reason'); // ongkir order (dasar payout klaim)
            $table->boolean('is_insured')->default(false)->after('shipping_tlkm');
            $table->decimal('premium_tlkm', 18, 6)->nullable()->after('is_insured');
            $table->dateTime('promised_date')->nullable()->after('premium_tlkm'); // ETA kurir + buffer
            $table->enum('insurance_status', ['none', 'active', 'paid', 'rejected'])
                ->default('none')->after('promised_date');
            $table->string('payout_tx', 80)->nullable()->after('insurance_status'); // tx payout klaim
            $table->decimal('payout_tlkm', 18, 6)->nullable()->after('payout_tx');   // nominal payout (circuit breaker harian)
            $table->string('premium_tx', 80)->nullable()->after('payout_tlkm');      // tx pembayaran premi
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'settlement_status', 'ai_decision', 'ai_reason',
                'is_insured', 'premium_tlkm', 'promised_date',
                'insurance_status', 'payout_tx', 'premium_tx',
            ]);
        });
    }
};

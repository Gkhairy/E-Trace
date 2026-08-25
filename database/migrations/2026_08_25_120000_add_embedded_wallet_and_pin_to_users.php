<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Embedded wallet (DIY, testnet) + PIN 6 angka.
 * Private key HANYA disimpan terenkripsi (AES-256-GCM), kunci turunan dari
 * PIN + WALLET_ENC_SECRET + salt per-user. PIN disimpan sebagai hash + lockout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_embedded')->default(false);   // wallet dibuat & dikelola platform
            $table->text('wallet_enc')->nullable();           // ciphertext private key (base64)
            $table->string('wallet_salt')->nullable();        // salt PBKDF2 per-user
            $table->string('wallet_iv')->nullable();          // nonce AES-GCM
            $table->string('wallet_tag')->nullable();         // auth tag AES-GCM
            $table->string('pin_hash')->nullable();           // bcrypt PIN (bukan plaintext)
            $table->unsignedTinyInteger('pin_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
            $table->timestamp('gas_dripped_at')->nullable();  // sudah dikirimi gas ETH?
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_embedded', 'wallet_enc', 'wallet_salt', 'wallet_iv', 'wallet_tag',
                'pin_hash', 'pin_attempts', 'pin_locked_until', 'gas_dripped_at',
            ]);
        });
    }
};

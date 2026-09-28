<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ChainSigner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * "Gas drip" testnet: wallet operator platform mengirim sedikit tBNB (BSC Testnet) ke
 * wallet embedded baru agar bisa bayar gas. Gratis (testnet). Lewat queue.
 * Butuh PLATFORM_GAS_PRIVATE_KEY (.env) yang wallet-nya sudah berisi tBNB (BSC Testnet).
 */
class GasDrip implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(ChainSigner $signer): void
    {
        $user = User::find($this->userId);
        if (!$user || !$user->wallet_address || $user->gas_dripped_at) {
            return;
        }

        $priv = (string) config('wallet.gas_private_key', '');
        if ($priv === '') {
            Log::warning('GasDrip: PLATFORM_GAS_PRIVATE_KEY belum diset — lewati.');
            return;
        }
        if (str_starts_with($priv, '0x')) {
            $priv = substr($priv, 2);
        }

        // Klaim atomik dulu: job ganda (retry/daftar ulang) tidak bisa mengirim dua kali.
        $claimed = User::whereKey($user->id)->whereNull('gas_dripped_at')->update(['gas_dripped_at' => now()]);
        if (!$claimed) {
            return;
        }

        $amount = (string) config('wallet.gas_drip_amount', '0.01');
        try {
            $signer->sendRaw($priv, $user->wallet_address, $signer->toWeiHex($amount)); // ETH 18 desimal
        } catch (\Throwable $e) {
            // Gagal → lepaskan klaim supaya drip bisa dicoba lagi.
            User::whereKey($user->id)->update(['gas_dripped_at' => null]);
            Log::warning('GasDrip gagal untuk user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}

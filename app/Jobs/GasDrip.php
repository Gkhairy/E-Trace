<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ChainSigner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * "Gas drip" testnet: wallet operator platform mengirim sedikit ETH Sepolia ke
 * wallet embedded baru agar bisa bayar gas. Gratis (testnet). Lewat queue.
 * Butuh PLATFORM_GAS_PRIVATE_KEY (.env) yang wallet-nya sudah berisi ETH Sepolia.
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

        $priv = (string) env('PLATFORM_GAS_PRIVATE_KEY', '');
        if ($priv === '') {
            Log::warning('GasDrip: PLATFORM_GAS_PRIVATE_KEY belum diset — lewati.');
            return;
        }
        if (str_starts_with($priv, '0x')) {
            $priv = substr($priv, 2);
        }

        $amount = (string) env('GAS_DRIP_AMOUNT', '0.01');
        try {
            $signer->sendRaw($priv, $user->wallet_address, $signer->toWeiHex($amount)); // ETH 18 desimal
            $user->forceFill(['gas_dripped_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::warning('GasDrip gagal untuk user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}

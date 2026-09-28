<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ChainSigner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Bonus TLKM uji coba: wallet platform (PLATFORM_GAS_PRIVATE_KEY, sama dengan gas drip)
 * mentransfer TLKM ke akun yang baru selesai verifikasi email, supaya penguji bisa langsung
 * belanja/donasi tanpa minta token dulu. Sekali per akun (users.tlkm_granted_at).
 * Wallet platform harus memegang TLKM; jumlah diatur WELCOME_TLKM_AMOUNT (0 = nonaktif).
 */
class WelcomeTlkm implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(ChainSigner $signer): void
    {
        $amount = (string) config('wallet.welcome_tlkm', '0');
        $priv   = (string) config('wallet.gas_private_key', '');
        $token  = (string) config('chain.tlkm', '');
        if (bccomp($amount, '0', 18) <= 0 || $priv === '' || !preg_match('/^0x[0-9a-fA-F]{40}$/', $token)) {
            return;
        }
        if (str_starts_with($priv, '0x')) {
            $priv = substr($priv, 2);
        }

        $user = User::find($this->userId);
        if (!$user || !$user->wallet_address || !$user->email_verified_at) {
            return;
        }

        // Klaim atomik dulu: job ganda (retry/klik ganda) tidak bisa mengirim dua kali.
        $claimed = User::whereKey($user->id)->whereNull('tlkm_granted_at')->update(['tlkm_granted_at' => now()]);
        if (!$claimed) {
            return;
        }

        // transfer(address,uint256) — selector a9059cbb.
        $data = '0xa9059cbb'
            . str_pad(substr(strtolower($user->wallet_address), 2), 64, '0', STR_PAD_LEFT)
            . str_pad(gmp_strval(gmp_init($signer->toWei($amount), 10), 16), 64, '0', STR_PAD_LEFT);

        try {
            // Satu pengiriman per waktu dari wallet platform, agar nonce tidak bentrok antar-job.
            Cache::lock('platform-wallet-tx', 60)->block(45, function () use ($signer, $priv, $token, $data) {
                $receipt = $signer->waitReceipt($signer->sendRaw($priv, $token, '0x0', $data));
                if (($receipt['status'] ?? null) === '0x0') {
                    throw new \RuntimeException('transfer TLKM di-revert (saldo TLKM wallet platform kurang?)');
                }
            });
        } catch (\Throwable $e) {
            // Gagal → lepaskan klaim supaya bisa dicoba lagi (tlkm:grant-welcome).
            User::whereKey($user->id)->update(['tlkm_granted_at' => null]);
            Log::warning('WelcomeTlkm gagal untuk user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}

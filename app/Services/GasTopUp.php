<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Menjaga wallet embedded & dompet komunitas tetap punya tBNB untuk biaya gas.
 *
 * Gas drip saat daftar hanya sekali, jadi setelah beberapa transaksi saldo tBNB habis
 * dan semua transaksi gagal. Sebelum menandatangani, saldo dicek; bila di bawah batas,
 * wallet gas platform (PLATFORM_GAS_PRIVATE_KEY) mengisi ulang dan kita tunggu ter-mine.
 */
class GasTopUp
{
    /** Cukup untuk approve + payCart beberapa kali pada harga gas BSC Testnet. */
    public const MIN_BALANCE = 0.002;

    public function __construct(private ChainSigner $signer) {}

    /**
     * Pastikan $address punya cukup tBNB. Isi ulang otomatis bila kurang; bila tetap
     * kurang, hentikan dengan 422 berisi $emptyMessage (atau pesan bawaan).
     */
    public function ensure(string $address, ?string $emptyMessage = null): void
    {
        $bal = $this->signer->ethBalance($address);
        if ($bal === null || $bal >= self::MIN_BALANCE) {
            return; // RPC gagal dibaca: biarkan transaksi mencoba, error-nya akan jelas
        }

        $this->drip($address);

        $bal = $this->signer->ethBalance($address);
        abort_if($bal !== null && $bal < self::MIN_BALANCE, 422, $emptyMessage
            ?? 'Saldo tBNB untuk biaya gas di wallet ini habis dan pengisian otomatis sedang tidak tersedia. Coba lagi beberapa menit lagi.');
    }

    private function drip(string $address): void
    {
        $priv = (string) config('wallet.gas_private_key', '');
        if ($priv === '') {
            Log::warning('GasTopUp: PLATFORM_GAS_PRIVATE_KEY belum diset — lewati.');
            return;
        }
        if (str_starts_with($priv, '0x')) {
            $priv = substr($priv, 2);
        }

        // Paling banyak sekali per alamat per 10 menit, agar wallet gas platform tidak terkuras.
        if (!Cache::add('gas-topup:' . strtolower($address), 1, now()->addMinutes(10))) {
            return;
        }

        try {
            $amount = (string) config('wallet.gas_drip_amount', '0.01');
            $hash = $this->signer->sendRaw($priv, $address, $this->signer->toWeiHex($amount));
            $this->signer->waitReceipt($hash); // saldo baru terbaca setelah ter-mine
        } catch (\Throwable $e) {
            Log::warning('GasTopUp gagal untuk ' . $address . ': ' . $e->getMessage());
        }
    }
}

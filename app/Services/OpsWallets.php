<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use kornrunner\Keccak;
use Throwable;

/**
 * Bacaan on-chain untuk wallet operasional platform (arbiter, pool asuransi, gas).
 *
 * Dipakai `config:check` saat boot dan dashboard pengawas. Kunci privat hanya dipakai
 * untuk menurunkan ALAMAT publiknya; nilainya tak pernah dikembalikan atau dicatat.
 * Setiap bacaan gagal → null, supaya pemanggil bisa menampilkan "tak terbaca" alih-alih error.
 */
class OpsWallets
{
    /** Alamat publik (huruf kecil) dari kunci privat, atau null bila kosong/tak valid. */
    public function addressOf($key): ?string
    {
        $hex = (string) $key;
        $hex = str_starts_with($hex, '0x') ? substr($hex, 2) : $hex;
        if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) {
            return null;
        }
        try {
            return strtolower((new EmbeddedWallet())->addressFromPrivate($hex));
        } catch (Throwable) {
            return null;
        }
    }

    /** Arbiter yang tercatat di kontrak gateway. */
    public function arbiterOnChain(): ?string
    {
        $result = $this->rpc('eth_call', [[
            'to'   => config('chain.gateway'),
            'data' => '0x' . substr(Keccak::hash('arbiter()', 256), 0, 8),
        ], 'latest']);
        return is_string($result) && strlen($result) >= 42 ? '0x' . strtolower(substr($result, -40)) : null;
    }

    /** Saldo tBNB (desimal string). */
    public function nativeBalance(string $addr): ?string
    {
        $result = $this->rpc('eth_getBalance', [$addr, 'latest']);
        return is_string($result) ? $this->fromWei($result) : null;
    }

    /** Saldo token TLKM (desimal string). */
    public function tlkmBalance(string $addr): ?string
    {
        $arg = str_pad(substr(strtolower($addr), 2), 64, '0', STR_PAD_LEFT);
        $result = $this->rpc('eth_call', [[
            'to'   => config('chain.tlkm'),
            'data' => '0x' . substr(Keccak::hash('balanceOf(address)', 256), 0, 8) . $arg,
        ], 'latest']);
        return is_string($result) && $result !== '0x' ? $this->fromWei($result) : null;
    }

    public static function short(string $addr): string
    {
        return substr($addr, 0, 6) . '…' . substr($addr, -4);
    }

    private function fromWei(string $hex): string
    {
        $dec = bcdiv(gmp_strval(gmp_init($hex, 16)), '1000000000000000000', 6);
        return rtrim(rtrim($dec, '0'), '.') ?: '0';
    }

    private function rpc(string $method, array $params)
    {
        try {
            return Http::timeout(8)->post((string) config('chain.rpc_url'), [
                'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
            ])->json('result');
        } catch (Throwable) {
            return null;
        }
    }
}

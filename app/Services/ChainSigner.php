<?php

namespace App\Services;

use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Membangun, menandatangani (offline), dan menyiarkan transaksi legacy ke jaringan
 * yang dikonfigurasi (config/chain.php) — kini BNB Smart Chain Testnet (chainId 97).
 * chainId dari config dipakai untuk EIP-155 signing; jika salah, transaksi ditolak jaringan.
 * Dipakai untuk: gas-drip (kirim native coin) & panggilan kontrak (approve/donate/payCart/transfer)
 * atas nama embedded wallet — private key HANYA ada sesaat di memori setelah dekripsi PIN.
 */
class ChainSigner
{
    private string $rpc;
    private int $chainId;
    private ?string $lastError = null; // pesan error RPC terakhir, untuk pesan gagal yang jelas

    public function __construct()
    {
        $this->rpc     = config('chain.rpc_url');
        $this->chainId = (int) config('chain.chain_id', 97);
    }

    private function rpc(string $method, array $params = [])
    {
        $res = Http::timeout(25)->acceptJson()->post($this->rpc, [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
        ]);
        if (!$res->ok()) {
            $this->lastError = 'RPC HTTP ' . $res->status();
            return null;
        }
        $json = $res->json();
        $this->lastError = $json['error']['message'] ?? null;
        return $json['result'] ?? null; // error → null (dilempar oleh pemanggil)
    }

    /** Saldo ETH (gas) sebuah alamat dalam ETH (float). Null bila RPC gagal. */
    public function ethBalance(string $address): ?float
    {
        $res = $this->rpc('eth_getBalance', [$address, 'latest']);
        if ($res === null) {
            return null;
        }
        return (float) (gmp_strval(gmp_init($res, 16)) / 1e18);
    }

    /** Konversi jumlah token desimal (mis. "12.5") ke wei hex (18 desimal). */
    public function toWeiHex(string|float|int $amount, int $decimals = 18): string
    {
        return '0x' . gmp_strval(gmp_init($this->toWei($amount, $decimals), 10), 16);
    }

    /** Konversi jumlah token desimal ke wei sebagai string desimal (untuk arg uint256). */
    public function toWei(string|float|int $amount, int $decimals = 18): string
    {
        return bcmul((string) $amount, bcpow('10', (string) $decimals), 0);
    }

    /** Tunggu receipt tx ter-mine (polling). Return receipt atau null bila timeout. */
    public function waitReceipt(string $hash, int $tries = 20, int $sleepMs = 1500): ?array
    {
        for ($i = 0; $i < $tries; $i++) {
            $r = $this->rpc('eth_getTransactionReceipt', [$hash]);
            if ($r) {
                return $r;
            }
            usleep($sleepMs * 1000);
        }
        return null;
    }

    /** Allowance TLKM owner→spender (wei desimal string). */
    public function allowance(string $token, string $owner, string $spender): string
    {
        $sel = substr(\kornrunner\Keccak::hash('allowance(address,address)', 256), 0, 8);
        $a1  = str_pad(substr(strtolower($owner), 2), 64, '0', STR_PAD_LEFT);
        $a2  = str_pad(substr(strtolower($spender), 2), 64, '0', STR_PAD_LEFT);
        $res = $this->rpc('eth_call', [['to' => $token, 'data' => '0x' . $sel . $a1 . $a2], 'latest']);
        return ($res && $res !== '0x') ? gmp_strval(gmp_init($res, 16), 10) : '0';
    }

    /** Encode data panggilan fungsi kontrak (ABI dinamis ditangani Web3\Contract). */
    public function encodeCall(array $abi, string $method, array $args): string
    {
        $contract = new Contract($this->rpc, $abi);
        $data = $contract->getData($method, ...$args);
        // JANGAN pakai ltrim('0x') — itu menghapus angka 0 di depan selector.
        if (str_starts_with($data, '0x')) {
            $data = substr($data, 2);
        }
        return '0x' . $data;
    }

    /**
     * Tanda tangani & siarkan satu transaksi. $to = tujuan (kontrak/wallet),
     * $valueWeiHex = nilai ETH (untuk transfer ETH/gas-drip), $dataHex = calldata.
     * Return tx hash. Lempar exception bila gagal (mis. saldo gas kurang).
     */
    public function sendRaw(string $privHex, string $to, string $valueWeiHex = '0x0', string $dataHex = ''): string
    {
        $from = strtolower((new EmbeddedWallet())->addressFromPrivate($privHex));

        // Satu pengiriman per alamat per waktu (web, worker, keeper, job). Tanpa ini dua proses
        // yang memakai kunci yang sama (mis. GasDrip + WelcomeTlkm dari wallet gas platform)
        // membaca nonce yang sama dan salah satu transaksinya ditolak.
        return \Illuminate\Support\Facades\Cache::lock('chain-tx:' . $from, 30)->block(20,
            fn () => $this->signAndBroadcast($privHex, $from, $to, $valueWeiHex, $dataHex));
    }

    private function signAndBroadcast(string $privHex, string $from, string $to, string $valueWeiHex, string $dataHex): string
    {
        // Nonce 'pending' dari RPC bisa tertinggal sesaat setelah broadcast (node berbeda di
        // balik load balancer), jadi ambil yang lebih besar dari nonce terakhir yang kita pakai.
        $nonceKey = 'chain-nonce:' . $from;
        $rpcNonce = gmp_init($this->rpc('eth_getTransactionCount', [$from, 'pending']) ?: '0x0', 16);
        $last     = \Illuminate\Support\Facades\Cache::get($nonceKey);
        $nonceInt = ($last !== null && gmp_cmp(gmp_add(gmp_init($last, 10), 1), $rpcNonce) > 0)
            ? gmp_add(gmp_init($last, 10), 1)
            : $rpcNonce;
        $nonce    = '0x' . gmp_strval($nonceInt, 16);
        $gasPrice = $this->rpc('eth_gasPrice') ?: '0x3b9aca00';
        $gas      = $this->estimateGas($from, $to, $valueWeiHex, $dataHex);

        $tx = new Transaction([
            'nonce'    => $nonce,
            'gasPrice' => $gasPrice,
            'gas'      => $gas,
            'to'       => $to,
            'value'    => ($valueWeiHex === '0x0' || $valueWeiHex === '0x') ? '0x0' : $valueWeiHex,
            'data'     => $dataHex ?: '0x',
            'chainId'  => $this->chainId,
        ]);

        $signed = '0x' . $tx->sign($privHex);
        $hash = $this->rpc('eth_sendRawTransaction', [$signed]);
        if (!$hash) {
            $err = (string) $this->lastError;
            throw new \App\Exceptions\ChainTxException(str_contains(strtolower($err), 'insufficient funds')
                ? __('Saldo tBNB untuk biaya gas tidak cukup. Coba lagi sebentar lagi.')
                : __('Gagal mengirim transaksi ke blockchain') . ($err !== '' ? ': ' . $err : '.'));
        }
        \Illuminate\Support\Facades\Cache::put($nonceKey, gmp_strval($nonceInt, 10), now()->addMinutes(2));
        return $hash;
    }

    /** Panggil fungsi kontrak (encode + sign + broadcast). */
    public function sendContractCall(string $privHex, string $contract, array $abi, string $method, array $args, string $valueWeiHex = '0x0'): string
    {
        return $this->sendRaw($privHex, $contract, $valueWeiHex, $this->encodeCall($abi, $method, $args));
    }

    private function estimateGas(string $from, string $to, string $value, string $data): string
    {
        $call = ['from' => $from, 'to' => $to];
        if ($value !== '0x0' && $value !== '') $call['value'] = $value;
        if ($data)                              $call['data']  = $data;

        $res = $this->rpc('eth_estimateGas', [$call]);
        if (!$res) {
            return '0x' . dechex($data ? 250000 : 21000); // fallback aman
        }
        $g = (int) (hexdec($res) * 1.3); // buffer 30%
        return '0x' . dechex($g);
    }
}

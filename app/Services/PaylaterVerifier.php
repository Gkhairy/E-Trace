<?php

namespace App\Services;

use kornrunner\Keccak;
use Illuminate\Support\Facades\Http;

/**
 * Verifikasi posisi Paylater langsung ke kontrak on-chain via RPC dari config/chain.php.
 * Sumber kebenaran = on-chain (positionOf / ownerFundInfo). Data dari browser TIDAK
 * dipercaya. Pola sama seperti ChainVerifier. (Kontrak: TlkmPaylater.sol — DEMO testnet.)
 */
class PaylaterVerifier
{
    private string $rpc;
    private ?string $contract;

    public function __construct()
    {
        $this->rpc = config('chain.rpc_url');
        $addr = config('chain.paylater_address');
        $this->contract = ($addr && preg_match('/^0x[a-fA-F0-9]{40}$/', $addr)) ? strtolower($addr) : null;
    }

    /** Kontrak Paylater sudah dikonfigurasi (PAYLATER_ADDRESS terisi)? */
    public function configured(): bool
    {
        return $this->contract !== null;
    }

    public function address(): ?string
    {
        return $this->contract;
    }

    private function rpc(string $method, array $params)
    {
        $res = Http::timeout(12)->acceptJson()->post($this->rpc, [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
        ]);
        return $res->ok() ? ($res->json()['result'] ?? null) : null;
    }

    /**
     * Posisi user (wei string): collateral (tBNB), principal (TLKM pokok), due_amount
     * (TLKM kewajiban = pokok+bunga), due_date (unix), limit (TLKM).
     * Null bila kontrak belum diset atau RPC gagal.
     */
    public function positionOf(string $address): ?array
    {
        if (!$this->contract || !preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return null;
        }
        $sel = substr(Keccak::hash('positionOf(address)', 256), 0, 8);
        $arg = str_pad(substr(strtolower($address), 2), 64, '0', STR_PAD_LEFT);
        $res = $this->rpc('eth_call', [['to' => $this->contract, 'data' => '0x' . $sel . $arg], 'latest']);
        if (!$res || strlen($res) < 2 + 64 * 5) {
            return null;
        }
        $h = substr($res, 2);
        $word = fn ($i) => gmp_strval(gmp_init('0x' . substr($h, $i * 64, 64)));
        return [
            'collateral' => $word(0),   // tBNB wei
            'principal'  => $word(1),   // TLKM wei (pokok)
            'due_amount' => $word(2),   // TLKM wei (kewajiban = pokok + bunga)
            'due_date'   => (int) $word(3),
            'limit'      => $word(4),   // TLKM wei
        ];
    }

    /** Baca fungsi view yang mengembalikan satu uint256 (wei string). Null bila gagal. */
    private function callUint(string $signature, string $argHex = ''): ?string
    {
        if (!$this->contract) {
            return null;
        }
        $sel = substr(Keccak::hash($signature, 256), 0, 8);
        $res = $this->rpc('eth_call', [['to' => $this->contract, 'data' => '0x' . $sel . $argHex], 'latest']);
        if ($res === null || $res === '0x') {
            return null;
        }
        return gmp_strval(gmp_init($res, 16));
    }

    private function addrArg(string $address): string
    {
        return str_pad(substr(strtolower($address), 2), 64, '0', STR_PAD_LEFT);
    }

    private function uintArg(int $n): string
    {
        return str_pad(dechex($n), 64, '0', STR_PAD_LEFT);
    }

    /** Baca fungsi view yang mengembalikan beberapa uint256. Return array string, atau null. */
    private function callTuple(string $signature, string $argHex, int $count): ?array
    {
        if (!$this->contract) {
            return null;
        }
        $sel = substr(Keccak::hash($signature, 256), 0, 8);
        $res = $this->rpc('eth_call', [['to' => $this->contract, 'data' => '0x' . $sel . $argHex], 'latest']);
        if (!$res || strlen($res) < 2 + 64 * $count) {
            return null;
        }
        $h = substr($res, 2);
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = gmp_strval(gmp_init('0x' . substr($h, $i * 64, 64)));
        }
        return $out;
    }

    /**
     * Posisi PENYUPLAI untuk satu jangka (term 0=fleksibel,1=30h,2=90h).
     *  shares, principal (setoran), value (klaim=pokok+yield), maturity (unix, 0=fleksibel).
     */
    public function supplierInfo(string $address, int $term): ?array
    {
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return null;
        }
        $t = $this->callTuple('supplierInfo(address,uint8)', $this->addrArg($address) . $this->uintArg($term), 4);
        if (!$t) {
            return null;
        }
        return ['shares' => $t[0], 'principal' => $t[1], 'value' => $t[2], 'maturity' => (int) $t[3]];
    }

    /** Statistik satu bucket jangka: principal, assets, shares, nisbah (bps), lock (detik). */
    public function bucketInfo(int $term): ?array
    {
        $t = $this->callTuple('bucketInfo(uint8)', $this->uintArg($term), 5);
        if (!$t) {
            return null;
        }
        return ['principal' => $t[0], 'assets' => $t[1], 'shares' => $t[2], 'nisbah_bps' => (int) $t[3], 'lock' => (int) $t[4]];
    }

    /** Statistik pool global: liquidity, borrows, reserve, total_assets, util_bps. */
    public function poolStats(): ?array
    {
        $t = $this->callTuple('poolStats()', '', 5);
        if (!$t) {
            return null;
        }
        return ['liquidity' => $t[0], 'borrows' => $t[1], 'reserve' => $t[2], 'assets' => $t[3], 'util_bps' => (int) $t[4]];
    }

    /** Apakah kontrak sudah jadi owner TLKM (boleh mint / cadangan likuiditas)? Null bila RPC gagal. */
    public function canMint(): ?bool
    {
        if (!$this->contract) {
            return null;
        }
        $sel = substr(Keccak::hash('canMint()', 256), 0, 8);
        $res = $this->rpc('eth_call', [['to' => $this->contract, 'data' => '0x' . $sel], 'latest']);
        if ($res === null || $res === '0x') {
            return null;
        }
        return gmp_strval(gmp_init($res, 16)) === '1';
    }

    /** Likuiditas TLKM tersedia di kontrak (wei string). Null bila gagal. */
    public function availableLiquidity(): ?string
    {
        if (!$this->contract) {
            return null;
        }
        $sel = substr(Keccak::hash('availableLiquidity()', 256), 0, 8);
        $res = $this->rpc('eth_call', [['to' => $this->contract, 'data' => '0x' . $sel], 'latest']);
        if (!$res || $res === '0x') {
            return null;
        }
        return gmp_strval(gmp_init($res, 16));
    }
}

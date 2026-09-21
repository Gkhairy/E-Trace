<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use kornrunner\Keccak;

/**
 * TransferFeed — daftar transfer TLKM terbaru dengan membaca event Transfer
 * langsung dari blockchain (eth_getLogs). Mencakup SEMUA pergerakan TLKM:
 * transfer biasa, Paylater, donasi, payout asuransi (semuanya event Transfer).
 *
 * Catatan: RPC utama (data-seed) memblokir getLogs ("limit exceeded"), jadi feed ini
 * memakai RPC khusus yang mengizinkannya (config chain.logs_rpc_url, default publicnode).
 * Tanpa API key. Aman-nonaktif bila alamat TLKM belum diset; read-only.
 */
class TransferFeed
{
    private const ZERO = '0x0000000000000000000000000000000000000000';

    /** Aktif bila alamat TLKM sudah dikonfigurasi (RPC selalu tersedia, tanpa key). */
    public static function enabled(): bool
    {
        $tlkm = strtolower((string) config('chain.tlkm'));
        return $tlkm !== '' && $tlkm !== self::ZERO;
    }

    /**
     * Transfer TLKM terbaru (desc by block). Tiap baris:
     * from, to, tlkm (float), tx, block, time (unix|null).
     */
    public function recent(int $limit = 25): array
    {
        if (!self::enabled()) {
            return [];
        }

        $rpc   = config('chain.logs_rpc_url', 'https://bsc-testnet-rpc.publicnode.com');
        $tlkm  = strtolower(config('chain.tlkm'));
        $topic = '0x' . Keccak::hash('Transfer(address,address,uint256)', 256);

        $latest = (new ChainVerifier())->latestBlock();
        if (!$latest) {
            return [];
        }
        $win  = max(200, (int) config('chain.transfers_lookback_blocks', 5000));
        $from = '0x' . dechex(max(0, $latest - $win));

        try {
            $res  = $this->rpc($rpc, 'eth_getLogs', [[
                'address' => $tlkm, 'topics' => [$topic], 'fromBlock' => $from, 'toBlock' => 'latest',
            ]]);
            if (!is_array($res)) {
                return []; // mis. "limit exceeded" / RPC error → kosong yang aman
            }

            $rows = [];
            foreach ($res as $log) {
                $topics = $log['topics'] ?? [];
                if (count($topics) < 3) {
                    continue; // butuh from & to (indexed)
                }
                $valHex = $log['data'] ?? '0x0';
                $wei = ($valHex && $valHex !== '0x') ? gmp_strval(gmp_init($valHex)) : '0';
                $rows[] = [
                    'from'  => '0x' . substr($topics[1], -40),
                    'to'    => '0x' . substr($topics[2], -40),
                    'tlkm'  => (float) bcdiv($wei, '1000000000000000000', 4),
                    'tx'    => $log['transactionHash'] ?? '',
                    'block' => hexdec($log['blockNumber'] ?? '0x0'),
                    'time'  => null,
                ];
            }

            usort($rows, fn ($a, $b) => $b['block'] <=> $a['block']);
            $rows = array_slice($rows, 0, $limit);

            // Lengkapi waktu: ambil timestamp tiap blok unik (eth_getBlockByNumber).
            $times = [];
            foreach (array_unique(array_column($rows, 'block')) as $b) {
                $blk = $this->rpc($rpc, 'eth_getBlockByNumber', ['0x' . dechex($b), false]);
                if (isset($blk['timestamp'])) {
                    $times[$b] = hexdec($blk['timestamp']);
                }
            }
            foreach ($rows as &$r) {
                $r['time'] = $times[$r['block']] ?? null;
            }

            return $rows;
        } catch (\Throwable $e) {
            Log::warning('TransferFeed gagal: ' . $e->getMessage());
            return [];
        }
    }

    private function rpc(string $url, string $method, array $params)
    {
        $res = Http::timeout(20)->acceptJson()->post($url, [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
        ]);
        return $res->ok() ? ($res->json()['result'] ?? null) : null;
    }
}

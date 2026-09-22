<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use kornrunner\Keccak;

/**
 * TransferFeed — daftar transfer TLKM dengan membaca event Transfer langsung dari
 * blockchain (eth_getLogs). Mencakup SEMUA pergerakan TLKM: transfer biasa,
 * Paylater, donasi, dana komunitas, payout asuransi.
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
     * Transfer TLKM terbaru se-jaringan (desc by block). Tiap baris:
     * from, to, tlkm (float), tx, block, time (unix|null).
     */
    public function recent(int $limit = 25): array
    {
        if (!self::enabled()) {
            return [];
        }
        [$rpc, $topic, $from] = $this->window();
        if ($from === null) {
            return [];
        }

        try {
            $res = $this->rpc($rpc, 'eth_getLogs', [[
                'address' => strtolower(config('chain.tlkm')),
                'topics' => [$topic], 'fromBlock' => $from, 'toBlock' => 'latest',
            ]]);
            if (!is_array($res)) {
                return []; // mis. "limit exceeded" / RPC error → kosong yang aman
            }
            $rows = $this->decode($res);
            usort($rows, fn ($a, $b) => $b['block'] <=> $a['block']);
            return $this->withTimes(array_slice($rows, 0, $limit), $rpc);
        } catch (\Throwable $e) {
            Log::warning('TransferFeed gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Transfer MASUK & KELUAR untuk satu alamat (halaman profil Explorer).
     * Difilter di sisi node lewat topik indexed, jadi jangkauan bloknya bisa jauh lebih
     * lebar daripada feed global. Tiap baris ditambah 'direction' => in|out|self.
     */
    public function forAddress(string $address, int $limit = 30): array
    {
        if (!self::enabled() || !preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return [];
        }
        $addr = strtolower($address);
        $tlkm = strtolower(config('chain.tlkm'));
        $pad  = '0x' . str_pad(substr($addr, 2), 64, '0', STR_PAD_LEFT);

        $latest = (new ChainVerifier())->latestBlock();
        if (!$latest) {
            return [];
        }
        $rpc = config('chain.logs_rpc_url', 'https://bsc-testnet-rpc.publicnode.com');
        $topic = '0x' . Keccak::hash('Transfer(address,address,uint256)', 256);

        // Node membatasi rentang getLogs (maks 50.000 blok), jadi riwayat panjang
        // dipindai bertahap per potongan sampai cukup atau batas mundur tercapai.
        $chunk   = 45000;
        $maxBack = max($chunk, (int) config('chain.transfers_address_lookback_blocks', 250000));

        try {
            $rows = [];
            $to = $latest;
            $scanned = 0;
            while ($scanned < $maxBack && count($rows) < $limit * 3 && $to > 0) {
                $fromB = max(0, $to - $chunk);
                $range = ['fromBlock' => '0x' . dechex($fromB), 'toBlock' => '0x' . dechex($to)];

                $out = $this->rpc($rpc, 'eth_getLogs', [$range + ['address' => $tlkm, 'topics' => [$topic, $pad, null]]]);
                $in  = $this->rpc($rpc, 'eth_getLogs', [$range + ['address' => $tlkm, 'topics' => [$topic, null, $pad]]]);
                if (is_array($out)) { $rows = array_merge($rows, $this->decode($out)); }
                if (is_array($in))  { $rows = array_merge($rows, $this->decode($in)); }

                if ($fromB === 0) { break; }
                $to = $fromB - 1;
                $scanned += $chunk;
            }

            // Transfer ke diri sendiri bisa muncul di kedua query.
            $uniq = [];
            foreach ($rows as $r) {
                $uniq[$r['tx'] . ':' . $r['from'] . ':' . $r['to'] . ':' . $r['tlkm']] = $r;
            }
            $rows = array_values($uniq);
            foreach ($rows as &$r) {
                $r['direction'] = ($r['from'] === $addr && $r['to'] === $addr) ? 'self'
                    : ($r['from'] === $addr ? 'out' : 'in');
            }
            unset($r);

            usort($rows, fn ($a, $b) => $b['block'] <=> $a['block']);
            return $this->withTimes(array_slice($rows, 0, $limit), $rpc);
        } catch (\Throwable $e) {
            Log::warning('TransferFeed forAddress gagal: ' . $e->getMessage());
            return [];
        }
    }

    /** [rpc, topic, fromBlockHex|null] untuk feed global. */
    private function window(): array
    {
        $rpc   = config('chain.logs_rpc_url', 'https://bsc-testnet-rpc.publicnode.com');
        $topic = '0x' . Keccak::hash('Transfer(address,address,uint256)', 256);
        $latest = (new ChainVerifier())->latestBlock();
        if (!$latest) {
            return [$rpc, $topic, null];
        }
        $win = max(200, (int) config('chain.transfers_lookback_blocks', 5000));
        return [$rpc, $topic, '0x' . dechex(max(0, $latest - $win))];
    }

    /** Ubah log mentah jadi baris siap tampil. */
    private function decode(array $logs): array
    {
        $rows = [];
        foreach ($logs as $log) {
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
        return $rows;
    }

    /** Lengkapi waktu tiap baris dari timestamp bloknya (blok unik saja). */
    private function withTimes(array $rows, string $rpc): array
    {
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
    }

    private function rpc(string $url, string $method, array $params)
    {
        $res = Http::timeout(20)->acceptJson()->post($url, [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
        ]);
        return $res->ok() ? ($res->json()['result'] ?? null) : null;
    }
}

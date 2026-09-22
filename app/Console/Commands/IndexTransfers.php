<?php

namespace App\Console\Commands;

use App\Models\IndexerCursor;
use App\Models\TokenTransfer;
use App\Services\ChainVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use kornrunner\Keccak;

/**
 * Indeks event Transfer TLKM ke DB supaya Explorer bisa menampilkan riwayat PENUH
 * sebuah alamat (masuk dari siapa, keluar ke mana) sejak token lahir.
 *
 * Resumable: posisi terakhir disimpan di `indexer_cursors`. Node membatasi getLogs
 * 50.000 blok per query, jadi pemindaian dipecah per potongan.
 */
class IndexTransfers extends Command
{
    protected $signature = 'transfers:index {--from= : mulai dari blok ini (abaikan cursor)} {--chunk=45000} {--max-chunks=200}';
    protected $description = 'Indeks transfer TLKM on-chain ke tabel token_transfers.';

    private const CURSOR = 'tlkm_transfers';

    private ?string $lastError = null;

    public function handle(): int
    {
        $tlkm = strtolower((string) config('chain.tlkm'));
        if (!$tlkm || $tlkm === '0x0000000000000000000000000000000000000000') {
            $this->warn('TLKM_ADDRESS belum diset — indexer dilewati.');
            return self::SUCCESS;
        }

        // RPC arsip (opsional) dipakai lebih dulu: hanya node arsip yang menyimpan log lama.
        $rpc = config('chain.archive_rpc_url') ?: config('chain.logs_rpc_url', 'https://bsc-testnet-rpc.publicnode.com');
        $latest = (new ChainVerifier())->latestBlock();
        if (!$latest) {
            $this->error('Tidak bisa membaca blok terbaru.');
            return self::FAILURE;
        }

        $cursor = IndexerCursor::firstOrNew(['name' => self::CURSOR]);
        $start = $this->option('from') !== null
            ? (int) $this->option('from')
            : (int) ($cursor->block_number ?: 0);

        if (!$start) {
            // Node publik memangkas riwayat lama (termasuk eth_getCode), jadi mencari blok
            // kelahiran token hanya masuk akal bila ada RPC ARSIP. Tanpa itu, mulai dari
            // rentang terbaru yang masih dilayani — indeks tumbuh maju dari sekarang.
            $start = config('chain.archive_rpc_url')
                ? $this->birthBlock($rpc, $tlkm)
                : max(0, $latest - (int) config('chain.transfers_initial_lookback_blocks', 150000));
        }

        if ($start > $latest) {
            $this->info('Sudah mutakhir.');
            return self::SUCCESS;
        }

        $chunk  = max(1000, (int) $this->option('chunk'));
        $topic  = '0x' . Keccak::hash('Transfer(address,address,uint256)', 256);
        $from   = $start;
        $chunks  = 0;
        $saved   = 0;
        $skipped = false;

        while ($from <= $latest && $chunks < (int) $this->option('max-chunks')) {
            $to = min($latest, $from + $chunk);
            $logs = $this->getLogs($rpc, $tlkm, $topic, $from, $to);
            if ($logs === null) {
                // Node publik memangkas riwayat lama. Kalau blok yang diminta sudah dipangkas,
                // lompat ke rentang terbaru yang masih dilayani daripada berhenti total.
                if (!$skipped && $this->lastError && stripos($this->lastError, 'prun') !== false) {
                    $skipped = true;   // hanya sekali, dan HARUS maju
                    $skipTo = max($from + 1, $latest - $chunk);
                    $this->warn('Riwayat lama sudah dipangkas node (' . $this->lastError . ').');
                    $this->warn("Melompat ke blok {$skipTo}. Isi ARCHIVE_RPC_URL di .env untuk indeks penuh sejak token lahir.");
                    $from = $skipTo;
                    continue;
                }
                $this->warn("Gagal membaca blok {$from}-{$to}: " . ($this->lastError ?: 'tidak diketahui') . '; berhenti agar bisa dilanjut nanti.');
                break;
            }
            $saved += $this->store($logs, $rpc);

            $cursor->fill(['name' => self::CURSOR, 'block_number' => $to + 1])->save();
            $from = $to + 1;
            $chunks++;
        }

        $this->info("Indexer: {$chunks} potongan, {$saved} transfer baru. Cursor di blok {$cursor->block_number} (latest {$latest}).");
        return self::SUCCESS;
    }

    /** Simpan log (idempoten lewat unique tx_hash+log_index). Return jumlah baru. */
    private function store(array $logs, string $rpc): int
    {
        if (!$logs) {
            return 0;
        }
        // Ambil timestamp tiap blok unik sekali saja.
        $times = [];
        foreach (array_unique(array_map(fn ($l) => $l['blockNumber'] ?? '0x0', $logs)) as $bh) {
            $blk = $this->rpc($rpc, 'eth_getBlockByNumber', [$bh, false]);
            if (isset($blk['timestamp'])) {
                $times[$bh] = (int) hexdec($blk['timestamp']);
            }
        }

        $new = 0;
        foreach ($logs as $log) {
            $topics = $log['topics'] ?? [];
            if (count($topics) < 3) {
                continue; // butuh from & to (indexed)
            }
            $valHex = $log['data'] ?? '0x0';
            $wei = ($valHex && $valHex !== '0x') ? gmp_strval(gmp_init($valHex)) : '0';
            $bh  = $log['blockNumber'] ?? '0x0';

            $row = TokenTransfer::firstOrCreate(
                ['tx_hash' => $log['transactionHash'] ?? '', 'log_index' => hexdec($log['logIndex'] ?? '0x0')],
                [
                    'block_number' => hexdec($bh),
                    'block_time'   => isset($times[$bh]) ? now()->setTimestamp($times[$bh]) : null,
                    'from_address' => '0x' . substr($topics[1], -40),
                    'to_address'   => '0x' . substr($topics[2], -40),
                    'amount'       => bcdiv($wei, '1000000000000000000', 18),
                ]
            );
            if ($row->wasRecentlyCreated) {
                $new++;
            }
        }
        return $new;
    }

    /** Cari blok kelahiran kontrak TLKM (binary search eth_getCode). */
    private function birthBlock(string $rpc, string $tlkm): int
    {
        $latest = (new ChainVerifier())->latestBlock() ?: 0;
        $lo = 0; $hi = $latest;
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            $code = $this->rpc($rpc, 'eth_getCode', [$tlkm, '0x' . dechex($mid)]);
            if (is_string($code) && strlen($code) > 4) { $hi = $mid; } else { $lo = $mid + 1; }
        }
        $this->info("Blok kelahiran TLKM terdeteksi: {$lo}");
        return $lo;
    }

    private function getLogs(string $rpc, string $tlkm, string $topic, int $from, int $to): ?array
    {
        $res = $this->rpc($rpc, 'eth_getLogs', [[
            'address' => $tlkm, 'topics' => [$topic],
            'fromBlock' => '0x' . dechex($from), 'toBlock' => '0x' . dechex($to),
        ]]);
        return is_array($res) ? $res : null;
    }

    private function rpc(string $url, string $method, array $params)
    {
        try {
            $res = Http::timeout(25)->acceptJson()->post($url, [
                'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
            ]);
            $this->lastError = $res->json()['error']['message'] ?? ($res->ok() ? null : 'HTTP ' . $res->status());
            return ($res->ok() && isset($res->json()['result'])) ? $res->json()['result'] : null;
        } catch (\Throwable $e) {
            Log::warning('IndexTransfers RPC gagal: ' . $e->getMessage());
            return null;
        }
    }
}

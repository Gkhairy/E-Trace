<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\SepoliaVerifier;
use Illuminate\Console\Command;

/**
 * Indexer on-chain (Fase 2). Satu pass:
 *  - Naikkan confirmations tiap order & promosi status: pending_confirmation -> paid (>=6) -> finalized (>=12).
 *  - Sinkron status TIAP ITEM dari kontrak (Completed/Refunded/Disputed) — termasuk aksi arbiter,
 *    dan menambal kalau POST markItemStatus dari browser gagal.
 *  - Deteksi reorg sederhana: kalau receipt hilang/gagal, turunkan lagi ke pending_confirmation.
 *
 * Jalankan berkala (scheduler tiap menit) — lihat routes/console.php.
 * Sumber kebenaran = on-chain (event/state menang atas DB).
 */
class ChainIndex extends Command
{
    protected $signature = 'chain:index {--limit=100 : maksimum order diproses per pass}';
    protected $description = 'Sinkronkan status order/escrow dari blockchain (konfirmasi, finality, reorg).';

    public function handle(): int
    {
        $v = new SepoliaVerifier();
        $latest = $v->latestBlock();
        if (!$latest) {
            $this->warn('RPC tidak bisa dihubungi.');
            return self::FAILURE;
        }

        $paidConf  = (int) config('chain.paid_confirmations');
        $finalConf = (int) config('chain.finalized_confirmations');

        // Order yang masih perlu dipantau: belum final ATAU masih punya item non-final.
        $orders = Order::whereNotNull('block_number')
            ->where(function ($q) {
                $q->whereNull('finalized_at')
                  ->orWhereHas('items', fn ($i) => $i->whereIn('status', ['paid', 'disputed', 'pending_confirmation']));
            })
            ->with('items')
            ->latest()
            ->limit((int) $this->option('limit'))
            ->get();

        $touched = 0;

        foreach ($orders as $o) {
            // --- Reorg check: receipt harus masih ada & sukses ---
            $rc = $v->getReceipt($o->tx_hash);
            if (!$rc || ($rc['status'] ?? '') !== '0x1') {
                if ($o->status !== 'completed' && $o->status !== 'refunded') {
                    $o->status = 'pending_confirmation';
                    $o->confirmations = 0;
                    $o->finalized_at = null;
                    $o->save();
                    $touched++;
                }
                continue; // tunggu pass berikutnya
            }

            $blk  = hexdec($rc['blockNumber']);
            $conf = max(1, $latest - $blk + 1);
            $o->block_number  = $blk;
            $o->confirmations = $conf;

            // Promosi status header (jangan turunkan dari completed/refunded).
            if (!in_array($o->status, ['completed', 'refunded'], true)) {
                $o->status = $conf >= $paidConf ? 'paid' : 'pending_confirmation';
            }
            if ($conf >= $finalConf && !$o->finalized_at) {
                $o->finalized_at = now();
            }
            $o->save();

            // --- Sinkron status tiap item dari kontrak ---
            $map = [2 => 'completed', 3 => 'refunded', 4 => 'disputed'];
            foreach ($o->items as $item) {
                if (in_array($item->status, ['completed', 'refunded'], true)) {
                    continue; // sudah final
                }
                $it = $v->getItem($o->order_id, (int) $item->item_index);
                if (!$it) {
                    continue;
                }
                $want = $map[$it['status']] ?? 'paid';
                if ($item->status !== $want) {
                    $item->status = $want;
                    if ($want === 'completed') {
                        $item->fulfillment_status = 'delivered';
                        $item->delivered_at = $item->delivered_at ?? now();
                    }
                    $item->save();
                }
            }

            // Header 'completed' kalau tidak ada lagi item yang belum final.
            $hasOpen = $o->items()->whereIn('status', ['paid', 'disputed', 'pending_confirmation'])->exists();
            if (!$hasOpen && $o->status !== 'completed') {
                $o->status = 'completed';
                $o->save();
            }

            $touched++;
        }

        $this->info("chain:index selesai — block $latest, {$touched}/{$orders->count()} order disinkron.");
        return self::SUCCESS;
    }
}

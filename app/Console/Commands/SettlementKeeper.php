<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ChainSigner;
use App\Services\DeliveryAI;
use App\Support\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * KEEPER — AI Auto-Settlement + klaim Garansi Tepat Waktu (DEMO testnet).
 *
 * Untuk tiap order escrow aktif: baca riwayat tracking → DeliveryAI memutuskan
 * release/refund/hold → eksekusi on-chain sebagai ARBITER (ChainSigner). Untuk order
 * berasuransi: bila telat karena penjual/kurir & lewat tenggat → payout ongkir (di-cap)
 * dari pool ke pembeli.
 *
 * IDEMPOTEN: settlement hanya untuk order settlement_status='pending'; klaim asuransi
 * hanya untuk insurance_status='active' (maks 1 payout/order). Menghormati semua cap &
 * circuit breaker harian. Aman-nonaktif bila kontrak/kunci/pool belum dikonfigurasi.
 */
class SettlementKeeper extends Command
{
    protected $signature = 'settlement:keep {--order= : proses hanya satu order_id (demo)} {--limit=30}';
    protected $description = 'AI Auto-Settlement escrow + klaim Garansi Tepat Waktu untuk order aktif.';

    /** ABI minimal untuk aksi arbiter di PaymentGatewayV3 (Web3\Contract). */
    private const GATEWAY_ABI = [
        ['inputs' => [['name' => 'orderId', 'type' => 'string'], ['name' => 'index', 'type' => 'uint256']], 'name' => 'arbiterRelease', 'outputs' => [], 'stateMutability' => 'nonpayable', 'type' => 'function'],
        ['inputs' => [['name' => 'orderId', 'type' => 'string'], ['name' => 'index', 'type' => 'uint256']], 'name' => 'arbiterRefund', 'outputs' => [], 'stateMutability' => 'nonpayable', 'type' => 'function'],
    ];
    private const TLKM_ABI = [
        ['inputs' => [['name' => 'to', 'type' => 'address'], ['name' => 'amount', 'type' => 'uint256']], 'name' => 'transfer', 'outputs' => [['name' => '', 'type' => 'bool']], 'stateMutability' => 'nonpayable', 'type' => 'function'],
    ];

    public function handle(DeliveryAI $ai, ChainSigner $signer): int
    {
        $cfg        = config('chain.insurance');
        $minConf    = (float) config('chain.ai.min_confidence', 0.8);
        $maxAuto    = (float) config('chain.ai.max_auto_amount_tlkm', 1000);
        $grace      = (int) ($cfg['grace_days'] ?? 2);
        $gateway    = config('chain.gateway');
        $arbiterKey = config('chain.arbiter_key');
        $zero       = '0x0000000000000000000000000000000000000000';
        $gatewayOk  = $gateway && $gateway !== $zero;
        $insEnabled = (bool) ($cfg['enabled'] ?? false) && !empty($cfg['pool_wallet']);

        // Order butuh proses: settlement belum tuntas ATAU klaim asuransi masih aktif.
        $q = Order::query()->with(['items', 'trackingEvents', 'shippingAddress'])
            ->where(function ($w) {
                $w->where('settlement_status', 'pending')
                  ->orWhere(fn ($x) => $x->where('is_insured', true)->where('insurance_status', 'active'));
            });
        if ($this->option('order')) {
            $q->where('order_id', $this->option('order'));
        }
        $orders = $q->latest()->limit((int) $this->option('limit'))->get();

        $done = 0;
        foreach ($orders as $order) {
            try {
                $this->process($order, $ai, $signer, [
                    'minConf' => $minConf, 'maxAuto' => $maxAuto, 'grace' => $grace,
                    'gateway' => $gateway, 'gatewayOk' => $gatewayOk, 'arbiterKey' => $arbiterKey,
                    'insEnabled' => $insEnabled, 'cfg' => $cfg,
                ]);
                $done++;
            } catch (\Throwable $e) {
                Log::error("SettlementKeeper order {$order->order_id}: " . $e->getMessage());
            }
        }

        $this->info("Keeper selesai: {$done} order diproses.");
        return self::SUCCESS;
    }

    private function process(Order $order, DeliveryAI $ai, ChainSigner $signer, array $o): void
    {
        $paidItems = $order->items->where('status', 'paid')->values();

        // Tak ada lagi item 'paid' (mis. pembeli sudah Konfirmasi Terima manual) →
        // settlement dianggap selesai; jangan proses ulang.
        if ($order->settlement_status === 'pending' && $paidItems->isEmpty()) {
            $order->settlement_status = 'released';
            $order->save();
        }

        // ===== 0) Aturan DETERMINISTIK (timeout) — jalan lebih dulu, tanpa AI =====
        $this->applyTimeouts($order, $paidItems, $o, $signer);
        // Segarkan daftar item 'paid' setelah kemungkinan aksi timeout.
        $paidItems = $order->items()->where('status', 'paid')->get();

        // Butuh AI hanya bila masih ada yang harus diputuskan.
        $needSettle = $order->settlement_status === 'pending' && $paidItems->isNotEmpty();
        $needClaim  = $order->is_insured && $order->insurance_status === 'active';
        if (!$needSettle && !$needClaim) {
            return;
        }

        // ===== 1) Kumpulkan tracking → DeliveryAI =====
        $events = $order->trackingEvents->map(fn ($t) => [
            'at' => $t->created_at?->toIso8601String(), 'text' => $t->raw_text, 'source' => $t->source,
        ])->all();

        $shippingTlkm = (float) ($order->shipping_tlkm ?? 0);
        $promised     = $order->promised_date?->toDateString() ?? '(tidak diketahui)';
        $decision     = $ai->decide($events, $promised, (int) $o['grace'], (float) $order->total, $shippingTlkm);

        // Simpan keputusan (transparansi/audit) untuk SEMUA order.
        $order->ai_decision = $decision;
        $order->ai_reason   = $decision['reason'];
        $order->save();

        $confOk = $decision['confidence'] >= $o['minConf'];
        $auto   = $confOk && ((float) $order->total <= $o['maxAuto']);

        // ===== 2) Settlement (hanya bila masih pending & ada item paid) =====
        if ($needSettle) {
            if ($decision['settlement'] === 'hold' || !$auto) {
                $order->settlement_status = 'held';
                $order->save();
                Notify::toSupervisors('order', 'Order ditahan — perlu tinjauan',
                    "Order {$order->order_id}: AI belum yakin ({$decision['confidence']}) / di atas batas otomatis. " . $decision['reason'],
                    '/supervisor/held', '🕵️');
            } elseif (in_array($decision['settlement'], ['release', 'refund'], true)) {
                if (!$o['gatewayOk'] || empty($o['arbiterKey'])) {
                    // Tak bisa eksekusi on-chain (kontrak/kunci arbiter belum siap) → tahan.
                    $order->settlement_status = 'held';
                    $order->save();
                    Notify::toSupervisors('order', 'Order ditahan — arbiter belum siap',
                        "Order {$order->order_id}: auto-settlement mati (gateway/kunci arbiter kosong).", '/supervisor/held', '⚙️');
                } else {
                    $method = $decision['settlement'] === 'release' ? 'arbiterRelease' : 'arbiterRefund';
                    $newItemStatus = $decision['settlement'] === 'release' ? 'completed' : 'refunded';
                    $ok = true;
                    foreach ($paidItems as $it) {
                        try {
                            $signer->sendContractCall($o['arbiterKey'], $o['gateway'], self::GATEWAY_ABI, $method, [$order->order_id, (int) $it->item_index]);
                            $it->status = $newItemStatus;
                            if ($newItemStatus === 'completed') {
                                $it->fulfillment_status = 'delivered';
                                $it->delivered_at = $it->delivered_at ?? now();
                            }
                            $it->save();
                        } catch (\Throwable $e) {
                            $ok = false;
                            Log::error("Keeper {$method} {$order->order_id}#{$it->item_index}: " . $e->getMessage());
                        }
                    }
                    if ($ok) {
                        $order->settlement_status = $decision['settlement'] === 'release' ? 'released' : 'refunded';
                        if (!OrderItem::where('order_ref_id', $order->id)->whereIn('status', ['paid', 'disputed', 'pending_confirmation'])->exists()) {
                            $order->status = 'completed';
                        }
                        $order->save();
                        Notify::send($order->user_id, 'order',
                            $decision['settlement'] === 'release' ? 'Pesanan diselesaikan otomatis oleh AI' : 'Dana dikembalikan otomatis oleh AI',
                            $decision['reason'], '/orders', '🤖');
                    } else {
                        $order->settlement_status = 'held';
                        $order->save();
                        Notify::toSupervisors('order', 'Eksekusi settlement gagal',
                            "Order {$order->order_id}: aksi arbiter on-chain gagal, perlu tinjauan manual.", '/supervisor/held', '⚠️');
                    }
                }
            }
        }

        // ===== 3) Klaim asuransi (hanya is_insured & active) =====
        if ($needClaim) {
            $this->evaluateClaim($order, $decision, $signer, $o, $confOk);
        }
    }

    /**
     * Aturan penyelesaian DETERMINISTIK (tanpa AI):
     *  (a) Penjual tak mengirim dalam N hari → auto-REFUND (tak bergantung jarak).
     *  (b) Barang DITERIMA tapi pembeli tak konfirmasi M hari → auto-SELESAI, KECUALI
     *      tujuan JAUH (eta_days >= far_eta_days) → dilewati agar pembeli remote punya
     *      waktu lebih (konfirmasi manual / lewat pengawas).
     */
    private function applyTimeouts(Order $order, $paidItems, array $o, ChainSigner $signer): void
    {
        if ($order->settlement_status !== 'pending' || $paidItems->isEmpty()) {
            return;
        }
        if (!$o['gatewayOk'] || empty($o['arbiterKey'])) {
            return; // eksekusi on-chain butuh gateway + kunci arbiter
        }

        $shipDeadline = (int) config('chain.settlement.ship_deadline_days', 3);
        $autoComplete = (int) config('chain.settlement.auto_complete_days', 3);
        $farEta       = (int) config('chain.settlement.far_eta_days', 10);
        $isFar        = $farEta > 0 && (int) ($order->eta_days ?? 0) >= $farEta;
        $now = now();
        $acted = false;

        foreach ($paidItems as $it) {
            $notShipped = in_array($it->fulfillment_status, [null, 'pending', 'processing'], true);

            // (a) Tak dikirim dalam N hari → refund.
            if ($shipDeadline > 0 && $notShipped && $order->created_at->copy()->addDays($shipDeadline)->lt($now)) {
                if ($this->arbiterItem($order, $it, false, $o, $signer)) {
                    $acted = true;
                    Notify::send($order->user_id, 'order', 'Dana dikembalikan otomatis',
                        "Penjual tak mengirim dalam {$shipDeadline} hari — dana item dikembalikan.", '/orders', '↩️');
                }
                continue;
            }

            // (b) Sudah diterima & tak dikonfirmasi M hari → selesai (kecuali tujuan jauh).
            if ($autoComplete > 0 && !$isFar && $it->fulfillment_status === 'delivered' && $it->delivered_at
                && $it->delivered_at->copy()->addDays($autoComplete)->lt($now)) {
                if ($this->arbiterItem($order, $it, true, $o, $signer)) {
                    $acted = true;
                    Notify::send($order->user_id, 'order', 'Pesanan diselesaikan otomatis',
                        "Barang sudah diterima & tak dikonfirmasi {$autoComplete} hari — dana dilepas ke penjual.", '/orders', '✅');
                }
            }
        }

        if (!$acted) {
            return;
        }
        // Bila tak ada lagi item 'paid', tandai order selesai (refunded bila semua refund).
        if (!OrderItem::where('order_ref_id', $order->id)->where('status', 'paid')->exists()) {
            $statuses = OrderItem::where('order_ref_id', $order->id)->pluck('status')->unique();
            $order->settlement_status = ($statuses->count() === 1 && $statuses->first() === 'refunded') ? 'refunded' : 'released';
            if (!OrderItem::where('order_ref_id', $order->id)->whereIn('status', ['paid', 'disputed', 'pending_confirmation'])->exists()) {
                $order->status = 'completed';
            }
            $order->ai_reason = 'Diselesaikan otomatis oleh aturan waktu (timeout).';
            $order->save();
        }
    }

    /** Aksi arbiter on-chain untuk satu item + update status DB. Return true bila sukses. */
    private function arbiterItem(Order $order, OrderItem $it, bool $release, array $o, ChainSigner $signer): bool
    {
        $method = $release ? 'arbiterRelease' : 'arbiterRefund';
        try {
            $signer->sendContractCall($o['arbiterKey'], $o['gateway'], self::GATEWAY_ABI, $method, [$order->order_id, (int) $it->item_index]);
            $it->status = $release ? 'completed' : 'refunded';
            if ($release) {
                $it->fulfillment_status = 'delivered';
                $it->delivered_at = $it->delivered_at ?? now();
            }
            $it->save();
            return true;
        } catch (\Throwable $e) {
            Log::error("Keeper {$method} {$order->order_id}#{$it->item_index}: " . $e->getMessage());
            return false;
        }
    }

    /** Nilai & (jika layak) bayar klaim Garansi Tepat Waktu. Idempoten + circuit breaker. */
    private function evaluateClaim(Order $order, array $decision, ChainSigner $signer, array $o, bool $confOk): void
    {
        $cfg = $o['cfg'];
        $now = now();
        $pastDue = $order->promised_date && $now->gt($order->promised_date->copy()->addDays((int) $o['grace']));

        $eligible = $decision['late'] && $decision['late_cause'] === 'seller_courier' && $pastDue && $confOk;

        if ($eligible) {
            if (!$o['insEnabled'] || empty($cfg['pool_key'])) {
                Log::info("Klaim {$order->order_id}: layak tapi pool/kunci belum siap — ditunda.");
                return; // biarkan 'active', dicoba lagi lain waktu
            }
            $payout = min((float) ($order->shipping_tlkm ?? 0), (float) $cfg['payout_cap_tlkm']);
            if ($payout <= 0) {
                $order->insurance_status = 'rejected';
                $order->ai_reason = 'Ongkir nol / tak diketahui — tidak ada yang dikompensasi.';
                $order->save();
                return;
            }
            // Circuit breaker harian.
            $todayPaid = (float) Order::whereDate('updated_at', today())->where('insurance_status', 'paid')->sum('payout_tlkm');
            if ($todayPaid + $payout > (float) $cfg['daily_payout_cap_tlkm']) {
                Log::warning("Circuit breaker: payout harian tercapai. Klaim {$order->order_id} ditunda.");
                Notify::toSupervisors('order', 'Circuit breaker klaim aktif',
                    "Batas payout harian ({$cfg['daily_payout_cap_tlkm']} TLKM) tercapai. Klaim {$order->order_id} ditunda.", '/supervisor/held', '⛔');
                return; // tetap 'active' untuk dicoba besok
            }

            $buyer = strtolower((string) $order->user?->wallet_address);
            if (!preg_match('/^0x[a-f0-9]{40}$/', $buyer)) {
                Log::warning("Klaim {$order->order_id}: wallet pembeli tak valid.");
                return;
            }
            try {
                $amountWei = $signer->toWei((string) $payout);
                $tx = $signer->sendContractCall($cfg['pool_key'], config('chain.tlkm'), self::TLKM_ABI, 'transfer', [$buyer, $amountWei]);
                $order->insurance_status = 'paid';
                $order->payout_tx   = $tx;
                $order->payout_tlkm = $payout;
                $order->ai_reason   = 'Klaim garansi dibayar: telat karena penjual/kurir. ' . $decision['reason'];
                $order->save();
                Notify::send($order->user_id, 'order', 'Klaim Garansi Tepat Waktu dibayar',
                    "Kompensasi ongkir {$payout} TLKM sudah dikirim ke walletmu.", '/orders', '🛡️');
            } catch (\Throwable $e) {
                Log::error("Payout klaim {$order->order_id}: " . $e->getMessage());
            }
            return;
        }

        // Tidak layak → tentukan tolak vs tunggu.
        if ($decision['delivered'] && !$decision['late']) {
            $order->insurance_status = 'rejected';
            $order->save();
        } elseif (in_array($decision['late_cause'], ['buyer', 'force_majeure'], true) && ($decision['late'] || $decision['delivered'])) {
            $order->insurance_status = 'rejected';
            $order->save();
        }
        // selain itu (masih berjalan / belum jatuh tempo / ambigu) → biarkan 'active'.
    }
}

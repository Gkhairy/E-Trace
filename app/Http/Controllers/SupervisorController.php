<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\WalletLabel;
use App\Services\ChainSigner;
use App\Services\ChainVerifier;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupervisorController extends Controller
{
    /** ABI minimal untuk aksi arbiter & payout (Web3\Contract). */
    private const GATEWAY_ABI = [
        ['inputs' => [['name' => 'orderId', 'type' => 'string'], ['name' => 'index', 'type' => 'uint256']], 'name' => 'arbiterRelease', 'outputs' => [], 'stateMutability' => 'nonpayable', 'type' => 'function'],
        ['inputs' => [['name' => 'orderId', 'type' => 'string'], ['name' => 'index', 'type' => 'uint256']], 'name' => 'arbiterRefund', 'outputs' => [], 'stateMutability' => 'nonpayable', 'type' => 'function'],
    ];
    private const TLKM_ABI = [
        ['inputs' => [['name' => 'to', 'type' => 'address'], ['name' => 'amount', 'type' => 'uint256']], 'name' => 'transfer', 'outputs' => [['name' => '', 'type' => 'bool']], 'stateMutability' => 'nonpayable', 'type' => 'function'],
    ];

    private function ensure()
    {
        abort_unless(auth()->user()->isSupervisor(), 403, 'Khusus pengawas platform.');
    }

    /** Daftar order 'held' (AI belum yakin / di atas batas otomatis) untuk ditinjau manual. */
    public function held()
    {
        $this->ensure();
        $orders = Order::with(['items', 'user', 'trackingEvents', 'shippingAddress'])
            ->where('settlement_status', 'held')
            ->orWhere(fn ($q) => $q->where('is_insured', true)->where('insurance_status', 'active'))
            ->latest()->get();
        return view('supervisor.held', compact('orders'));
    }

    /**
     * Putusan MANUAL pengawas atas order 'held': release/refund (arbiter on-chain) atau
     * approve/reject klaim garansi. Eksekusi server-side via ChainSigner (kunci arbiter/pool).
     */
    public function settle(Request $req, ChainSigner $signer)
    {
        $this->ensure();
        $data = $req->validate([
            'order_id' => 'required|string',
            'action'   => 'required|in:release,refund,approve_claim,reject_claim',
        ]);
        $order = Order::with('items', 'user')->where('order_id', $data['order_id'])->firstOrFail();

        $gateway    = config('chain.gateway');
        $arbiterKey = config('chain.arbiter_key');
        $zero       = '0x0000000000000000000000000000000000000000';

        if (in_array($data['action'], ['release', 'refund'], true)) {
            if (!$gateway || $gateway === $zero || empty($arbiterKey)) {
                return response()->json(['success' => false, 'message' => 'Gateway / kunci arbiter belum dikonfigurasi.'], 422);
            }
            $method = $data['action'] === 'release' ? 'arbiterRelease' : 'arbiterRefund';
            $newStatus = $data['action'] === 'release' ? 'completed' : 'refunded';
            foreach ($order->items->where('status', 'paid') as $it) {
                try {
                    $signer->sendContractCall($arbiterKey, $gateway, self::GATEWAY_ABI, $method, [$order->order_id, (int) $it->item_index]);
                    $it->status = $newStatus;
                    if ($newStatus === 'completed') { $it->fulfillment_status = 'delivered'; $it->delivered_at = $it->delivered_at ?? now(); }
                    $it->save();
                } catch (\Throwable $e) {
                    Log::error("Supervisor {$method} {$order->order_id}#{$it->item_index}: " . $e->getMessage());
                    return response()->json(['success' => false, 'message' => 'Aksi on-chain gagal: ' . $e->getMessage()], 422);
                }
            }
            $order->settlement_status = $data['action'] === 'release' ? 'released' : 'refunded';
            if (!OrderItem::where('order_ref_id', $order->id)->whereIn('status', ['paid', 'disputed', 'pending_confirmation'])->exists()) {
                $order->status = 'completed';
            }
            $order->ai_reason = 'Diputus manual oleh pengawas: ' . $data['action'] . '.';
            $order->save();
            return response()->json(['success' => true, 'settlement_status' => $order->settlement_status]);
        }

        // ===== Klaim garansi manual =====
        if ($data['action'] === 'reject_claim') {
            $order->insurance_status = 'rejected';
            $order->ai_reason = 'Klaim ditolak manual oleh pengawas.';
            $order->save();
            return response()->json(['success' => true, 'insurance_status' => 'rejected']);
        }

        // approve_claim
        $cfg = config('chain.insurance');
        if (!$order->is_insured || $order->insurance_status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Klaim tidak dalam status aktif.'], 422);
        }
        if (empty($cfg['pool_key']) || empty($cfg['pool_wallet'])) {
            return response()->json(['success' => false, 'message' => 'Pool asuransi belum dikonfigurasi.'], 422);
        }
        $payout = min((float) ($order->shipping_tlkm ?? 0), (float) $cfg['payout_cap_tlkm']);
        if ($payout <= 0) {
            return response()->json(['success' => false, 'message' => 'Ongkir nol — tak ada yang dikompensasi.'], 422);
        }
        $buyer = strtolower((string) $order->user?->wallet_address);
        if (!preg_match('/^0x[a-f0-9]{40}$/', $buyer)) {
            return response()->json(['success' => false, 'message' => 'Wallet pembeli tidak valid.'], 422);
        }
        try {
            $tx = $signer->sendContractCall($cfg['pool_key'], config('chain.tlkm'), self::TLKM_ABI, 'transfer', [$buyer, $signer->toWei((string) $payout)]);
            $order->insurance_status = 'paid';
            $order->payout_tx   = $tx;
            $order->payout_tlkm = $payout;
            $order->ai_reason   = 'Klaim disetujui manual oleh pengawas — kompensasi ongkir dibayar.';
            $order->save();
            Notify::send($order->user_id, 'order', 'Klaim Garansi Tepat Waktu dibayar',
                "Kompensasi ongkir {$payout} TLKM sudah dikirim ke walletmu.", '/orders', '🛡️');
            return response()->json(['success' => true, 'payout_tx' => $tx, 'payout_tlkm' => $payout]);
        } catch (\Throwable $e) {
            Log::error("Supervisor payout {$order->order_id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Payout gagal: ' . $e->getMessage()], 422);
        }
    }

    public function disputes()
    {
        $this->ensure();

        $items = OrderItem::with(['order.shippingAddress', 'order.user', 'product.store'])
            ->where('status', 'disputed')
            ->latest()
            ->get();

        return view('supervisor.disputes', compact('items'));
    }

    /**
     * Catat hasil putusan pengawas SETELAH aksi arbiter on-chain (arbiterRelease/refund).
     * Sumber kebenaran = kontrak: hanya update DB kalau status on-chain sudah sesuai.
     */
    public function resolve(Request $req)
    {
        $this->ensure();

        $data = $req->validate([
            'order_item_id' => 'required|integer',
            'status'        => 'required|in:completed,refunded',
        ]);

        $item = OrderItem::with('order')->find($data['order_item_id']);
        if (!$item || !$item->order) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan.'], 404);
        }

        $onchain = (new ChainVerifier())->getItem($item->order->order_id, (int) $item->item_index);
        if (!$onchain) {
            return response()->json(['success' => false, 'message' => 'Item tidak terbaca di kontrak.'], 422);
        }
        $expect = $data['status'] === 'completed' ? 2 : 3;
        if ($onchain['status'] !== $expect) {
            return response()->json(['success' => false, 'message' => 'Status on-chain belum sesuai.'], 422);
        }

        $item->status = $data['status'];
        if ($data['status'] === 'completed') {
            $item->fulfillment_status = 'delivered';
            $item->delivered_at = $item->delivered_at ?? now();
        }
        $item->save();

        $order = $item->order;
        if (!OrderItem::where('order_ref_id', $order->id)->whereIn('status', ['paid', 'disputed', 'pending_confirmation'])->exists()) {
            $order->status = 'completed';
            $order->save();
        }

        return response()->json(['success' => true, 'status' => $item->status]);
    }

    // ===== LABEL ENTITAS (ala Arkham) — hanya pengawas =====
    public function labels()
    {
        $this->ensure();
        $labels = WalletLabel::latest()->get();
        return view('supervisor.labels', compact('labels'));
    }

    public function labelStore(Request $req)
    {
        $this->ensure();
        $data = $req->validate([
            'address'  => 'required|regex:/^0x[a-fA-F0-9]{40}$/',
            'label'    => 'required|string|max:100',
            'category' => 'nullable|string|max:50',
            'notes'    => 'nullable|string|max:255',
        ]);
        WalletLabel::updateOrCreate(
            ['address' => strtolower($data['address'])],
            ['label' => $data['label'], 'category' => $data['category'] ?? null, 'notes' => $data['notes'] ?? null, 'verified' => true]
        );
        return back()->with('success', 'Label entitas disimpan.');
    }

    public function labelDelete(Request $req)
    {
        $this->ensure();
        WalletLabel::where('id', $req->id)->delete();
        return back()->with('success', 'Label dihapus.');
    }
}

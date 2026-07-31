<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\WalletLabel;
use App\Services\SepoliaVerifier;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    private function ensure()
    {
        abort_unless(auth()->user()->isSupervisor(), 403, 'Khusus pengawas platform.');
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

        $onchain = (new SepoliaVerifier())->getItem($item->order->order_id, (int) $item->item_index);
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

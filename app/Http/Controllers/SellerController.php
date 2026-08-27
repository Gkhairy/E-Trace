<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Jobs\SendShipped;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SellerController extends Controller
{
    private function store()
    {
        $user = auth()->user();
        abort_unless($user->isSeller(), 403, 'Hanya penjual yang bisa mengakses dashboard toko.');
        $store = $user->store;
        abort_unless($store, 404, 'Toko belum tersedia untuk akun ini.');
        return $store;
    }

    public function dashboard()
    {
        $store = $this->store();

        $products = $store->products()->latest()->get();

        // Order masuk = item yang penjualnya toko ini (per wallet payout).
        $items = OrderItem::with(['order.shippingAddress', 'product'])
            ->where('seller_wallet', $store->payout_wallet)
            ->latest()
            ->get();

        // Biaya & pajak sisi PENJUAL (H8/H9) — TIDAK ditampilkan ke pembeli.
        $feeBps = (int) config('chain.platform_fee_bps'); // 100 = 1%
        $vatBps = (int) config('chain.vat_bps');          // 1100 = 11% (atas fee)
        $grossCompleted = (float) $items->where('status', 'completed')->sum('amount');
        $feeTotal = $grossCompleted * $feeBps / 10000;
        $vatTotal = $feeTotal * $vatBps / 10000;          // PPN dihitung atas fee jasa platform

        $stats = [
            'products'      => $products->count(),
            'orders'        => $items->count(),
            'escrow_active' => $items->where('status', 'paid')->sum('amount'),                       // ditahan escrow
            'gross'         => $grossCompleted,                                                       // penjualan bruto (selesai)
            'fee'           => $feeTotal,                                                             // fee platform
            'vat'           => $vatTotal,                                                             // PPN atas fee
            'released_net'  => $grossCompleted - $feeTotal,                                           // diterima on-chain (bruto - fee)
            'refunded'      => $items->where('status', 'refunded')->sum('amount'),
            'fee_pct'       => $feeBps / 100,
            'vat_pct'       => $vatBps / 100,
        ];

        return view('seller.dashboard', compact('store', 'products', 'items', 'stats'));
    }

    public function editStore()
    {
        $store = $this->store();
        return view('seller.store-edit', compact('store'));
    }

    public function updateStore(Request $req)
    {
        $store = $this->store();

        $data = $req->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'origin_address' => 'nullable|string|max:500',
            'contact_email'  => 'nullable|email|max:255',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $store->name           = $data['name'];
        $store->description    = $data['description'] ?? null;
        $store->origin_address = $data['origin_address'] ?? null;
        $store->contact_email  = $data['contact_email'] ?? null;

        foreach (['logo', 'banner'] as $field) {
            if ($req->hasFile($field)) {
                $name = Str::uuid() . '.' . $req->file($field)->getClientOriginalExtension();
                $req->file($field)->move(public_path('store_images'), $name);
                $store->{$field} = $name;
            }
        }

        $store->save();

        return redirect('/seller')->with('success', 'Profil toko diperbarui.');
    }

    /**
     * Update status pengiriman item (logistik off-chain).
     * process: pending -> processing. ship: -> shipped (+ resi).
     */
    public function fulfill(Request $req)
    {
        $store = $this->store();

        $data = $req->validate([
            'item_id'         => 'required|integer',
            'action'          => 'required|in:process,ship',
            'tracking_number' => 'required_if:action,ship|nullable|string|max:100',
            'courier'         => 'nullable|string|max:60',
        ]);

        // Hanya item milik toko ini (cocokkan wallet payout).
        $item = OrderItem::where('id', $data['item_id'])
            ->where('seller_wallet', $store->payout_wallet)
            ->first();
        abort_unless($item, 404, 'Item tidak ditemukan.');

        // Hanya boleh kirim kalau pembayaran (escrow) sudah terkonfirmasi.
        if ($item->status !== 'paid') {
            return back()->with('error', 'Pesanan belum bisa diproses (pembayaran belum terkonfirmasi atau sudah selesai/refund).');
        }

        if ($data['action'] === 'process') {
            if ($item->fulfillment_status === 'pending') {
                $item->fulfillment_status = 'processing';
                $item->save();
            }
        } else { // ship
            $item->fulfillment_status = 'shipped';
            $item->tracking_number    = $data['tracking_number'];
            $item->courier            = $data['courier'] ?? null;
            $item->shipped_at         = now();
            $item->save();

            // Notifikasi email ke pembeli (via queue). Best-effort.
            try {
                SendShipped::dispatch($item->id);
            } catch (\Throwable $e) {
                Log::warning('Gagal dispatch email dikirim item '.$item->id.': '.$e->getMessage());
            }

            // Notifikasi in-app ke pembeli: pesanan dikirim (+ resi).
            $buyerId = optional($item->order)->user_id;
            $prodName = optional($item->product)->name ?: 'Produk';
            $resi = $item->tracking_number ? (' Resi: ' . $item->tracking_number . ($item->courier ? ' (' . $item->courier . ')' : '') . '.') : '';
            \App\Support\Notify::send($buyerId, 'order', 'Pesanan dikirim',
                "\"{$prodName}\" sedang dikirim ke alamatmu.{$resi}", '/orders', '🚚');
        }

        return back()->with('success', 'Status pengiriman diperbarui.');
    }
}


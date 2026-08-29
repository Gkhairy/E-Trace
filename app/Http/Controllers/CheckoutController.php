<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Store;
use App\Services\ShippingService;

class CheckoutController extends Controller
{
    // Halaman checkout: ringkasan cart dikelompokkan per penjual + form alamat.
    public function index(ShippingService $shipping)
    {
        $items = CartItem::with('product')
            ->where('user_id', auth()->id())
            ->get();

        if ($items->isEmpty()) {
            return redirect('/cart')->with('success', 'Keranjang masih kosong.');
        }

        // Kelompokkan per penjual (seller_wallet).
        $groups = $items->groupBy(fn ($it) => $it->product->seller_wallet ?? '-');

        $total = $items->sum(fn ($it) => (float) $it->product->price_usdc * $it->quantity);

        // Buku alamat: alamat tersimpan user (default dulu, lalu terbaru) untuk dipilih.
        $addresses = \App\Models\ShippingAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')->latest()->get();
        $lastAddress = $addresses->first();

        // H7: estimasi ongkir per penjual (J&T bila ada; fallback jarak). Dalam TLKM.
        $buyerCity = $lastAddress->city ?? null;
        $shipEstimates = [];   // seller_wallet => ['fee_tlkm'=>, 'km'=>, 'method'=>, 'store'=>]
        $shipTotalTlkm = 0;
        foreach ($groups as $seller => $g) {
            $store = Store::where('payout_wallet', $seller)->first();
            $est = $shipping->estimate($store?->origin_address, $buyerCity);
            $shipEstimates[$seller] = $est + ['store' => $store?->name, 'origin' => $store?->origin_address];
            $shipTotalTlkm += $est['fee_tlkm'];
        }

        return view('checkout.index', compact(
            'items', 'groups', 'total', 'addresses', 'lastAddress',
            'shipEstimates', 'shipTotalTlkm', 'buyerCity'
        ));
    }
}

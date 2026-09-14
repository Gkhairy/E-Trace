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
        $etaMax = 1;
        foreach ($groups as $seller => $g) {
            $store = Store::where('payout_wallet', $seller)->first();
            $est = $shipping->estimate($store?->origin_address, $buyerCity);
            $shipEstimates[$seller] = $est + ['store' => $store?->name, 'origin' => $store?->origin_address];
            $shipTotalTlkm += $est['fee_tlkm'];
            $etaMax = max($etaMax, $shipping->etaDays($store?->origin_address, $buyerCity));
        }

        // Garansi Tepat Waktu (asuransi parametrik) — aman-nonaktif bila belum dikonfigurasi.
        $ins = config('chain.insurance');
        $bufferDays = (int) ($ins['eta_buffer_days'] ?? 3);
        $insurance = [
            'enabled'         => (bool) ($ins['enabled'] ?? false) && !empty($ins['pool_wallet']),
            'premium_tlkm'    => (float) ($ins['premium_tlkm'] ?? 2),
            'pool_wallet'     => $ins['pool_wallet'] ?? null,
            'grace_days'      => (int) ($ins['grace_days'] ?? 2),
            'payout_cap_tlkm' => (float) ($ins['payout_cap_tlkm'] ?? 30),
            'promised_days'   => $etaMax + $bufferDays,
            'promised_date'   => now()->addDays($etaMax + $bufferDays),
        ];

        return view('checkout.index', compact(
            'items', 'groups', 'total', 'addresses', 'lastAddress',
            'shipEstimates', 'shipTotalTlkm', 'buyerCity', 'insurance'
        ));
    }
}

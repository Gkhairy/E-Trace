<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Store;
use App\Services\ShippingService;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Hitung ulang estimasi ongkir (TLKM) ke sebuah kota, per penjual, dari cart user.
     * Dipakai checkout agar ongkir ikut berubah saat alamat/kota diganti.
     */
    public function quote(Request $request, ShippingService $shipping)
    {
        $data = $request->validate(['city' => 'nullable|string|max:120']);
        $buyerCity = trim((string) ($data['city'] ?? ''));

        $items = CartItem::with('product')->where('user_id', auth()->id())->get();
        if ($items->isEmpty()) {
            return response()->json(['total_tlkm' => 0, 'sellers' => []]);
        }

        $groups = $items->groupBy(fn ($it) => $it->product->seller_wallet ?? '-');

        $sellers = [];
        $total = 0.0;
        foreach ($groups as $seller => $g) {
            $store = Store::where('payout_wallet', $seller)->first();
            $est = $shipping->estimate($store?->origin_address, $buyerCity ?: null);
            $sellers[$seller] = [
                'fee_tlkm' => $est['fee_tlkm'],
                'method'   => $est['method'],
                'km'       => $est['km'],
            ];
            $total += $est['fee_tlkm'];
        }

        return response()->json([
            'total_tlkm' => round($total, 2),
            'sellers'    => $sellers,
        ]);
    }
}

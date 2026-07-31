<?php

namespace App\Http\Controllers;

use App\Models\CartItem;

class CheckoutController extends Controller
{
    // Halaman checkout: ringkasan cart dikelompokkan per penjual + form alamat.
    public function index()
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

        // Alamat terakhir (kalau ada) untuk prefill form.
        $lastAddress = \App\Models\ShippingAddress::where('user_id', auth()->id())->latest()->first();

        return view('checkout.index', compact('items', 'groups', 'total', 'lastAddress'));
    }
}

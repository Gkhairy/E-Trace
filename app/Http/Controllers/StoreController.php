<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Order;
use App\Support\Identity;

class StoreController extends Controller
{
    /** Halaman profil toko (publik) + produk yang dijual. */
    public function show(string $slug)
    {
        $store = Store::where('slug', $slug)->firstOrFail();

        $products = $store->products()->latest()->get();

        // Statistik ringkas.
        $sold = Order::whereHas('items', fn ($q) => $q->where('seller_wallet', $store->payout_wallet)->where('status', 'completed'))->count();

        $identity = Identity::resolve($store->payout_wallet); // untuk badge terverifikasi bila ada label

        return view('stores.show', compact('store', 'products', 'sold', 'identity'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Order;
use App\Models\Review;
use App\Support\Identity;

class StoreController extends Controller
{
    /** Halaman profil toko (publik) + produk yang dijual + ulasan gabungan. */
    public function show(string $slug)
    {
        $store = Store::where('slug', $slug)->firstOrFail();

        $productCount = $store->products()->count();
        $products = $store->products()->with('category')->latest()->paginate(20);

        // Statistik ringkas.
        $sold = Order::whereHas('items', fn ($q) => $q->where('seller_wallet', $store->payout_wallet)->where('status', 'completed'))->count();

        $identity = Identity::resolve($store->payout_wallet); // untuk badge terverifikasi bila ada label

        // F1: ulasan gabungan dari SEMUA produk toko (10 terbaru) + ringkasan rating.
        $reviewStats = Review::where('store_id', $store->id)
            ->selectRaw('AVG(rating) avg, COUNT(*) c')->first();
        $ratingAvg   = $reviewStats && $reviewStats->c ? round((float) $reviewStats->avg, 1) : null;
        $ratingCount = (int) ($reviewStats->c ?? 0);

        $reviews = Review::where('store_id', $store->id)
            ->with(['user', 'product'])
            ->latest()->limit(10)->get()
            ->map(fn ($r) => [
                'reviewer' => Identity::resolve(strtolower(optional($r->user)->wallet_address ?? ''))['name'] ?? 'Pembeli',
                'rating'   => (int) $r->rating,
                'comment'  => $r->comment,
                'product'  => $r->product,
                'at'       => $r->created_at,
            ]);

        return view('stores.show', compact('store', 'products', 'productCount', 'sold', 'identity', 'reviews', 'ratingAvg', 'ratingCount'));
    }
}

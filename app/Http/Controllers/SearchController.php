<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Support\Identity;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** Pencarian gabungan: produk, toko, dan orang (nama publik / wallet). */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $products = collect();
        $stores = collect();
        $people = collect();

        if ($q !== '') {
            $like = '%' . $q . '%';

            $products = Product::with(['store', 'category'])
                ->where('name', 'like', $like)
                ->latest()->limit(30)->get();

            $stores = Store::where('name', 'like', $like)
                ->withCount('products')->limit(12)->get();

            // Orang: cocokkan wallet (siapa saja) ATAU nama publik (hanya yang publik).
            // JANGAN pernah cari/paparkan nama asli, email, atau telepon.
            $people = User::where(function ($w) use ($like) {
                    $w->where('wallet_address', 'like', $like)
                      ->orWhere(function ($x) use ($like) {
                          $x->where('explorer_public', true)->where('public_name', 'like', $like);
                      });
                })
                ->whereNotNull('wallet_address')
                ->limit(12)->get()
                ->map(function ($u) {
                    $id = Identity::resolve($u->wallet_address);
                    return [
                        'name'   => $id['name'],
                        'wallet' => $u->wallet_address,
                        'verified' => $id['verified'] ?? false,
                    ];
                });
        }

        return view('search.index', compact('q', 'products', 'stores', 'people'));
    }
}

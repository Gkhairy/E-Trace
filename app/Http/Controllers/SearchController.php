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
            $like = '%' . $this->escapeLike($q) . '%';

            $products = Product::with(['store', 'category'])
                ->where('name', 'like', $like)
                ->latest()->limit(30)->get();

            $stores = Store::where('name', 'like', $like)
                ->withCount('products')->limit(12)->get();

            // Orang: cocokkan wallet (siapa saja) ATAU nama publik (hanya yang publik).
            // JANGAN pernah cari/paparkan nama asli, email, atau telepon.
            //
            // Wallet hanya dicocokkan bila kueri memang berbentuk potongan alamat
            // (0x + minimal 6 hex). Dulu "%" atau "0" saja sudah mendaftar SEMUA user
            // terdaftar, termasuk yang memilih tidak tampil publik.
            $walletPrefix = preg_match('/^0x[a-fA-F0-9]{6,40}$/', $q) ? strtolower($q) . '%' : null;
            $people = User::where(function ($w) use ($like, $walletPrefix) {
                    if ($walletPrefix) {
                        $w->where('wallet_address', 'like', $walletPrefix);
                    }
                    $w->orWhere(function ($x) use ($like) {
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

    /** Saran cepat (autocomplete) untuk kotak pencarian — maks 6 produk. JSON ringan. */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['products' => [], 'q' => $q]);
        }

        $products = Product::with('store')
            ->where('name', 'like', '%' . $this->escapeLike($q) . '%')
            ->limit(6)->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => rtrim(rtrim(number_format($p->price_usdc, 2), '0'), '.'),
                'image' => $p->imageUrl(),
                'store' => $p->store->name ?? null,
                'url'   => url('/products/' . $p->id),
            ]);

        return response()->json(['products' => $products, 'q' => $q]);
    }

    /** Perlakukan % dan _ dari pengguna sebagai teks biasa, bukan wildcard LIKE. */
    private function escapeLike(string $v): string
    {
        return addcslashes($v, '\\%_');
    }
}

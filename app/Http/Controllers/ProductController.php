<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', [
            'products' => Product::with('store')->latest()->get()
        ]);
    }

    public function show($id)
    {
        return view('products.show', [
            'product' => Product::with(['store', 'reviews.user'])->findOrFail($id)
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->isSeller(), 403, 'Hanya penjual yang bisa menambah produk.');
        return view('products.create');
    }

    public function store(Request $req)
    {
        abort_unless(auth()->user()->isSeller(), 403, 'Hanya penjual yang bisa menambah produk.');

        $store = auth()->user()->store;
        abort_unless($store, 403, 'Toko belum tersedia untuk akun ini.');

        $req->validate([
            'name' => 'required',
            'price_usdc' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $imageName = null;
        if ($req->hasFile('image')) {
            // Nama file acak (hindari nama dari klien / overwrite).
            $imageName = Str::uuid() . '.' . $req->image->getClientOriginalExtension();
            $req->image->move(public_path('product_images'), $imageName);
        }

        Product::create([
            'name'          => $req->name,
            'description'   => $req->description,
            'price_usdc'    => $req->price_usdc,
            'stock'         => $req->filled('stock') ? (int) $req->stock : null, // kosong = tak dibatasi
            'store_id'      => $store->id,
            'seller_wallet' => $store->payout_wallet, // dana escrow -> wallet payout toko
            'product_id'    => Str::uuid(),
            'image'         => $imageName,
        ]);

        return redirect('/products')->with('success', 'Produk berhasil ditambahkan!');
    }


}

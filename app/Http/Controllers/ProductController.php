<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Filter kategori (G1) via ?category=slug.
        $categories = Category::orderBy('sort')->get();
        $active = $request->query('category');
        $activeCategory = $active ? $categories->firstWhere('slug', $active) : null;

        $products = Product::with(['store', 'category'])
            ->when($activeCategory, fn ($q) => $q->where('category_id', $activeCategory->id))
            ->latest()->paginate(16)->withQueryString();

        // Banner iklan aktif (dikelola admin) untuk carousel hero.
        $banners = Banner::where('is_active', true)->orderBy('sort')->orderByDesc('id')->get();

        return view('products.index', compact('products', 'categories', 'activeCategory', 'banners'));
    }

    public function show($id)
    {
        $product = Product::with(['store.user', 'reviews.user'])->findOrFail($id);
        // Id user penjual (untuk fitur chat/nawar). Null bila produk penjualnya tak dikenal.
        $sellerId = optional($product->store)->user_id;
        return view('products.show', compact('product', 'sellerId'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isSeller(), 403, 'Hanya penjual yang bisa menambah produk.');
        return view('products.create', ['categories' => Category::orderBy('sort')->get()]);
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
            'category_id' => 'nullable|exists:categories,id',
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
            'category_id'   => $req->filled('category_id') ? (int) $req->category_id : null,
            'seller_wallet' => $store->payout_wallet, // dana escrow -> wallet payout toko
            'product_id'    => Str::uuid(),
            'image'         => $imageName,
        ]);

        return redirect('/products')->with('success', 'Produk berhasil ditambahkan!');
    }

    /** Ambil produk milik toko user (otorisasi), atau 403/404. */
    private function ownedProduct($id): Product
    {
        $store = auth()->user()->store;
        abort_unless($store, 403, 'Toko belum tersedia untuk akun ini.');
        $product = Product::findOrFail($id);
        abort_unless($product->store_id === $store->id, 403, 'Bukan produk tokomu.');
        return $product;
    }

    public function edit($id)
    {
        abort_unless(auth()->user()->isSeller(), 403, 'Hanya penjual.');
        $product = $this->ownedProduct($id);
        return view('products.edit', [
            'product'    => $product,
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function update(Request $req, $id)
    {
        abort_unless(auth()->user()->isSeller(), 403, 'Hanya penjual.');
        $product = $this->ownedProduct($id);

        $req->validate([
            'name'        => 'required',
            'price_usdc'  => 'required|numeric|min:0',
            'stock'       => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product->name        = $req->name;
        $product->description = $req->description;
        $product->price_usdc  = $req->price_usdc;
        $product->stock       = $req->filled('stock') ? (int) $req->stock : null;
        $product->category_id = $req->filled('category_id') ? (int) $req->category_id : null;

        if ($req->hasFile('image')) {
            // Hapus gambar lokal lama (bukan URL remote).
            if ($product->image && !str_starts_with($product->image, 'http')) {
                @unlink(public_path('product_images/' . $product->image));
            }
            $imageName = Str::uuid() . '.' . $req->image->getClientOriginalExtension();
            $req->image->move(public_path('product_images'), $imageName);
            $product->image = $imageName;
        }
        $product->save();

        return redirect('/seller')->with('success', 'Produk diperbarui.');
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->isSeller(), 403, 'Hanya penjual.');
        $product = $this->ownedProduct($id);

        if ($product->image && !str_starts_with($product->image, 'http')) {
            @unlink(public_path('product_images/' . $product->image));
        }
        $product->delete();

        return redirect('/seller')->with('success', 'Produk dihapus.');
    }
}

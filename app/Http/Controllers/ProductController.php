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
        abort_unless(auth()->user()->store, 403, 'Toko belum tersedia untuk akun ini.');
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
            'images'   => 'nullable|array|max:6',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Simpan semua gambar; urutan unggah = urutan galeri (elemen 0 = thumbnail).
        $gallery = $this->storeImages($req);

        Product::create([
            'name'          => $req->name,
            'description'   => $req->description,
            'price_usdc'    => $req->price_usdc,
            'stock'         => $req->filled('stock') ? (int) $req->stock : null, // kosong = tak dibatasi
            'store_id'      => $store->id,
            'category_id'   => $req->filled('category_id') ? (int) $req->category_id : null,
            'seller_wallet' => $store->payout_wallet, // dana escrow -> wallet payout toko
            'product_id'    => Str::uuid(),
            'image'         => $gallery[0] ?? null, // thumbnail = gambar pertama
            'gallery'       => $gallery ?: null,
        ]);

        return redirect('/seller/products')->with('success', 'Produk berhasil ditambahkan.');
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
            'images'      => 'nullable|array|max:6',
            'images.*'    => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product->name        = $req->name;
        $product->description = $req->description;
        $product->price_usdc  = $req->price_usdc;
        $product->stock       = $req->filled('stock') ? (int) $req->stock : null;
        $product->category_id = $req->filled('category_id') ? (int) $req->category_id : null;

        // Unggahan baru MENGGANTI seluruh galeri; kosong = pertahankan galeri lama.
        if ($req->hasFile('images')) {
            $this->deleteLocalImages($product);
            $gallery = $this->storeImages($req);
            $product->gallery = $gallery ?: null;
            $product->image   = $gallery[0] ?? null; // thumbnail = gambar pertama
        }
        $product->save();

        return redirect('/seller/products')->with('success', 'Produk diperbarui.');
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->isSeller(), 403, 'Hanya penjual.');
        $product = $this->ownedProduct($id);

        $this->deleteLocalImages($product);
        $product->delete();

        return redirect('/seller/products')->with('success', 'Produk dihapus.');
    }

    /**
     * Simpan file dari input `images[]` ke public/product_images.
     * Kembalikan array nama file (urut sesuai unggahan; elemen 0 = thumbnail).
     */
    private function storeImages(Request $req): array
    {
        $names = [];
        foreach ((array) $req->file('images', []) as $file) {
            if (!$file) {
                continue;
            }
            $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('product_images'), $name);
            $names[] = $name;
        }
        return $names;
    }

    /** Hapus semua gambar lokal milik produk (kolom image + gallery). URL remote dilewati. */
    private function deleteLocalImages(Product $product): void
    {
        $refs = is_array($product->gallery) ? $product->gallery : [];
        if ($product->image) {
            $refs[] = $product->image;
        }
        foreach (array_unique(array_filter($refs)) as $ref) {
            if (!str_starts_with($ref, 'http')) {
                @unlink(public_path('product_images/' . $ref));
            }
        }
    }
}

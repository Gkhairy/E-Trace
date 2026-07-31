<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Halaman keranjang.
    public function index()
    {
        $items = CartItem::with('product')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('cart.index', compact('items'));
    }

    // Tambah produk ke keranjang (dari kartu produk / detail).
    public function add(Request $req)
    {
        $data = $req->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);
        $qty = $data['quantity'] ?? 1;

        $item = CartItem::firstOrNew([
            'user_id'    => auth()->id(),
            'product_id' => $data['product_id'],
        ]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $qty;
        $item->save();

        // Dukung fetch (JSON) maupun form biasa (redirect).
        if ($req->expectsJson() || $req->boolean('ajax')) {
            $count = CartItem::where('user_id', auth()->id())->sum('quantity');
            return response()->json(['success' => true, 'count' => $count]);
        }

        // buy_now = langsung ke checkout (tombol "Beli Langsung").
        if ($req->boolean('buy_now')) {
            return redirect('/checkout');
        }

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    // Ubah jumlah item.
    public function update(Request $req)
    {
        $data = $req->validate([
            'id'       => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        CartItem::where('id', $data['id'])
            ->where('user_id', auth()->id())
            ->update(['quantity' => $data['quantity']]);

        return back();
    }

    // Hapus item.
    public function remove(Request $req)
    {
        $data = $req->validate(['id' => 'required|integer']);

        CartItem::where('id', $data['id'])
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', 'Item dihapus dari keranjang.');
    }
}

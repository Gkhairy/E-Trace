<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $req)
    {
        $data = $req->validate([
            'order_item_id' => 'required|integer',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'nullable|string|max:1000',
        ]);

        $item = OrderItem::with(['order', 'product'])->find($data['order_item_id']);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan.'], 404);
        }
        // Hanya pembeli pemilik order.
        if (!$item->order || $item->order->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan.'], 403);
        }
        // Hanya item yang sudah selesai (barang diterima).
        if ($item->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Ulasan hanya untuk item yang sudah selesai.'], 422);
        }
        if (Review::where('order_item_id', $item->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Item ini sudah diulas.'], 409);
        }

        Review::create([
            'order_item_id' => $item->id,
            'user_id'       => auth()->id(),
            'product_id'    => $item->product_id,
            'store_id'      => $item->product->store_id ?? null,
            'rating'        => $data['rating'],
            'comment'       => $data['comment'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }
}

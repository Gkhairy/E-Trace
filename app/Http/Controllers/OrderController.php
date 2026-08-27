<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\ShippingAddress;
use App\Jobs\SendOrderReceipt;
use App\Jobs\SendSellerNewOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // Riwayat order milik user (header + item per penjual).
    public function index()
    {
        $orders = Order::with(['items.product', 'items.review', 'shippingAddress'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * H3: sinyal ringan untuk auto-refresh. Kembalikan "signature" dari state order
     * user (jumlah + status header & item + status logistik). Frontend polling:
     * kalau signature berubah -> muat ulang sekali (menangkap order/item baru).
     */
    public function updates()
    {
        $rows = Order::where('user_id', auth()->id())
            ->with(['items:id,order_ref_id,item_index,status,fulfillment_status'])
            ->get(['id', 'order_id', 'status']);

        $parts = [];
        foreach ($rows as $o) {
            $parts[] = $o->order_id . ':' . $o->status;
            foreach ($o->items as $it) {
                $parts[] = $it->item_index . '=' . $it->status . '/' . ($it->fulfillment_status ?? '');
            }
        }
        return response()->json([
            'count' => $rows->count(),
            'sig'   => md5(implode('|', $parts)),
        ]);
    }

    /**
     * Simpan order dari cart SETELAH pembayaran on-chain (payCart) sukses.
     * Membuat: alamat pengiriman + order (header) + order_items (per penjual),
     * lalu mengosongkan cart. Dijalankan dalam transaksi.
     */
    public function store(Request $req)
    {
        // Catatan: total, amount, seller TIDAK dipercaya dari browser — diambil dari CHAIN.
        $data = $req->validate([
            'order_id'               => 'required|string|max:80',
            'tx_hash'                => 'required|string|regex:/^0x[a-fA-F0-9]{64}$/',
            'shipping_address_id'    => 'nullable|integer|exists:shipping_addresses,id',
            'shipping.recipient_name'=> 'required_without:shipping_address_id|string|max:255',
            'shipping.phone'         => 'required_without:shipping_address_id|string|max:20',
            'shipping.address'       => 'required_without:shipping_address_id|string',
            'shipping.city'          => 'required_without:shipping_address_id|string|max:255',
            'shipping.postal_code'   => 'required_without:shipping_address_id|string|max:10',
            'shipping.notes'         => 'nullable|string|max:255',
            'address_label'          => 'nullable|string|max:40',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.item_index'     => 'required|integer|min:0',
        ]);

        // Cegah duplikat: satu tx_hash / order_id hanya tercatat sekali.
        if (Order::where('tx_hash', $data['tx_hash'])->orWhere('order_id', $data['order_id'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Order sudah tercatat.'], 409);
        }

        $user = auth()->user();
        if (!$user->wallet_address) {
            return response()->json(['success' => false, 'message' => 'Akun tidak memiliki wallet terikat.'], 422);
        }

        // Ekspektasi tiap item dibangun dari PRODUK (server-side), lalu dicocokkan ke chain.
        $expected = [];
        foreach ($data['items'] as $it) {
            $product = \App\Models\Product::find($it['product_id']);
            if (!$product) {
                continue; // produk terhapus -> lewati, jangan gagalkan seluruh order
            }
            $expected[(int) $it['item_index']] = [
                'product_id_uuid' => $product->product_id,
                'seller_wallet'   => strtolower($product->seller_wallet),
                'db_product_id'   => $product->id,
                'price'           => (float) $product->price_usdc,
            ];
        }

        $verifier = new \App\Services\SepoliaVerifier();

        // ===== H2: pastikan JUMLAH item cocok dengan yang dibayar on-chain =====
        // Kalau browser mengirim lebih sedikit item dari yang tercatat di kontrak,
        // lengkapi dari chain (map productId->Product) supaya tak ada item yang hilang.
        $onChainCount = $verifier->itemCount($data['order_id']);
        if ($onChainCount !== null && $onChainCount > count($expected)) {
            for ($i = 0; $i < $onChainCount; $i++) {
                if (isset($expected[$i])) {
                    continue;
                }
                $chainItem = $verifier->getItem($data['order_id'], $i);
                if (!$chainItem || empty($chainItem['productId'])) {
                    continue;
                }
                $product = \App\Models\Product::where('product_id', $chainItem['productId'])->first();
                if (!$product) {
                    Log::warning("Order {$data['order_id']}: item on-chain #$i (produk {$chainItem['productId']}) tak dikenal, dilewati.");
                    continue;
                }
                $expected[$i] = [
                    'product_id_uuid' => $product->product_id,
                    'seller_wallet'   => strtolower($product->seller_wallet),
                    'db_product_id'   => $product->id,
                    'price'           => (float) $product->price_usdc,
                ];
            }
            ksort($expected);
        }

        if (empty($expected)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada item valid untuk disimpan.'], 422);
        }

        // ===== VERIFIKASI ON-CHAIN (sumber kebenaran) =====
        $vr = $verifier
            ->verifyCart($data['order_id'], $data['tx_hash'], $user->wallet_address, $expected);
        if (!($vr['ok'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'Verifikasi on-chain gagal: ' . ($vr['reason'] ?? '-')], 422);
        }

        // Nominal & status diambil dari CHAIN.
        $totalTlkm = '0';
        foreach ($vr['items'] as $amt) {
            $totalTlkm = bcadd($totalTlkm, $amt['amount_tlkm'], 6);
        }
        $headerStatus = $vr['confirmations'] >= config('chain.paid_confirmations') ? 'paid' : 'pending_confirmation';

        try {
        $order = DB::transaction(function () use ($data, $expected, $vr, $totalTlkm, $headerStatus) {
            // 1) Alamat pengiriman (data pribadi, di DB saja).
            // Pakai alamat tersimpan bila dipilih & milik user; kalau tidak, pakai input baru.
            $addr = null;
            if (!empty($data['shipping_address_id'])) {
                $addr = ShippingAddress::where('id', $data['shipping_address_id'])
                    ->where('user_id', auth()->id())->first();
            }
            if (!$addr) {
                $s = $data['shipping'] ?? [];
                $addr = ShippingAddress::create([
                    'user_id'        => auth()->id(),
                    'label'          => $data['address_label'] ?? null,
                    'recipient_name' => $s['recipient_name'] ?? '',
                    'phone'          => $s['phone'] ?? '',
                    'address'        => $s['address'] ?? '',
                    'city'           => $s['city'] ?? '',
                    'postal_code'    => $s['postal_code'] ?? '',
                    'notes'          => $s['notes'] ?? null,
                ]);
            }

            // 2) Header order — nominal & status dari chain.
            $order = Order::create([
                'user_id'             => auth()->id(),
                'product_id'          => $data['items'][0]['product_id'], // legacy (kolom NOT NULL)
                'order_id'            => $data['order_id'],
                'tx_hash'             => $data['tx_hash'],
                'amount'              => $totalTlkm,   // legacy
                'total'               => $totalTlkm,
                'shipping_address_id' => $addr->id,
                'status'              => $headerStatus,
                'block_number'        => $vr['block_number'],
                'confirmations'       => $vr['confirmations'],
            ]);

            // 3) Item — amount & seller dari chain/produk (bukan dari browser).
            foreach ($expected as $index => $exp) {
                $amt = $vr['items'][$index]['amount_tlkm'];
                // Quantity diturunkan dari nominal on-chain ÷ harga produk.
                $qty = $exp['price'] > 0 ? max(1, (int) round((float) $amt / $exp['price'])) : 1;

                OrderItem::create([
                    'order_ref_id' => $order->id,
                    'product_id'   => $exp['db_product_id'],
                    'seller_wallet'=> $exp['seller_wallet'],
                    'amount'       => $amt,
                    'quantity'     => $qty,
                    'item_index'   => $index,
                    'status'       => 'paid',
                ]);

                // Kurangi stok (kalau produk melacak stok).
                $prod = \App\Models\Product::find($exp['db_product_id']);
                if ($prod && $prod->stock !== null) {
                    $prod->stock = max(0, $prod->stock - $qty);
                    $prod->save();
                }
            }

            // 4) Kosongkan cart.
            CartItem::where('user_id', auth()->id())->delete();

            return $order;
        });
        } catch (\Illuminate\Database\QueryException $e) {
            // Backstop unique constraint (order_id/tx_hash) dari race -> tetap idempotent.
            return response()->json(['success' => false, 'message' => 'Order sudah tercatat.'], 409);
        }

        // Kirim email via queue (RabbitMQ) — SUDAH di belakang verifikasi on-chain.
        // Best-effort: kegagalan queue TIDAK menggagalkan checkout.
        try {
            SendOrderReceipt::dispatch($order->id);
            foreach ($order->items()->pluck('seller_wallet')->unique() as $wallet) {
                SendSellerNewOrder::dispatch($order->id, $wallet);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal dispatch email order '.$order->id.': '.$e->getMessage());
        }

        // Notifikasi in-app: pembeli (pesanan dibuat) + tiap penjual (pesanan baru).
        \App\Support\Notify::send($order->user_id, 'order', 'Pesanan dibuat',
            'Pembayaran diterima & ditahan escrow. Order ' . $order->order_id . '.', '/orders', '🛒');
        foreach ($order->items()->pluck('seller_wallet')->unique() as $wallet) {
            \App\Support\Notify::toWallet($wallet, 'order', 'Pesanan baru masuk',
                'Ada pesanan baru untuk tokomu. Segera proses & kirim.', '/seller', '📦');
        }

        return response()->json(['success' => true, 'order_id' => $order->id, 'status' => $order->status]);
    }

    /**
     * Update status SATU item (mengikuti aksi on-chain confirmItem/refundItem).
     * Owner-only, hanya dari status 'paid'. Konfirmasi 1 item TIDAK menyentuh item lain.
     */
    public function updateItemStatus(Request $req)
    {
        $data = $req->validate([
            'order_id'   => 'required|string',
            'item_index' => 'required|integer|min:0',
            'status'     => 'required|in:completed,refunded,disputed',
        ]);

        $order = Order::where('order_id', $data['order_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan.'], 404);
        }

        $item = OrderItem::where('order_ref_id', $order->id)
            ->where('item_index', $data['item_index'])
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan.'], 404);
        }

        if ($item->status !== 'paid') {
            return response()->json(['success' => true, 'status' => $item->status]); // sudah final
        }

        // ===== VERIFIKASI ON-CHAIN: status DB harus mengikuti status kontrak =====
        // completed->Completed(2), refunded->Refunded(3), disputed->Disputed(4).
        $onchain = (new \App\Services\SepoliaVerifier())->getItem($order->order_id, (int) $data['item_index']);
        if (!$onchain) {
            return response()->json(['success' => false, 'message' => 'Item tidak terbaca di kontrak.'], 422);
        }
        $toChain = ['completed' => 2, 'refunded' => 3, 'disputed' => 4];
        if ($onchain['status'] !== $toChain[$data['status']]) {
            return response()->json([
                'success' => false,
                'message' => 'Status on-chain belum sesuai. Pastikan transaksi sudah dikonfirmasi di blockchain.',
            ], 422);
        }

        $item->status = $data['status'];
        // Konfirmasi terima = barang diterima -> tandai logistik 'delivered'.
        if ($data['status'] === 'completed') {
            $item->fulfillment_status = 'delivered';
            $item->delivered_at = now();
        }
        $item->save();

        // Header 'completed' kalau tidak ada lagi item yang belum final (paid/disputed/pending).
        if (!OrderItem::where('order_ref_id', $order->id)->whereIn('status', ['paid', 'disputed', 'pending_confirmation'])->exists()) {
            $order->status = 'completed';
            $order->save();
        }

        // Notifikasi ke penjual (+ pengawas bila sengketa).
        $prodName = optional($item->product)->name ?: 'Produk';
        if ($data['status'] === 'completed') {
            \App\Support\Notify::toWallet($item->seller_wallet, 'order', 'Pesanan dikonfirmasi diterima',
                "Pembeli mengonfirmasi \"{$prodName}\" diterima — dana dilepas ke kamu.", '/seller', '✅');
        } elseif ($data['status'] === 'refunded') {
            \App\Support\Notify::toWallet($item->seller_wallet, 'order', 'Item direfund',
                "Item \"{$prodName}\" direfund ke pembeli.", '/seller', '↩️');
        } elseif ($data['status'] === 'disputed') {
            \App\Support\Notify::toWallet($item->seller_wallet, 'order', 'Sengketa diajukan',
                "Pembeli mengajukan sengketa untuk \"{$prodName}\". Dana ditahan sampai pengawas memutus.", '/seller', '⚠️');
            \App\Support\Notify::toSupervisors('order', 'Sengketa baru',
                "Ada sengketa untuk \"{$prodName}\" (order {$order->order_id}). Perlu ditinjau.", '/supervisor/disputes', '⚖️');
        }

        return response()->json(['success' => true, 'status' => $item->status]);
    }
}

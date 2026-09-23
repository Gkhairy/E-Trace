<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Jobs\SendShipped;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SellerController extends Controller
{
    private function store()
    {
        $user = auth()->user();
        abort_unless($user->isSeller(), 403, 'Hanya penjual yang bisa mengakses dashboard toko.');
        $store = $user->store;
        abort_unless($store, 404, 'Toko belum tersedia untuk akun ini.');
        return $store;
    }

    /** Semua item order milik toko ini (dicocokkan lewat wallet payout). */
    private function itemsOf($store)
    {
        return OrderItem::where('seller_wallet', $store->payout_wallet);
    }

    /** Item yang sudah dibayar tapi belum dikirim: pekerjaan utama penjual hari ini. */
    private function toShipQuery($store)
    {
        return $this->itemsOf($store)->where('status', 'paid')
            ->whereIn('fulfillment_status', ['pending', 'processing']);
    }

    public function dashboard()
    {
        $store  = $this->store();
        $feeBps = (int) config('chain.platform_fee_bps'); // 100 = 1%
        $vatBps = (int) config('chain.vat_bps');          // 1100 = 11% (atas fee)

        // Deret harian 180 hari: tampilan memotong 7/30/90 hari dan membandingkan
        // dengan periode sebelumnya yang sama panjang. Refund tidak dihitung penjualan.
        $start = now()->subDays(179)->startOfDay();
        $rows = $this->itemsOf($store)->where('status', '!=', 'refunded')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) d, SUM(amount) amt, COUNT(*) orders, SUM(COALESCE(quantity,1)) qty')
            ->groupBy('d')->get()->keyBy('d');
        $daily = [];
        for ($i = 179; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $r = $rows[$day->toDateString()] ?? null;
            $daily[] = [
                'date'   => $day->translatedFormat('d M'),
                'sales'  => round((float) ($r->amt ?? 0), 4),
                'orders' => (int) ($r->orders ?? 0),
                'qty'    => (int) ($r->qty ?? 0),
            ];
        }

        $byStatus = $this->itemsOf($store)->selectRaw('status, SUM(amount) amt, COUNT(*) c')
            ->groupBy('status')->get()->keyBy('status');
        $amt = fn ($k) => (float) ($byStatus[$k]->amt ?? 0);

        $gross = $amt('completed');
        $fee   = $gross * $feeBps / 10000;
        $money = [
            'released_net' => $gross - $fee,              // sudah cair ke wallet (bruto - fee)
            'escrow'       => $amt('paid') + $amt('disputed'),
            'disputed'     => $amt('disputed'),
            'gross'        => $gross,
            'fee'          => $fee,
            'vat'          => $fee * $vatBps / 10000,     // PPN dihitung atas fee jasa platform
            'refunded'     => $amt('refunded'),
            'fee_pct'      => $feeBps / 100,
            'vat_pct'      => $vatBps / 100,
        ];

        $toShip = $this->toShipQuery($store)->count();

        $recent = $this->itemsOf($store)->with(['order.shippingAddress', 'product'])
            ->latest()->limit(6)->get();

        // Terlaris: jumlah unit terjual (tanpa refund), lalu pendapatannya.
        $top = $this->itemsOf($store)->where('status', '!=', 'refunded')->whereNotNull('product_id')
            ->selectRaw('product_id, SUM(COALESCE(quantity,1)) sold, SUM(amount) revenue')
            ->groupBy('product_id')->orderByDesc('sold')->limit(6)->get();
        $topProducts = \App\Models\Product::whereIn('id', $top->pluck('product_id'))->get()->keyBy('id');
        $best = $top->map(fn ($t) => ['product' => $topProducts[$t->product_id] ?? null, 'sold' => (int) $t->sold, 'revenue' => (float) $t->revenue])
            ->filter(fn ($b) => $b['product'])->values();

        $lowStock = $store->products()->whereNotNull('stock')->where('stock', '<=', 3)->orderBy('stock')->limit(5)->get();
        $productCount = $store->products()->count();

        return view('seller.dashboard', compact('store', 'daily', 'money', 'toShip', 'recent', 'best', 'lowStock', 'productCount'));
    }

    /** Pesanan: dikelompokkan menurut apa yang harus dilakukan penjual. */
    public function orders(Request $req)
    {
        $store = $this->store();
        $filters = [
            'kirim'   => fn ($q) => $q->where('status', 'paid')->whereIn('fulfillment_status', ['pending', 'processing']),
            'jalan'   => fn ($q) => $q->where('status', 'paid')->where('fulfillment_status', 'shipped'),
            'selesai' => fn ($q) => $q->where('status', 'completed'),
            'masalah' => fn ($q) => $q->whereIn('status', ['disputed', 'refunded']),
            'semua'   => fn ($q) => $q,
        ];
        $counts = collect($filters)->map(fn ($f) => $f($this->itemsOf($store))->count());
        $tab = $req->query('tab');
        if (!isset($filters[$tab])) {
            $tab = $counts['kirim'] > 0 ? 'kirim' : 'semua';
        }

        $items = $filters[$tab]($this->itemsOf($store))
            ->with(['order.shippingAddress', 'product'])
            ->latest()->paginate(15)->withQueryString();

        $feePct = (int) config('chain.platform_fee_bps') / 100;

        return view('seller.orders', compact('store', 'items', 'counts', 'tab', 'feePct'));
    }

    /** Produk: daftar dengan unit terjual & status stok, bisa dicari. */
    public function products(Request $req)
    {
        $store = $this->store();
        $q = trim((string) $req->query('q', ''));

        $products = $store->products()->with('category')
            ->when($q !== '', fn ($b) => $b->where('name', 'like', '%' . addcslashes($q, '\%_') . '%'))
            ->latest()->paginate(24)->withQueryString();

        $sold = $this->itemsOf($store)->where('status', '!=', 'refunded')
            ->whereIn('product_id', $products->pluck('id'))
            ->selectRaw('product_id, SUM(COALESCE(quantity,1)) sold')->groupBy('product_id')
            ->pluck('sold', 'product_id');

        return view('seller.products', compact('store', 'products', 'sold', 'q'));
    }

    public function editStore()
    {
        $store = $this->store();
        return view('seller.store-edit', compact('store'));
    }

    public function updateStore(Request $req)
    {
        $store = $this->store();

        $data = $req->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'origin_address' => 'required|string|max:500', // wajib: dipakai hitung ongkir (kota asal)
            'contact_email'  => 'nullable|email|max:255',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $store->name           = $data['name'];
        $store->description    = $data['description'] ?? null;
        $store->origin_address = $data['origin_address'] ?? null;
        $store->contact_email  = $data['contact_email'] ?? null;

        foreach (['logo', 'banner'] as $field) {
            if ($req->hasFile($field)) {
                $name = Str::uuid() . '.' . $req->file($field)->getClientOriginalExtension();
                $req->file($field)->move(public_path('store_images'), $name);
                $store->{$field} = $name;
            }
        }

        $store->save();

        return redirect('/seller/store')->with('success', 'Pengaturan toko disimpan.');
    }

    /**
     * Update status pengiriman item (logistik off-chain).
     * process: pending -> processing. ship: -> shipped (+ resi).
     */
    public function fulfill(Request $req)
    {
        $store = $this->store();

        $data = $req->validate([
            'item_id'         => 'required|integer',
            'action'          => 'required|in:process,ship',
            'tracking_number' => 'required_if:action,ship|nullable|string|max:100',
            'courier'         => 'nullable|string|max:60',
        ]);

        // Hanya item milik toko ini (cocokkan wallet payout).
        $item = OrderItem::where('id', $data['item_id'])
            ->where('seller_wallet', $store->payout_wallet)
            ->first();
        abort_unless($item, 404, 'Item tidak ditemukan.');

        // Hanya boleh kirim kalau pembayaran (escrow) sudah terkonfirmasi.
        if ($item->status !== 'paid') {
            return back()->with('error', 'Pesanan belum bisa diproses (pembayaran belum terkonfirmasi atau sudah selesai/refund).');
        }

        if ($data['action'] === 'process') {
            if ($item->fulfillment_status === 'pending') {
                $item->fulfillment_status = 'processing';
                $item->save();
            }
        } else { // ship
            $item->fulfillment_status = 'shipped';
            $item->tracking_number    = $data['tracking_number'];
            $item->courier            = $data['courier'] ?? null;
            $item->shipped_at         = now();
            $item->save();

            // Notifikasi email ke pembeli (via queue). Best-effort.
            try {
                SendShipped::dispatch($item->id);
            } catch (\Throwable $e) {
                Log::warning('Gagal dispatch email dikirim item '.$item->id.': '.$e->getMessage());
            }

            // Notifikasi in-app ke pembeli: pesanan dikirim (+ resi).
            $buyerId = optional($item->order)->user_id;
            $prodName = optional($item->product)->name ?: 'Produk';
            $resi = $item->tracking_number ? (' Resi: ' . $item->tracking_number . ($item->courier ? ' (' . $item->courier . ')' : '') . '.') : '';
            \App\Support\Notify::send($buyerId, 'order', 'Pesanan dikirim',
                "\"{$prodName}\" sedang dikirim ke alamatmu.{$resi}", '/orders', '🚚');
        }

        return back()->with('success', 'Status pengiriman diperbarui.');
    }
}


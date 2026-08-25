<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use App\Models\WalletLabel;
use App\Support\Identity;
use App\Services\SepoliaVerifier;
use Illuminate\Support\Facades\Cache;

class ExplorerController extends Controller
{
    // Overview transparansi publik. Agregat di-cache 2 menit (anti-berat di skala besar).
    public function index()
    {
        $totals = Cache::remember('explorer:totals', 120, fn () => [
            'volume'      => (float) Order::sum('total'),
            'tx'          => Order::count(),
            'escrow_held' => (float) OrderItem::where('status', 'paid')->sum('amount'),
            'stores'      => Store::count(),
            'labels'      => WalletLabel::count(),
        ]);

        $labels = Cache::remember('explorer:labels', 120, fn () => WalletLabel::latest()->get()->map(fn ($l) => [
            'address' => $l->address, 'name' => $l->label, 'category' => $l->category, 'verified' => $l->verified,
        ]));

        // Ringkasan penjualan per toko dihitung sekali dari agregat (bukan query per-baris).
        $stores = Cache::remember('explorer:stores', 120, function () {
            $sold = OrderItem::where('status', 'completed')
                ->selectRaw('seller_wallet, SUM(amount) t')->groupBy('seller_wallet')->pluck('t', 'seller_wallet');
            return Store::withCount('products')->get()->map(fn ($s) => [
                'address' => $s->payout_wallet, 'name' => $s->name,
                'products' => $s->products_count, 'sold' => (float) ($sold[$s->payout_wallet] ?? 0),
            ])->sortByDesc('sold')->values();
        });

        // Transaksi terbaru (item) — paginasi + identitas via resolver.
        $recent = OrderItem::with(['order.user', 'product'])->latest()->paginate(20)->through(function ($it) {
            $buyerAddr = strtolower(optional($it->order->user)->wallet_address ?? '');
            return [
                'tx'      => $it->order->tx_hash,
                'buyer'   => $buyerAddr,
                'buyerId' => $buyerAddr ? Identity::resolve($buyerAddr) : ['name' => '—', 'verified' => false],
                'seller'  => $it->seller_wallet,
                'sellerId'=> Identity::resolve($it->seller_wallet),
                'product' => $it->product->name ?? '—',
                'amount'  => (float) $it->amount,
                'status'  => $it->status,
                'time'    => $it->created_at,
            ];
        });

        return view('explorer.index', compact('totals', 'labels', 'stores', 'recent'));
    }

    // Profil satu wallet/entitas.
    public function show(string $address)
    {
        $addr = strtolower($address);
        if (!preg_match('/^0x[a-f0-9]{40}$/', $addr)) {
            abort(404);
        }

        $identity = Identity::resolve($addr);
        // Saldo TLKM via RPC — cache 2 menit per alamat agar tak panggil RPC tiap request.
        $balance  = Cache::remember("explorer:bal:{$addr}", 120, fn () => (new SepoliaVerifier())->tlkmBalance($addr));

        // Sebagai pembeli.
        $user = User::where('wallet_address', $addr)->first();
        $buyerOrders = $user
            ? Order::with('items.product')->where('user_id', $user->id)->latest()->get()
            : collect();

        // Sebagai penjual.
        $sellerItems = OrderItem::with(['order.user', 'product'])->where('seller_wallet', $addr)->latest()->get();

        $stats = [
            'bought_total' => (float) $buyerOrders->sum('total'),
            'bought_count' => $buyerOrders->count(),
            'sold_net'     => (float) $sellerItems->where('status', 'completed')->sum(fn ($i) => (float) $i->amount * 0.99),
            'sold_count'   => $sellerItems->count(),
            'escrow_in'    => (float) $sellerItems->where('status', 'paid')->sum('amount'),         // ditahan untuk dia (penjual)
            'escrow_out'   => (float) $buyerOrders->flatMap->items->where('status', 'paid')->sum('amount'), // ditahan dari dia (pembeli)
        ];

        return view('explorer.show', compact('addr', 'identity', 'balance', 'buyerOrders', 'sellerItems', 'stats'));
    }
}

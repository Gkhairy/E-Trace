<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use App\Models\WalletLabel;
use App\Support\Identity;
use App\Services\ChainVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExplorerController extends Controller
{
    // Overview transparansi publik. Agregat di-cache 2 menit (anti-berat di skala besar).
    public function index(Request $request)
    {
        // D1: filter waktu transaksi — harian/mingguan/bulanan/semua.
        $range = in_array($request->query('range'), ['day', 'week', 'month', 'all'], true)
            ? $request->query('range') : 'all';
        $since = match ($range) {
            'day'   => now()->subDay(),
            'week'  => now()->subWeek(),
            'month' => now()->subMonth(),
            default => null,
        };

        // D4: urutan toko — paling banyak terjual / rating tertinggi.
        $storeSort = in_array($request->query('store_sort'), ['sold', 'rating'], true)
            ? $request->query('store_sort') : 'sold';

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

        // Ringkasan penjualan + rating per toko (cache per mode urutan), ambil TOP 4.
        $stores = Cache::remember("explorer:stores:{$storeSort}", 120, function () use ($storeSort) {
            $sold = OrderItem::where('status', 'completed')
                ->selectRaw('seller_wallet, SUM(amount) t')->groupBy('seller_wallet')->pluck('t', 'seller_wallet');
            $ratings = Review::selectRaw('store_id, AVG(rating) avg, COUNT(*) c')->groupBy('store_id')->get()->keyBy('store_id');

            $mapped = Store::withCount('products')->get()->map(function ($s) use ($sold, $ratings) {
                $r = $ratings[$s->id] ?? null;
                return [
                    'address' => $s->payout_wallet, 'name' => $s->name,
                    'products' => $s->products_count, 'sold' => (float) ($sold[$s->payout_wallet] ?? 0),
                    'rating' => $r ? round((float) $r->avg, 1) : null, 'reviews' => $r ? (int) $r->c : 0,
                ];
            });
            $sorted = $storeSort === 'rating'
                ? $mapped->sortByDesc(fn ($s) => [$s['rating'] ?? -1, $s['reviews']])
                : $mapped->sortByDesc('sold');
            return $sorted->values()->take(4);
        });

        // Transaksi terbaru (item), difilter waktu (D1) — paginasi + identitas via resolver.
        $recent = OrderItem::with(['order.user', 'product'])
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->latest()->paginate(20)->withQueryString()->through(function ($it) {
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

        return view('explorer.index', compact('totals', 'labels', 'stores', 'recent', 'range', 'storeSort'));
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
        $balance  = Cache::remember("explorer:bal:{$addr}", 120, fn () => (new ChainVerifier())->tlkmBalance($addr));

        // Sebagai pembeli.
        $user = User::where('wallet_address', $addr)->first();

        // Tanggal bergabung (A3): pakai tanggal daftar toko (jika penjual) atau akun user.
        $store = Store::where('payout_wallet', $addr)->first();
        $joined = $store?->created_at ?? $user?->created_at;
        // Nama tampilan adalah nama samaran yang belum diverifikasi? (A4)
        $isPseudonym = ($identity['type'] === 'buyer' && !$identity['verified']
            && $user && $user->explorer_public && $user->public_name);
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

        return view('explorer.show', compact('addr', 'identity', 'balance', 'buyerOrders', 'sellerItems', 'stats', 'joined', 'isPseudonym'));
    }
}

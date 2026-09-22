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

        // Deret 14 hari: volume (TLKM) + jumlah transaksi per hari — untuk grafik.
        $daily = Cache::remember('explorer:daily', 120, function () {
            $rows = Order::selectRaw('DATE(created_at) d, SUM(total) vol, COUNT(*) c')
                ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->groupBy('d')->pluck('vol', 'd');
            $cnt = Order::selectRaw('DATE(created_at) d, COUNT(*) c')
                ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->groupBy('d')->pluck('c', 'd');
            $out = [];
            for ($i = 13; $i >= 0; $i--) {
                $day = now()->subDays($i)->toDateString();
                $out[] = [
                    'date'   => now()->subDays($i)->translatedFormat('d M'),
                    'volume' => (float) ($rows[$day] ?? 0),
                    'count'  => (int) ($cnt[$day] ?? 0),
                ];
            }
            return $out;
        });

        // Distribusi status escrow (untuk donut).
        $statusDist = Cache::remember('explorer:status', 120, fn () => OrderItem::selectRaw('status, COUNT(*) c, SUM(amount) amt')
            ->groupBy('status')->get()
            ->map(fn ($r) => ['status' => $r->status, 'count' => (int) $r->c, 'amount' => (float) $r->amt])
            ->values());

        // Transfer TLKM terbaru — dibaca dari INDEKS di DB (instan, riwayat penuh),
        // diisi command `transfers:index` yang berjalan tiap menit.
        $transfersEnabled = \App\Services\TransferFeed::enabled();
        $transfersRaw = \App\Models\TokenTransfer::query()
            ->orderByDesc('block_number')->orderByDesc('log_index')->limit(25)->get()
            ->map(fn ($t) => [
                'from'  => $t->from_address,
                'to'    => $t->to_address,
                'tlkm'  => (float) $t->amount,
                'tx'    => $t->tx_hash,
                'block' => $t->block_number,
                'time'  => $t->block_time?->getTimestamp(),
            ])->all();

        $labelAddr = $this->addrLabeler();

        $transfers = collect($transfersRaw)->map(fn ($t) => $t + [
            'fromL' => $labelAddr($t['from']),
            'toL'   => $labelAddr($t['to']),
        ])->all();

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

        return view('explorer.index', compact('totals', 'labels', 'stores', 'recent', 'range', 'storeSort', 'daily', 'statusDist', 'transfers', 'transfersEnabled'));
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

        // Riwayat transfer TLKM alamat ini (masuk & keluar) dari INDEKS di DB.
        // Inilah "TLKM scan": dari siapa dana masuk, ke siapa keluar.
        $label = $this->addrLabeler();
        $transfers = \App\Models\TokenTransfer::forAddress($addr)->limit(100)->get()
            ->map(function ($t) use ($addr, $label) {
                $from = strtolower($t->from_address);
                $to   = strtolower($t->to_address);
                $dir  = ($from === $addr && $to === $addr) ? 'self' : ($from === $addr ? 'out' : 'in');
                return [
                    'direction' => $dir,
                    'counter'   => $label($dir === 'out' ? $to : $from),
                    'tlkm'      => (float) $t->amount,
                    'tx'        => $t->tx_hash,
                    'time'      => $t->block_time,
                    'block'     => $t->block_number,
                ];
            })->all();

        // Ringkasan arus TLKM (dari indeks) untuk kartu di atas daftar.
        $flow = [
            'in'      => collect($transfers)->where('direction', 'in')->sum('tlkm'),
            'out'     => collect($transfers)->where('direction', 'out')->sum('tlkm'),
            'count'   => count($transfers),
            'indexed' => (int) (\App\Models\IndexerCursor::find('tlkm_transfers')->block_number ?? 0),
        ];

        return view('explorer.show', compact('addr', 'identity', 'balance', 'buyerOrders', 'sellerItems', 'stats', 'joined', 'isPseudonym', 'transfers', 'flow'));
    }
    /**
     * Pelabel alamat: kontrak sistem (escrow, pool) & entitas terverifikasi diberi nama,
     * sisanya dipendekkan. Dipakai feed global maupun riwayat per alamat.
     */
    private function addrLabeler(): callable
    {
        $zero  = '0x0000000000000000000000000000000000000000';
        $known = array_filter([
            strtolower((string) config('chain.gateway'))               => 'Escrow',
            strtolower((string) config('chain.paylater_address'))      => 'Pool Paylater',
            strtolower((string) config('chain.donation_pool'))         => 'Donasi',
            strtolower((string) config('chain.insurance.pool_wallet')) => 'Pool Asuransi',
        ], fn ($name, $addr) => $name && $addr && $addr !== $zero, ARRAY_FILTER_USE_BOTH);
        $known[$zero] = 'Mint / Burn';

        // Dompet komunitas ikut dikenali supaya arus kas bersama terbaca jelas.
        foreach (\App\Models\CommunityWallet::query()->get(['name', 'address']) as $w) {
            $a = strtolower((string) $w->address);
            if ($a && !isset($known[$a])) {
                $known[$a] = 'Kas: ' . $w->name;
            }
        }

        return function (string $addr) use ($known) {
            $a = strtolower($addr);
            if (!empty($known[$a])) {
                return ['name' => $known[$a], 'system' => true, 'addr' => $a, 'verified' => true];
            }
            $id   = Identity::resolve($a);
            $name = ($id['name'] ?? '—') !== '—' ? $id['name'] : substr($a, 0, 8) . '…' . substr($a, -4);
            return ['name' => $name, 'system' => false, 'addr' => $a, 'verified' => (bool) ($id['verified'] ?? false)];
        };
    }
}
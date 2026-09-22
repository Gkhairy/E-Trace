<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Store;
use App\Services\ShippingService;

class CheckoutController extends Controller
{
    // Halaman checkout: ringkasan cart dikelompokkan per penjual + form alamat.
    public function index(ShippingService $shipping)
    {
        $items = CartItem::with('product')
            ->where('user_id', auth()->id())
            ->get();

        if ($items->isEmpty()) {
            return redirect('/cart')->with('success', 'Keranjang masih kosong.');
        }

        // Kelompokkan per penjual (seller_wallet).
        $groups = $items->groupBy(fn ($it) => $it->product->seller_wallet ?? '-');

        $total = $items->sum(fn ($it) => (float) $it->product->price_usdc * $it->quantity);

        // Buku alamat: alamat tersimpan user (default dulu, lalu terbaru) untuk dipilih.
        $addresses = \App\Models\ShippingAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')->latest()->get();
        $lastAddress = $addresses->first();

        // H7: estimasi ongkir per penjual (J&T bila ada; fallback jarak). Dalam TLKM.
        $buyerCity = $lastAddress->city ?? null;
        $shipEstimates = [];   // seller_wallet => ['fee_tlkm'=>, 'km'=>, 'method'=>, 'store'=>]
        $shipTotalTlkm = 0;
        $etaMax = 1;
        foreach ($groups as $seller => $g) {
            $store = Store::where('payout_wallet', $seller)->first();
            $est = $shipping->estimate($store?->origin_address, $buyerCity);
            $shipEstimates[$seller] = $est + ['store' => $store?->name, 'origin' => $store?->origin_address];
            $shipTotalTlkm += $est['fee_tlkm'];
            $etaMax = max($etaMax, $shipping->etaDays($store?->origin_address, $buyerCity));
        }

        // Garansi Tepat Waktu (asuransi parametrik) — aman-nonaktif bila belum dikonfigurasi.
        $ins = config('chain.insurance');
        $bufferDays = (int) ($ins['eta_buffer_days'] ?? 3);
        $insurance = [
            'enabled'         => (bool) ($ins['enabled'] ?? false) && !empty($ins['pool_wallet']),
            'premium_tlkm'    => (float) ($ins['premium_tlkm'] ?? 2),
            'pool_wallet'     => $ins['pool_wallet'] ?? null,
            'grace_days'      => (int) ($ins['grace_days'] ?? 2),
            'payout_cap_tlkm' => (float) ($ins['payout_cap_tlkm'] ?? 30),
            'promised_days'   => $etaMax + $bufferDays,
            'promised_date'   => now()->addDays($etaMax + $bufferDays),
        ];

        // Kelayakan tombol "Bayar pakai Paylater": sisa limit kredit & likuiditas pool
        // harus cukup untuk total produk. Kalau tidak, tombol di-disable + alasan.
        $paylaterBtn = ['available' => false, 'reason' => null];
        $pv = new \App\Services\PaylaterVerifier();
        if ($pv->configured()) {
            $wallet = strtolower(auth()->user()->wallet_address ?? '');
            // RPC ~0,7-1,5 dt tiap panggilan; di-cache singkat agar checkout tidak lambat.
            $pos = $wallet ? \Illuminate\Support\Facades\Cache::remember("pl:pos:{$wallet}", 30, fn () => $pv->positionOf($wallet)) : null;
            if (!$pos) {
                $paylaterBtn['reason'] = 'Posisi Paylater belum terbaca.';
            } else {
                $availTlkm = (float) bcdiv(bcsub($pos['limit'], $pos['principal']), bcpow('10', '18'), 6);
                if ($availTlkm + 1e-6 < $total) {
                    $shown = rtrim(rtrim(number_format($availTlkm, 2), '0'), '.');
                    $paylaterBtn['reason'] = "Sisa limit Paylater ({$shown} TLKM) kurang dari total. Tambah agunan di Dompet.";
                } else {
                    $liqWei = \Illuminate\Support\Facades\Cache::remember("pl:liq", 30, fn () => $pv->availableLiquidity());
                    $liqTlkm = $liqWei !== null ? (float) bcdiv($liqWei, bcpow('10', '18'), 6) : 0.0;
                    if ($liqTlkm + 1e-6 < $total) {
                        $paylaterBtn['reason'] = 'Likuiditas pool Paylater belum cukup.';
                    } else {
                        $paylaterBtn['available'] = true;
                    }
                }
            }
        }

        // Dana Komunitas: dompet MULTISIG (Mode B) yang user ini jadi anggotanya.
        // Hanya ditampilkan bila ada; kalau lebih dari satu, user memilih mau minta
        // persetujuan ke dompet yang mana. Saldo di-cache singkat (hemat panggilan RPC).
        $communityWallets = collect();
        $cwIds = \App\Models\CommunityMember::where('user_id', auth()->id())->pluck('community_wallet_id');
        if ($cwIds->isNotEmpty()) {
            $verifier = new \App\Services\ChainVerifier();
            $communityWallets = \App\Models\CommunityWallet::whereIn('id', $cwIds)
                ->where('mode', 'B')->orderBy('name')->get()
                ->map(function ($w) use ($verifier, $total) {
                    $bal = \Illuminate\Support\Facades\Cache::remember(
                        "cw:bal:{$w->address}", 60, fn () => $verifier->tlkmBalance($w->address)
                    );
                    $bal = $bal !== null ? (float) $bal : null;
                    return [
                        'id'      => $w->id,
                        'name'    => $w->name,
                        'balance' => $bal,
                        'enough'  => $bal === null ? true : $bal + 1e-6 >= $total,
                        'signers' => $w->members()->where('is_signer', true)->count(),
                    ];
                })->values();
        }

        return view('checkout.index', compact(
            'items', 'groups', 'total', 'addresses', 'lastAddress',
            'shipEstimates', 'shipTotalTlkm', 'buyerCity', 'insurance', 'paylaterBtn', 'communityWallets'
        ));
    }
}

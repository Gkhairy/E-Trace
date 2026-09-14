<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Models\PaylaterLoan;
use App\Support\Identity;
use App\Services\ChainVerifier;
use App\Services\PaylaterVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    /** Cari penerima via No HP / email / wallet → nama + wallet (untuk kirim). */
    public function lookup(Request $req)
    {
        $q = trim((string) $req->input('q'));
        if ($q === '') {
            return response()->json(['found' => false]);
        }

        $user = null;
        if (preg_match('/^0x[a-fA-F0-9]{40}$/', $q)) {
            $user = User::where('wallet_address', strtolower($q))->first();
            if (!$user) {
                // Wallet valid tapi bukan user terdaftar — tetap boleh kirim (nama tak diketahui).
                return response()->json(['found' => true, 'name' => null, 'wallet' => strtolower($q)]);
            }
        } else {
            // Cari via blind index phone_hash (phone terenkripsi) atau email.
            $user = User::where('phone_hash', User::hashPhone($q))->orWhere('email', $q)->first();
        }

        if (!$user || !$user->wallet_address) {
            return response()->json(['found' => false]);
        }
        if ($user->id === auth()->id()) {
            return response()->json(['found' => false, 'self' => true]);
        }

        return response()->json([
            'found'  => true,
            'name'   => $user->public_name ?: $user->name,
            'wallet' => strtolower($user->wallet_address),
        ]);
    }

    /** Dompet: kirim TLKM, minta uang, riwayat. */
    public function index()
    {
        $wallet = strtolower(auth()->user()->wallet_address ?? '');

        $transfers = $wallet
            ? Transfer::where('from_wallet', $wallet)->orWhere('to_wallet', $wallet)->latest()->limit(20)->get()
                ->map(fn ($t) => [
                    'dir'      => $t->from_wallet === $wallet ? 'out' : 'in',
                    'other'    => Identity::resolve($t->from_wallet === $wallet ? $t->to_wallet : $t->from_wallet),
                    'amount'   => $t->amount,
                    'note'     => $t->note,
                    'tx'       => $t->tx_hash,
                    'at'       => $t->created_at,
                ])
            : collect();

        $requests = PaymentRequest::where('user_id', auth()->id())->latest()->get();

        // ===== Paylater (kredit berjaminan on-chain, DEMO). Kebenaran posisi dari chain. =====
        // Aman-nonaktif bila PAYLATER_ADDRESS kosong.
        $pv = new PaylaterVerifier();
        $paylater = ['configured' => $pv->configured()];
        if ($pv->configured()) {
            $pos     = $wallet ? $pv->positionOf($wallet) : null;
            $canMint = $pv->canMint(); // model CDP: kontrak mencetak TLKM sesuai kebutuhan
            $fmt = fn ($wei, $dp = 6) => $wei !== null
                ? rtrim(rtrim(bcdiv((string) $wei, bcpow('10', '18'), $dp), '0'), '.')
                : null;

            $paylater += [
                'collateral' => $pos ? $fmt($pos['collateral']) : '0',                 // tBNB
                'limit'      => $pos ? $fmt($pos['limit'], 2) : '0',                    // TLKM
                'due_amount' => $pos ? $fmt($pos['due_amount'], 2) : '0',              // TLKM (pokok+bunga) tampilan
                'due_amount_raw' => $pos ? ($fmt($pos['due_amount'], 18) ?: '0') : '0', // eksak utk lunasi penuh
                'principal'  => $pos ? $fmt($pos['principal'], 2) : '0',               // TLKM
                'available'  => $pos ? ($fmt(bcsub($pos['limit'], $pos['principal']), 2) ?: '0') : '0',
                'can_mint'   => $canMint,                                               // true = mint aktif (siap dipinjam)
                'due_date'   => ($pos && $pos['due_date'] > 0) ? \Carbon\Carbon::createFromTimestamp($pos['due_date']) : null,
                'has_debt'   => $pos ? bccomp($pos['due_amount'], '0') > 0 : false,
                'rate'       => (int) config('chain.paylater_rate_tlkm_per_bnb', 1000000),
                'interest_bps' => (int) config('chain.paylater_interest_bps', 300),
                'contract'   => $pv->address(), // alamat kontrak Paylater
            ];

            // ===== Sisi PENYUPLAI (lender/earn) — 3 jangka + statistik pool =====
            $stats = $pv->poolStats();
            $termLabels = [0 => 'Fleksibel', 1 => 'Tetap 30 hari', 2 => 'Tetap 90 hari'];
            $terms = [];
            foreach ([0, 1, 2] as $t) {
                $bi  = $pv->bucketInfo($t);
                $spi = $wallet ? $pv->supplierInfo($wallet, $t) : null;
                $earnedWei = ($spi && bccomp($spi['value'], $spi['principal']) > 0) ? bcsub($spi['value'], $spi['principal']) : '0';
                $maturity  = ($spi && $spi['maturity'] > 0) ? \Carbon\Carbon::createFromTimestamp($spi['maturity']) : null;
                $terms[$t] = [
                    'label'      => $termLabels[$t],
                    'nisbah'     => $bi ? (int) round($bi['nisbah_bps'] / 100) : null,     // % penyuplai
                    'lock_days'  => $bi ? (int) round($bi['lock'] / 86400) : 0,             // hari kunci
                    'value'      => $spi ? ($fmt($spi['value'], 2) ?: '0') : '0',           // klaim (pokok+yield)
                    'principal'  => $spi ? ($fmt($spi['principal'], 2) ?: '0') : '0',       // setoran
                    'earned'     => $fmt($earnedWei, 4) ?: '0',                             // untung
                    'shares_raw' => $spi ? ($spi['shares'] ?: '0') : '0',                   // eksak utk tarik semua
                    'has_pos'    => $spi && bccomp($spi['shares'], '0') > 0,
                    'maturity'   => $maturity,
                    'matured'    => $t === 0 || ($maturity && $maturity->isPast()),
                ];
            }
            $paylater['supply'] = [
                'terms'     => $terms,
                'liquidity' => $stats ? ($fmt($stats['liquidity'], 2) ?: '0') : '0',
                'borrows'   => $stats ? ($fmt($stats['borrows'], 2) ?: '0') : '0',
                'assets'    => $stats ? ($fmt($stats['assets'], 2) ?: '0') : '0',
                'reserve'   => $stats ? ($fmt($stats['reserve'], 2) ?: '0') : '0',
                'util'      => $stats ? round(((int) $stats['util_bps']) / 100, 1) : null,
            ];
        }
        $paylaterHistory = PaylaterLoan::where('user_id', auth()->id())->latest()->limit(20)->get();

        return view('wallet.index', compact('wallet', 'transfers', 'requests', 'paylater', 'paylaterHistory'));
    }

    /** Catat transfer TLKM setelah verifikasi on-chain. */
    public function send(Request $req, ChainVerifier $verifier)
    {
        $data = $req->validate([
            'tx_hash'    => 'required|string|size:66',
            'note'       => 'nullable|string|max:120',
            'request_id' => 'nullable|integer|exists:payment_requests,id',
        ]);

        if (Transfer::where('tx_hash', $data['tx_hash'])->exists()) {
            return response()->json(['success' => true]);
        }

        $from = strtolower(auth()->user()->wallet_address ?? '');
        $v = $verifier->verifyTransfer($data['tx_hash'], $from);
        if (!$v['ok']) {
            return response()->json(['success' => false, 'message' => $v['reason']], 422);
        }

        try {
            Transfer::create([
                'from_wallet'  => $v['from'],
                'to_wallet'    => $v['to'],
                'amount'       => $v['amount_tlkm'],
                'note'         => $data['note'] ?? null,
                'request_id'   => $data['request_id'] ?? null,
                'tx_hash'      => $data['tx_hash'],
                'block_number' => $v['block_number'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => true, 'amount' => $v['amount_tlkm']]);
    }

    /** Buat permintaan uang (Minta Uang) — hasilkan kode utk link/QR. */
    public function createRequest(Request $req)
    {
        $data = $req->validate([
            'amount' => 'nullable|numeric|min:0',
            'note'   => 'nullable|string|max:120',
        ]);

        if (!auth()->user()->wallet_address) {
            return back()->with('error', 'Akun belum punya wallet untuk menerima uang.');
        }

        do {
            $code = Str::upper(Str::random(8));
        } while (PaymentRequest::where('code', $code)->exists());

        PaymentRequest::create([
            'code'    => $code,
            'user_id' => auth()->id(),
            'amount'  => $data['amount'] ?? null,
            'note'    => $data['note'] ?? null,
        ]);

        return redirect('/wallet')->with('success', 'Permintaan uang dibuat. Bagikan link atau QR-nya.');
    }

    /** Halaman bayar sebuah permintaan (publik) — siapa saja bisa membayar. */
    public function pay(string $code)
    {
        $request = PaymentRequest::where('code', $code)->firstOrFail();
        $recipient = $request->user;

        abort_unless($recipient && $recipient->wallet_address, 404, 'Penerima tidak punya wallet.');

        $identity = Identity::resolve($recipient->wallet_address);

        return view('wallet.pay', [
            'req'       => $request,
            'recipient' => strtolower($recipient->wallet_address),
            'identity'  => $identity,
        ]);
    }
}

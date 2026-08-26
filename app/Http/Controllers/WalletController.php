<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Support\Identity;
use App\Services\SepoliaVerifier;
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
            $user = User::where('phone', $q)->orWhere('email', $q)->first();
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

        return view('wallet.index', compact('wallet', 'transfers', 'requests'));
    }

    /** Catat transfer TLKM setelah verifikasi on-chain. */
    public function send(Request $req, SepoliaVerifier $verifier)
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

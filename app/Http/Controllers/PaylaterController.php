<?php

namespace App\Http\Controllers;

use App\Models\PaylaterLoan;
use App\Services\PaylaterVerifier;
use Illuminate\Http\Request;

/**
 * Paylater — kredit berjaminan on-chain (DEMO testnet). Posisi (agunan/utang/limit)
 * SELALU dibaca dari chain via PaylaterVerifier (sumber kebenaran). Tabel paylater_loans
 * hanya cermin aksi untuk riwayat. Eksekusi tx: MetaMask (frontend) atau PIN (PinTxController).
 */
class PaylaterController extends Controller
{
    public function index(PaylaterVerifier $verifier)
    {
        $user   = auth()->user();
        $wallet = strtolower((string) $user->wallet_address);

        $configured   = $verifier->configured();
        $pos          = $configured ? $verifier->positionOf($wallet) : null;
        $liquidityWei = $configured ? $verifier->ownerFundInfo() : null;

        // wei (18 desimal) -> string human.
        $fmt = fn ($wei, $dp = 6) => $wei !== null
            ? rtrim(rtrim(bcdiv((string) $wei, bcpow('10', '18'), $dp), '0'), '.')
            : null;

        $available = $pos ? bcsub($pos['limit'], $pos['debt']) : '0';
        $position = [
            'collateral' => $pos ? $fmt($pos['collateral']) : '0',       // tBNB
            'debt'       => $pos ? $fmt($pos['debt'], 2) : '0',          // TLKM
            'limit'      => $pos ? $fmt($pos['limit'], 2) : '0',         // TLKM
            'available'  => $fmt($available, 2) ?: '0',                  // sisa limit (TLKM)
            'due_date'   => ($pos && $pos['due_date'] > 0) ? \Carbon\Carbon::createFromTimestamp($pos['due_date']) : null,
            'has_debt'   => $pos ? bccomp($pos['debt'], '0') > 0 : false,
        ];
        $liquidity = $fmt($liquidityWei, 2);

        $history = PaylaterLoan::where('user_id', $user->id)->latest()->limit(30)->get();
        $rate    = (int) config('chain.paylater_rate_tlkm_per_bnb', 1000000);

        return view('paylater.index', compact('configured', 'position', 'liquidity', 'history', 'rate', 'wallet'));
    }

    /** Dipanggil frontend SETELAH tx sukses; simpan jejak aksi (cegah duplikat tx_hash). */
    public function record(Request $req, PaylaterVerifier $verifier)
    {
        $data = $req->validate([
            'action'  => 'required|in:deposit,borrow,repay,withdraw,seize',
            'amount'  => 'required|numeric|min:0',
            'tx_hash' => ['required', 'regex:/^0x[0-9a-fA-F]{64}$/'],
        ]);

        // Verifikasi ringan: fitur harus aktif (kontrak dikonfigurasi). Posisi tetap dibaca
        // dari chain saat ditampilkan, jadi baris ini hanya jejak riwayat.
        abort_unless($verifier->configured(), 422, 'Paylater belum dikonfigurasi.');

        $user = auth()->user();
        $tx   = strtolower($data['tx_hash']);

        if (PaylaterLoan::where('tx_hash', $tx)->exists()) {
            return response()->json(['success' => true, 'duplicate' => true]);
        }

        PaylaterLoan::create([
            'user_id'        => $user->id,
            'wallet_address' => strtolower((string) $user->wallet_address),
            'action'         => $data['action'],
            'amount'         => $data['amount'],
            'tx_hash'        => $tx,
        ]);

        return response()->json(['success' => true]);
    }
}

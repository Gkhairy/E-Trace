<?php

namespace App\Http\Controllers;

use App\Models\PaylaterLoan;
use App\Services\PaylaterVerifier;
use Illuminate\Http\Request;

/**
 * Paylater — kredit berjaminan on-chain DENGAN BUNGA (DEMO testnet). Posisi
 * (agunan/kewajiban/limit) SELALU dibaca dari chain via PaylaterVerifier (sumber
 * kebenaran) dan ditampilkan di halaman Wallet. Tabel paylater_loans hanya cermin
 * aksi untuk riwayat. Eksekusi tx: MetaMask (frontend) atau PIN (PinTxController).
 */
class PaylaterController extends Controller
{
    /** Dipanggil frontend SETELAH tx sukses; simpan jejak aksi (cegah duplikat tx_hash). */
    public function record(Request $req, PaylaterVerifier $verifier)
    {
        $data = $req->validate([
            'action'  => 'required|in:deposit,borrow,repay,withdraw,seize,supply,withdraw_supply',
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

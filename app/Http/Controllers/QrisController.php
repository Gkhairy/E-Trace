<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * PROTOTIPE — Bayar QRIS pakai stablecoin (ala Bitget Wallet bayar QRIS pakai USDC).
 * PENTING: ini SIMULASI. TIDAK ada transaksi on-chain / settlement nyata ke merchant.
 * Hasilnya hanya "receipt simulasi" untuk demo ide. PIN tetap diverifikasi sungguhan.
 */
class QrisController extends Controller
{
    private const RATE_IDR_PER_USDC = 16000; // kurs mock untuk prototipe

    public function pay(Request $req)
    {
        $data = $req->validate([
            'pin'      => 'required|digits:6',
            'merchant' => 'required|string|max:120',
            'city'     => 'nullable|string|max:80',
            'amount'   => 'required|numeric|min:1', // Rupiah
        ]);

        // Verifikasi PIN (berlaku untuk semua akun — embedded maupun MetaMask).
        $user = auth()->user();
        abort_unless($user->pin_hash, 422, 'Akun belum punya PIN.');
        abort_if($user->pinLocked(), 423, 'PIN terkunci sementara. Coba lagi nanti.');
        if (!Hash::check($data['pin'], $user->pin_hash)) {
            $user->increment('pin_attempts');
            if ($user->pin_attempts >= 5) {
                $user->forceFill(['pin_locked_until' => now()->addMinutes(15), 'pin_attempts' => 0])->save();
                abort(423, 'PIN salah 5×. Dikunci 15 menit.');
            }
            abort(422, 'PIN salah. Sisa percobaan: ' . max(0, 5 - $user->pin_attempts) . '.');
        }
        $user->forceFill(['pin_attempts' => 0])->save();

        // ===== SIMULASI: tidak ada pemindahan dana nyata =====
        $idr  = (float) $data['amount'];
        $usdc = round($idr / self::RATE_IDR_PER_USDC, 6);

        return response()->json([
            'success'    => true,
            'simulation' => true,
            'receipt'    => [
                'ref'               => 'ETRC-' . strtoupper(Str::random(10)),
                'merchant'          => $data['merchant'],
                'city'              => $data['city'] ?? null,
                'amount_idr'        => $idr,
                'stablecoin'        => 'USDC',
                'stablecoin_amount' => $usdc,
                'rate'              => self::RATE_IDR_PER_USDC,
                'payer'             => $user->name,
                'time'              => now()->format('d M Y, H:i'),
            ],
        ]);
    }
}

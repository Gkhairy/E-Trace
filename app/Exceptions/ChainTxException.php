<?php

namespace App\Exceptions;

use Illuminate\Http\Request;

/**
 * Transaksi on-chain gagal dikirim (gas kurang, RPC menolak, dsb).
 * Untuk request JSON dikembalikan sebagai 422 berisi pesan yang bisa dibaca user,
 * bukan 500 "Server Error" yang menyembunyikan penyebabnya.
 */
class ChainTxException extends \RuntimeException
{
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }
        return null; // halaman biasa: pakai penanganan error bawaan
    }
}

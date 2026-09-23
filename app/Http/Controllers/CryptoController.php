<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CryptoController extends Controller
{
    /**
     * Data untuk ticker marquee (TOP 10 coin) — endpoint PUBLIK GET /api/ticker.
     * Respons di-cache 3 menit supaya hemat kuota CMC & cepat.
     * Aman bila API gagal: pakai cadangan terakhir, atau kosong (tidak error).
     */
    public function ticker()
    {
        // 1) Data segar dari cache pendek.
        $fresh = Cache::get('cmc_ticker_top10');
        if ($fresh !== null) {
            return response()->json(['data' => $fresh]);
        }

        // 2) Ambil dari CMC.
        $data = $this->fetchTicker();

        if ($data !== null) {
            Cache::put('cmc_ticker_top10', $data, now()->addMinutes(3));   // segar 3 menit
            Cache::put('cmc_ticker_backup', $data, now()->addDay());       // cadangan 1 hari
            return response()->json(['data' => $data]);
        }

        // 3) API gagal -> pakai cadangan terakhir kalau ada, kalau tidak kosong.
        return response()->json(['data' => Cache::get('cmc_ticker_backup', [])]);
    }

    /**
     * Panggil CMC listings/latest (limit 10). Return array coin ringkas, atau null bila gagal.
     */
    private function fetchTicker(): ?array
    {
        try {
            $response = Http::timeout(8)->withHeaders([
                'X-CMC_PRO_API_KEY' => config('services.cmc.key'),
            ])->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest', [
                'limit'   => 10,
                'convert' => 'USD',
            ]);

            if (!$response->successful()) {
                return null;
            }

            return collect($response->json()['data'] ?? [])->map(fn ($c) => [
                'id'     => $c['id'] ?? null,
                'symbol' => $c['symbol'] ?? '',
                'price'  => $c['quote']['USD']['price'] ?? null,
                'change' => $c['quote']['USD']['percent_change_24h'] ?? null,
            ])->values()->all();
        } catch (\Throwable $e) {
            return null;
        }
    }
}

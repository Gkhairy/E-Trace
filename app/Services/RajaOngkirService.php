<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ongkir via RajaOngkir (Komerce API v1).
 * Docs: https://rajaongkir.com/docs/shipping-cost/getting_started/about
 *
 * Aktif hanya bila RAJAONGKIR_API_KEY diisi di .env. Bila tidak/gagal, pemanggil
 * memakai fallback estimasi jarak (ShippingService).
 *
 * Alur: nama kota -> id lokasi (destination search) -> hitung tarif termurah.
 * Return biaya Rupiah (int) atau null.
 */
class RajaOngkirService
{
    private function key(): string
    {
        return (string) config('chain.shipping.rajaongkir.key', '');
    }

    private function base(): string
    {
        return rtrim((string) config('chain.shipping.rajaongkir.base', 'https://rajaongkir.komerce.id/api/v1'), '/');
    }

    public function configured(): bool
    {
        return $this->key() !== '';
    }

    /** Cari id lokasi (subdistrict) dari nama kota. Di-cache 7 hari. */
    public function locationId(?string $city): ?int
    {
        $city = trim((string) $city);
        if ($city === '' || !$this->configured()) {
            return null;
        }
        // Ambil kata kunci kota terakhir (buang alamat panjang, ambil nama kota).
        $needle = $this->cityKeyword($city);

        return Cache::remember('rajaongkir:loc:' . strtolower($needle), 60 * 24 * 7, function () use ($needle) {
            try {
                $res = Http::withHeaders(['key' => $this->key()])->timeout(15)
                    ->get($this->base() . '/destination/domestic-destination', [
                        'search' => $needle, 'limit' => 5, 'offset' => 0,
                    ]);
                if (!$res->ok()) {
                    return null;
                }
                $rows = $res->json('data') ?? [];
                return isset($rows[0]['id']) ? (int) $rows[0]['id'] : null;
            } catch (\Throwable $e) {
                Log::warning('RajaOngkir locationId gagal: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Tarif termurah dari kota asal ke kota tujuan (Rupiah), atau null.
     */
    public function tariff(?string $originCity, ?string $destCity, int $weightGrams = 1000): ?int
    {
        if (!$this->configured()) {
            return null;
        }
        $originId = $this->locationId($originCity);
        $destId   = $this->locationId($destCity);
        if (!$originId || !$destId) {
            return null;
        }

        // Tarif jarang berubah tapi panggilannya mahal (~2,5 dt) dan dipanggil per
        // penjual tiap render checkout. Cache hasilnya; kegagalan di-cache singkat
        // supaya API yang sedang bermasalah tidak menahan halaman berulang kali.
        $ck = "rajaongkir:tariff:{$originId}:{$destId}:" . max(1, $weightGrams);
        if (($hit = Cache::get($ck)) !== null) {
            return $hit === 'null' ? null : (int) $hit;
        }
        $store = function (?int $v) use ($ck) {
            Cache::put($ck, $v === null ? 'null' : $v, $v === null ? 300 : 60 * 60 * 12);
            return $v;
        };

        try {
            $res = Http::withHeaders(['key' => $this->key()])->asForm()->timeout(20)
                ->post($this->base() . '/calculate/domestic-cost', [
                    'origin'      => $originId,
                    'destination' => $destId,
                    'weight'      => max(1, $weightGrams),
                    'courier'     => (string) config('chain.shipping.rajaongkir.couriers', 'jne:sicepat:jnt:ide:pos:tiki'),
                    'price'       => 'lowest',
                ]);
            if (!$res->ok()) {
                return $store(null);
            }
            // Struktur: { data: [ { name, service, cost, etd }, ... ] } (cost dalam Rupiah).
            $rows = $res->json('data') ?? $res->json('data.calculate_reguler') ?? [];
            $costs = collect($rows)->pluck('cost')->filter(fn ($c) => is_numeric($c) && $c > 0);
            return $store($costs->isNotEmpty() ? (int) $costs->min() : null);
        } catch (\Throwable $e) {
            Log::warning('RajaOngkir tariff gagal: ' . $e->getMessage());
            return $store(null);
        }
    }

    /** Ambil nama kota dari alamat bebas (ambil segmen bermakna terakhir). */
    private function cityKeyword(string $address): string
    {
        // Pisah dgn koma; pakai potongan terakhir yang tidak berupa kode pos.
        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $p = preg_replace('/\b\d{4,6}\b/', '', $parts[$i]); // buang kode pos
            $p = trim(preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $p));
            if ($p !== '') {
                return $p;
            }
        }
        return $address;
    }
}

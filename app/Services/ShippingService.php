<?php

namespace App\Services;

/**
 * Estimasi ongkos kirim untuk produk fisik (H7).
 *
 * Metode: jarak GARIS LURUS (haversine) antara kota toko dan kota pembeli,
 * memakai tabel koordinat bawaan (gratis, tanpa API eksternal / map berat).
 * Cocok untuk aproksimasi area Jabodetabek & Pulau Jawa. Rumus biaya diambil
 * dari config('chain.shipping') sehingga mudah diubah.
 *
 * (Alternatif yang bisa dipasang nanti: OSRM/OpenRouteService untuk jarak rute
 * sebenarnya, atau Dijkstra/A* bila memakai graf peta sendiri.)
 */
class ShippingService
{
    /** Koordinat [lat, lng] kota-kota utama Jabodetabek & Jawa. Key = lowercase. */
    private const CITY_COORDS = [
        'jakarta' => [-6.2088, 106.8456], 'jakarta pusat' => [-6.1862, 106.8340],
        'jakarta selatan' => [-6.2615, 106.8106], 'jakarta barat' => [-6.1683, 106.7588],
        'jakarta timur' => [-6.2250, 106.9004], 'jakarta utara' => [-6.1214, 106.7741],
        'bogor' => [-6.5950, 106.8166], 'depok' => [-6.4025, 106.7942],
        'tangerang' => [-6.1783, 106.6319], 'tangerang selatan' => [-6.2884, 106.7178],
        'bekasi' => [-6.2383, 106.9756], 'bandung' => [-6.9175, 107.6191],
        'cimahi' => [-6.8722, 107.5425], 'sukabumi' => [-6.9277, 106.9300],
        'cirebon' => [-6.7320, 108.5523], 'tasikmalaya' => [-7.3506, 108.2172],
        'semarang' => [-6.9932, 110.4203], 'solo' => [-7.5755, 110.8243],
        'surakarta' => [-7.5755, 110.8243], 'yogyakarta' => [-7.7956, 110.3695],
        'jogja' => [-7.7956, 110.3695], 'magelang' => [-7.4706, 110.2178],
        'surabaya' => [-7.2575, 112.7521], 'malang' => [-7.9666, 112.6326],
        'sidoarjo' => [-7.4478, 112.7183], 'gresik' => [-7.1560, 112.6516],
        'kediri' => [-7.8480, 112.0178], 'madiun' => [-7.6298, 111.5300],
        'purwokerto' => [-7.4213, 109.2346], 'tegal' => [-6.8694, 109.1402],
        'pekalongan' => [-6.8898, 109.6753], 'serang' => [-6.1200, 106.1503],
        'cilegon' => [-6.0025, 106.0113], 'karawang' => [-6.3227, 107.3376],
        'purwakarta' => [-6.5569, 107.4431], 'garut' => [-7.2145, 107.9081],
    ];

    /** Normalisasi & cari koordinat kota (pencocokan substring toleran). */
    public function coordsFor(?string $city): ?array
    {
        if (!$city) {
            return null;
        }
        $key = trim(mb_strtolower($city));
        if (isset(self::CITY_COORDS[$key])) {
            return self::CITY_COORDS[$key];
        }
        // Cocokkan bila nama kota terkandung (mis. "Kota Bandung" -> "bandung").
        foreach (self::CITY_COORDS as $name => $coord) {
            if (str_contains($key, $name)) {
                return $coord;
            }
        }
        return null;
    }

    /** Jarak haversine (km) antara dua titik [lat,lng]. */
    public function haversineKm(array $a, array $b): float
    {
        $r = 6371.0; // radius bumi (km)
        $dLat = deg2rad($b[0] - $a[0]);
        $dLon = deg2rad($b[1] - $a[1]);
        $lat1 = deg2rad($a[0]);
        $lat2 = deg2rad($b[0]);
        $h = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
        return $r * 2 * asin(min(1.0, sqrt($h)));
    }

    /**
     * Hitung estimasi ongkir dari kota toko ke kota pembeli.
     * Utama: J&T Tariff API (bila dikonfigurasi). Fallback: jarak haversine.
     * Return: ['fee'=>Rp int, 'fee_tlkm'=>float, 'km'=>float|null, 'known'=>bool,
     *          'method'=>string, 'note'=>string].
     */
    public function estimate(?string $fromCity, ?string $toCity): array
    {
        $cfg = config('chain.shipping');
        $rpPerTlkm = max(1, (int) config('chain.rp_per_tlkm', 1000));
        $toTlkm = fn (int $rp) => round($rp / $rpPerTlkm, 2);

        // 1) Coba RajaOngkir (nyata) bila API key diisi.
        $weightGrams = (int) round(((float) ($cfg['weight_kg'] ?? 1)) * 1000);
        $ro = app(RajaOngkirService::class)->tariff($fromCity, $toCity, $weightGrams);
        if ($ro !== null) {
            return [
                'fee' => $ro, 'fee_tlkm' => $toTlkm($ro), 'km' => null, 'known' => true,
                'method' => 'RajaOngkir', 'note' => 'Tarif termurah via RajaOngkir.',
            ];
        }

        // 2) Fallback: estimasi jarak garis lurus (gratis, tanpa API).
        $a = $this->coordsFor($fromCity);
        $b = $this->coordsFor($toCity);

        if (!$a || !$b) {
            $fee = (int) $cfg['fallback_fee'];
            return [
                'fee' => $fee, 'fee_tlkm' => $toTlkm($fee), 'km' => null, 'known' => false,
                'method' => 'estimasi', 'note' => 'Kota tidak dikenal — tarif dasar.',
            ];
        }

        $km = $this->haversineKm($a, $b);
        $fee = (int) $cfg['base_fee'];
        if ($km > $cfg['base_km']) {
            $extra = $km - $cfg['base_km'];
            $steps = (int) ceil($extra / max(1, (int) $cfg['step_km']));
            $fee += $steps * (int) $cfg['step_fee'];
        }

        return [
            'fee' => $fee, 'fee_tlkm' => $toTlkm($fee), 'km' => round($km, 1), 'known' => true,
            'method' => 'estimasi jarak', 'note' => 'Estimasi jarak garis lurus (haversine).',
        ];
    }

    /**
     * Estimasi ETA kurir (jumlah hari) dari kota toko ke kota pembeli. Heuristik
     * jarak sederhana — dipakai untuk promised_date Garansi Tepat Waktu
     * (promised = now + etaDays + insurance.eta_buffer_days).
     */
    public function etaDays(?string $fromCity, ?string $toCity): int
    {
        $a = $this->coordsFor($fromCity);
        $b = $this->coordsFor($toCity);
        if (!$a || !$b) {
            return 4; // kota tak dikenal — asumsi konservatif 4 hari.
        }
        $km = $this->haversineKm($a, $b);
        // ~1 hari per 400 km + 1 hari proses; dibatasi 1..7 hari.
        return max(1, min(7, (int) ceil($km / 400) + 1));
    }
}

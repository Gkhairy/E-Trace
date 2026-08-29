<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ongkir via J&T Express Tariff API.
 *
 * CATATAN: J&T mewajibkan registrasi di https://developer.jet.co.id + API key +
 * tanda tangan (MD5/Base64) + proses "mapping" nama district/city/province ke
 * area code milik J&T. Karena itu API ini AKTIF hanya bila kredensial diisi di
 * .env (JNT_API_KEY, JNT_SENDER_CODE). Bila tidak, pemanggil memakai fallback
 * estimasi jarak (ShippingService).
 *
 * Return: biaya Rupiah (int) atau null bila tidak dikonfigurasi/gagal.
 */
class JntService
{
    public function configured(): bool
    {
        return (bool) config('chain.shipping.jnt.enabled')
            && config('chain.shipping.jnt.api_key');
    }

    /**
     * Cek tarif J&T. $destCity/$originCity = nama kota (akan dipetakan ke area code
     * oleh J&T sesuai mapping yang sudah didaftarkan). Weight dalam kg.
     */
    public function tariff(?string $originCity, ?string $destCity, float $weightKg = 1): ?int
    {
        if (!$this->configured() || !$originCity || !$destCity) {
            return null;
        }

        try {
            $key    = (string) config('chain.shipping.jnt.api_key');
            $sender = (string) config('chain.shipping.jnt.sender');
            // Payload umum tarif J&T (nama field bisa disesuaikan dgn kontrak akunmu).
            $data = [
                'weight'      => max(1, (int) ceil($weightKg)),
                'sendSiteCode'=> $sender ?: $originCity,
                'destAreaCode'=> $destCity,
                'origin'      => $originCity,
                'destination' => $destCity,
            ];
            // Tanda tangan J&T: base64(md5(json(data) + apiKey)).
            $sign = base64_encode(md5(json_encode($data) . $key, true));

            $res = Http::timeout(15)->asForm()->post(config('chain.shipping.jnt.url'), [
                'data' => json_encode($data),
                'sign' => $sign,
            ]);
            if (!$res->ok()) {
                return null;
            }
            $json = $res->json();

            // Ambil tarif termurah dari respons (struktur bisa bervariasi antar akun).
            $cost = data_get($json, 'content.0.cost')
                ?? data_get($json, 'data.0.rate')
                ?? data_get($json, 'tariff')
                ?? null;

            return $cost !== null ? (int) $cost : null;
        } catch (\Throwable $e) {
            Log::warning('J&T tariff gagal: ' . $e->getMessage());
            return null;
        }
    }
}

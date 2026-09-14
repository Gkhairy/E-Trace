<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeliveryAI — mengklasifikasi status pengiriman sebuah order dari riwayat tracking
 * mentah (lintas kurir, tidak terstruktur) memakai LLM (gpt-4o-mini) dan MEMUTUSKAN
 * penyelesaian escrow: release / refund / hold. Untuk order berasuransi juga menilai
 * tepat waktu vs telat + PENYEBAB telat.
 *
 * Prinsip: hanya mengklasifikasi dari data tracking; tidak mengarang. Ragu → 'hold'.
 * Sumber kebenaran waktu-telat & pembayaran tetap dihitung/di-enforce server (keeper);
 * AI hanya memberi klasifikasi + keyakinan. Aman-nonaktif: tanpa OpenAI key → 'hold'.
 */
class DeliveryAI
{
    /**
     * @param array  $events        daftar ['at'=>ISO string, 'text'=>string, 'source'=>string]
     * @param string $promisedDate  ISO estimasi tiba yang dijanjikan (ETA + buffer)
     * @param int    $graceDays     toleransi telat (hari)
     * @param float  $orderValueTlkm nilai order (TLKM)
     * @param float  $shippingTlkm  ongkir order (TLKM)
     * @return array {settlement, delivered, delivered_at, late, late_cause, confidence, reason}
     */
    public function decide(array $events, string $promisedDate, int $graceDays, float $orderValueTlkm, float $shippingTlkm): array
    {
        // Tanpa data tracking sama sekali → tak ada dasar memutuskan.
        if (empty($events)) {
            return $this->hold('Belum ada data tracking untuk dinilai.');
        }

        $key = config('services.openai.key');
        if (empty($key)) {
            return $this->hold('AI tidak tersedia (OpenAI belum dikonfigurasi) — perlu tinjauan pengawas.');
        }

        $lines = [];
        foreach ($events as $e) {
            $lines[] = '- [' . ($e['at'] ?? '?') . '] (' . ($e['source'] ?? 'courier') . ') ' . trim((string) ($e['text'] ?? ''));
        }
        $tracking = implode("\n", $lines);

        $system = <<<'SYS'
Kamu mesin klasifikasi pengiriman untuk escrow marketplace. Tugasmu HANYA membaca
riwayat tracking (lintas kurir, bisa berantakan) lalu mengklasifikasikannya. JANGAN
mengarang fakta yang tidak ada di tracking. Kalau ragu atau data tak cukup, pilih
settlement "hold".

Aturan keputusan:
- settlement "release": ada bukti kuat paket TERKIRIM/diterima pembeli.
- settlement "refund": ada bukti kuat pengiriman GAGAL permanen (dikembalikan ke
  pengirim, paket hilang, dibatalkan) sehingga pembeli tak menerima barang.
- settlement "hold": masih dalam perjalanan, ambigu, atau bukti tak cukup.

Penilaian telat (untuk asuransi):
- late true bila paket tiba/masih berjalan melewati tanggal dijanjikan + toleransi.
- late_cause:
  - "seller_courier": penjual telat proses / kurir lambat / salah rute (DITANGGUNG).
  - "buyer": alamat salah, penerima tak ada, nomor tak bisa dihubungi (TIDAK ditanggung).
  - "force_majeure": cuaca, bencana, libur nasional, kerusuhan (TIDAK ditanggung).
  - "unknown": penyebab tak jelas dari tracking.

Balas HANYA JSON valid dengan skema PERSIS:
{"settlement":"release|refund|hold","delivered":true|false,"delivered_at":"YYYY-MM-DD atau null","late":true|false,"late_cause":"seller_courier|buyer|force_majeure|unknown","confidence":0..1,"reason":"kalimat singkat Bahasa Indonesia"}
SYS;

        $user = "Tanggal dijanjikan (ETA+buffer): {$promisedDate}\n"
              . "Toleransi telat: {$graceDays} hari\n"
              . "Nilai order: {$orderValueTlkm} TLKM · Ongkir: {$shippingTlkm} TLKM\n\n"
              . "Riwayat tracking:\n{$tracking}";

        try {
            $res = Http::withToken($key)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'           => config('services.openai.model', 'gpt-4o-mini'),
                'messages'        => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'temperature'     => 0,
                'max_tokens'      => 300,
                'response_format' => ['type' => 'json_object'],
            ]);
            if (!$res->ok()) {
                return $this->hold('AI sedang sibuk — perlu tinjauan pengawas.');
            }
            $raw = (string) $res->json('choices.0.message.content');
            $out = json_decode($raw, true);
            if (!is_array($out)) {
                return $this->hold('Jawaban AI tidak terbaca — perlu tinjauan pengawas.');
            }
            return $this->normalize($out);
        } catch (\Throwable $e) {
            Log::warning('DeliveryAI gagal: ' . $e->getMessage());
            return $this->hold('Kendala menghubungi AI — perlu tinjauan pengawas.');
        }
    }

    /** Validasi & rapikan output LLM ke skema tetap; nilai aneh → 'hold'. */
    private function normalize(array $o): array
    {
        $settlement = in_array(($o['settlement'] ?? null), ['release', 'refund', 'hold'], true) ? $o['settlement'] : 'hold';
        $cause = in_array(($o['late_cause'] ?? null), ['seller_courier', 'buyer', 'force_majeure', 'unknown'], true) ? $o['late_cause'] : 'unknown';
        $conf = (float) ($o['confidence'] ?? 0);
        $conf = max(0.0, min(1.0, $conf));
        $deliveredAt = $o['delivered_at'] ?? null;
        if (!is_string($deliveredAt) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $deliveredAt)) {
            $deliveredAt = null;
        }
        return [
            'settlement'   => $settlement,
            'delivered'    => (bool) ($o['delivered'] ?? false),
            'delivered_at' => $deliveredAt,
            'late'         => (bool) ($o['late'] ?? false),
            'late_cause'   => $cause,
            'confidence'   => round($conf, 2),
            'reason'       => mb_substr(trim((string) ($o['reason'] ?? '')), 0, 300) ?: 'Tanpa alasan.',
        ];
    }

    private function hold(string $reason): array
    {
        return [
            'settlement'   => 'hold',
            'delivered'    => false,
            'delivered_at' => null,
            'late'         => false,
            'late_cause'   => 'unknown',
            'confidence'   => 0.0,
            'reason'       => $reason,
        ];
    }
}

<?php

namespace App\Services\Disaster;

use App\Models\DisasterEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menilai sebuah kejadian: benar-benar bencana yang berdampak ke warga? Seberapa parah?
 * Kejadian yang sama dengan yang sudah tercatat? Sekalian menulis judul & deskripsi
 * campaign yang HANYA memakai fakta dari data sumber.
 *
 * AI hanya memberi penilaian; keputusan membuka campaign tetap diambil DisasterRadar
 * dari skor ini (ambang buka otomatis & batas harian).
 * Tanpa OpenAI key: penilaian aturan sederhana untuk sumber resmi, berita ke pengawas.
 */
class DisasterAI
{
    /**
     * @param array $e      kejadian (bentuk DisasterSources)
     * @param array $recent kejadian relevan 14 hari terakhir: [id, title, location, occurred_at]
     * @return array {is_disaster, type, location, severity, reason, title, description, same_as}
     */
    public function assess(array $e, array $recent = []): array
    {
        $key = config('services.openai.key');
        if (empty($key)) {
            return $this->fallback($e, 'AI tidak tersedia (OpenAI belum dikonfigurasi) — dinilai dengan aturan dasar.');
        }

        $types = implode('|', array_keys(DisasterEvent::TYPES));
        $system = <<<SYS
Kamu analis tanggap bencana untuk platform donasi transparan di Indonesia. Nilai SATU
kejadian dari data sumber. JANGAN mengarang angka korban, kerusakan, atau fakta lain
yang tidak ada di data. Kalau data tidak cukup, turunkan skor.

Tentukan:
- is_disaster: true hanya bila ini KEJADIAN bencana nyata yang sedang/baru terjadi di
  Indonesia. False untuk berita kegiatan (apel, simulasi, imbauan), prakiraan cuaca,
  kejadian luar negeri, atau berita lama.
- type: salah satu {$types}.
- severity 0-100: dampak ke warga dan kebutuhan bantuan, APA PUN jenis bencananya:
  0-20   tanpa dampak ke warga (mis. gempa kecil di laut, tidak ada kerusakan)
  20-40  dampak lokal kecil: 1-5 rumah rusak/terbakar, tanpa pengungsi
  40-60  puluhan rumah terdampak (terendam/rusak) atau pengungsi puluhan orang
  60-80  puluhan-ratusan rumah rusak/terbakar atau ratusan pengungsi yang butuh logistik
  80-100 ada korban jiwa, ribuan pengungsi, kerusakan luas, atau status tanggap darurat
  Pakai angka dari data bila ada. Level GDACS sudah memperhitungkan dampak: Orange ≈ 70-85,
  Red ≈ 85-100. Gempa BMKG M6+ yang dirasakan kuat (MMI V ke atas) ≈ 65-85 walau belum ada
  angka korban. Di luar itu, tanpa angka dampak skor maksimal 50.
- same_as: id kejadian di daftar "sudah tercatat" bila ini kejadian yang SAMA (lokasi &
  waktu cocok), selain itu null.
- title: judul campaign ≤ 70 karakter, pola "Tanggap Darurat <Bencana> <Lokasi>".
- Deskripsi campaign yang LENGKAP (total 150-300 kata), Bahasa Indonesia yang netral dan
  hangat, HANYA fakta dari data (termasuk "Isi artikel" bila ada), dipecah ke 4 kolom:
  - lead: 1-2 kalimat pembuka (apa, di mana, kapan).
  - kronologi: urutan kejadian: waktu, lokasi rinci (desa/kecamatan), penyebab bila
    disebut, dan bantuan yang sudah datang. TANPA angka korban/kerusakan.
  - dampak: SEMUA angka korban jiwa, luka, pengungsi, warga terdampak, rumah, fasilitas,
    persis seperti di data. Hanya tulis "Data dampak masih dihimpun dari laporan resmi."
    bila data sama sekali tidak menyebut angka dampak.
  - kebutuhan: barang/bantuan yang dibutuhkan atau sedang disalurkan menurut data, ditulis
    langsung (mis. "Warga membutuhkan ..."). Bila tidak ada tulis "Kebutuhan disesuaikan
    dengan laporan lapangan dari lembaga penyalur."
  Jangan menambah kata sifat yang tidak didukung data (mis. "luas", "parah"). Jangan
  menyebut kata "data" di kalimat. Jangan menulis sumber atau cara penyaluran dana; itu
  ditambahkan sistem.

Balas HANYA JSON valid:
{"is_disaster":true|false,"type":"...","location":"kabupaten/kota, provinsi atau null","severity":0,"reason":"1 kalimat alasan","impact":{"deaths":null,"displaced":null,"affected":null,"houses":null},"title":"...","lead":"...","kronologi":"...","dampak":"...","kebutuhan":"...","same_as":null}

impact: angka dampak dari data (bilangan bulat, null bila tidak disebut). deaths = korban
meninggal/hilang, displaced = pengungsi, affected = warga/jiwa terdampak, houses = rumah
rusak/terendam/terbakar. Kata kira-kira pakai batas bawahnya: "puluhan" = 10,
"ratusan" = 100, "ribuan" = 1000.
SYS;

        $known = collect($recent)->map(fn ($r) => "- id {$r['id']}: {$r['title']} ({$r['location']}, {$r['occurred_at']})")->implode("\n") ?: '- (belum ada)';
        $user = "Sumber: {$e['source']}\n"
              . "Judul: {$e['title']}\n"
              . 'Lokasi: ' . ($e['location'] ?? '-') . "\n"
              . 'Waktu: ' . (isset($e['occurred_at']) ? $e['occurred_at']->timezone('Asia/Jakarta')->format('Y-m-d H:i') . ' WIB' : '-') . "\n"
              . (isset($e['magnitude']) && $e['magnitude'] ? "Magnitudo: {$e['magnitude']}\n" : '')
              . (!empty($e['alert_level']) ? "Level GDACS: {$e['alert_level']}\n" : '')
              . 'Ringkasan: ' . mb_substr((string) ($e['summary'] ?? '-'), 0, 1500) . "\n\n"
              . "Kejadian yang sudah tercatat (14 hari):\n{$known}";

        try {
            $res = Http::withToken($key)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'           => config('services.openai.model', 'gpt-4.1-nano'),
                'messages'        => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
                'temperature'     => 0,
                'max_tokens'      => 1100,
                'response_format' => ['type' => 'json_object'],
            ]);
            $out = $res->ok() ? json_decode((string) $res->json('choices.0.message.content'), true) : null;
            if (!is_array($out)) {
                return $this->fallback($e, 'Jawaban AI tidak terbaca — dinilai dengan aturan dasar.');
            }
            return $this->normalize($out, $e, collect($recent)->pluck('id')->all());
        } catch (\Throwable $ex) {
            Log::warning('DisasterAI gagal: ' . $ex->getMessage());
            return $this->fallback($e, 'Kendala menghubungi AI — dinilai dengan aturan dasar.');
        }
    }

    private function normalize(array $o, array $e, array $knownIds): array
    {
        $type = array_key_exists($o['type'] ?? '', DisasterEvent::TYPES) ? $o['type'] : ($e['type'] ?? 'lainnya');
        $same = isset($o['same_as']) && in_array((int) $o['same_as'], $knownIds, true) ? (int) $o['same_as'] : null;
        $loc  = trim((string) ($o['location'] ?? '')) ?: ($e['location'] ?? null);
        return [
            'is_disaster' => (bool) ($o['is_disaster'] ?? false),
            'type'        => $type,
            'location'    => $loc ? mb_substr($loc, 0, 180) : null,
            // Skor AI bisa berbeda antar-panggilan; angka dampak yang diekstrak memberi batas
            // bawah yang stabil, jadi keputusan buka otomatis tidak berubah-ubah.
            'severity'    => max(max(0, min(100, (int) ($o['severity'] ?? 0))), ($o['is_disaster'] ?? false) ? $this->impactFloor((array) ($o['impact'] ?? [])) : 0),
            'reason'      => mb_substr(trim((string) ($o['reason'] ?? '')), 0, 400) ?: 'Tanpa alasan.',
            'title'       => mb_substr(trim((string) ($o['title'] ?? '')), 0, 110) ?: $this->defaultTitle($type, $loc),
            'description' => mb_substr($this->composeDescription($o), 0, 3500),
            'same_as'     => $same,
        ];
    }

    /** Skor minimum dari angka dampak (korban jiwa ≥ 85, ratusan pengungsi ≥ 72, dst.). */
    private function impactFloor(array $i): int
    {
        $n = fn (string $k) => is_numeric($i[$k] ?? null) ? (int) $i[$k] : 0;
        return match (true) {
            $n('deaths') >= 1, $n('displaced') >= 1000, $n('houses') >= 500 => 85,
            $n('displaced') >= 100, $n('houses') >= 50, $n('affected') >= 500 => 72,
            $n('displaced') >= 10, $n('houses') >= 10, $n('affected') >= 100 => 55,
            default => 0,
        };
    }

    /** Susun deskripsi berbagian dari kolom AI; judul bagian dikenali halaman campaign. */
    private function composeDescription(array $o): string
    {
        $part = fn (string $k) => trim(preg_replace('/\s+/', ' ', (string) ($o[$k] ?? '')));
        $out = $part('lead');
        foreach (['Kronologi' => 'kronologi', 'Dampak' => 'dampak', 'Kebutuhan mendesak' => 'kebutuhan'] as $label => $k) {
            if ($part($k) !== '') {
                $out .= "\n\n{$label}\n" . $part($k);
            }
        }
        return trim($out);
    }

    /** Tanpa AI: sumber resmi dinilai dari magnitudo / level; berita tidak dinilai. */
    private function fallback(array $e, string $why): array
    {
        $type = $e['type'] ?? 'lainnya';
        $sev = 0;
        if ($e['source'] === 'bmkg') {
            $m = (float) ($e['magnitude'] ?? 0);
            $sev = $m >= 7 ? 90 : ($m >= 6 ? 72 : ($m >= 5.5 ? 45 : 20));
        } elseif ($e['source'] === 'gdacs') {
            $sev = ['Red' => 90, 'Orange' => 72, 'Green' => 25][$e['alert_level'] ?? ''] ?? 30;
        } else {
            $sev = 50; // berita/manual tanpa AI → biarkan pengawas yang menilai
        }
        return [
            'is_disaster' => true,
            'type'        => $type,
            'location'    => $e['location'] ?? null,
            'severity'    => $sev,
            'reason'      => $why,
            'title'       => $this->defaultTitle($type, $e['location'] ?? null),
            'description' => trim((string) ($e['summary'] ?? '')),
            'same_as'     => null,
        ];
    }

    private function defaultTitle(string $type, ?string $loc): string
    {
        $label = DisasterEvent::TYPES[$type] ?? 'Bencana';
        return mb_substr('Tanggap Darurat ' . $label . ($loc ? ' ' . $loc : ''), 0, 110);
    }
}

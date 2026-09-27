<?php

namespace App\Services\Disaster;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengambil kejadian bencana nyata dari sumber publik (tanpa API key) dan
 * menyeragamkannya. Setiap sumber berdiri sendiri: kalau satu gagal, yang lain jalan.
 *
 * Bentuk kejadian: source, external_id, type, title, location, magnitude, alert_level,
 * occurred_at (Carbon), url, summary.
 */
class DisasterSources
{
    private const BMKG_LATEST = 'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json';
    private const BMKG_LIST   = 'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json'; // gempa M5+ terbaru
    private const GDACS       = 'https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH';
    private const NEWS_RSS    = 'https://news.google.com/rss/search';
    // Feed media yang menyertakan link artikel & foto langsung (Google News tidak).
    private const MEDIA_FEEDS = [
        'ANTARA'        => 'https://www.antaranews.com/rss/terkini.xml',
        'CNN Indonesia' => 'https://www.cnnindonesia.com/nasional/rss',
    ];
    private const DISASTER_WORDS = ['kebakaran', 'terbakar', 'banjir', 'longsor', 'gempa', 'erupsi', 'tsunami',
        'puting beliung', 'angin kencang', 'karhutla', 'kekeringan', 'rob ', 'abrasi', 'bencana'];

    private const GDACS_TYPES = ['EQ' => 'gempa', 'TS' => 'tsunami', 'FL' => 'banjir', 'VO' => 'erupsi', 'TC' => 'angin', 'DR' => 'kekeringan', 'WF' => 'kebakaran'];

    /** Semua sumber sekaligus. Feed media duluan: lebih lengkap (foto & link langsung). */
    public function all(): array
    {
        return array_merge($this->bmkg(), $this->gdacs(), $this->mediaFeeds(), $this->news());
    }

    public function bmkg(): array
    {
        $out = [];
        foreach ([self::BMKG_LATEST, self::BMKG_LIST] as $url) {
            $json = $this->getJson($url);
            $list = $json['Infogempa']['gempa'] ?? [];
            if (isset($list['DateTime'])) {
                $list = [$list]; // autogempa berisi satu objek, gempaterkini berisi daftar
            }
            foreach ($list as $g) {
                if (empty($g['DateTime'])) {
                    continue;
                }
                $mag = (float) ($g['Magnitude'] ?? 0);
                $wilayah = trim((string) ($g['Wilayah'] ?? ''));
                $out[$g['DateTime']] = [
                    'source'      => 'bmkg',
                    'external_id' => $g['DateTime'],
                    'type'        => 'gempa',
                    'title'       => "Gempa M{$mag} " . ($wilayah ?: 'Indonesia'),
                    'location'    => $wilayah ?: null,
                    'magnitude'   => $mag,
                    'alert_level' => null,
                    'occurred_at' => Carbon::parse($g['DateTime']),
                    'url'         => 'https://www.bmkg.go.id/gempabumi/gempabumi-terkini',
                    // Peta guncangan resmi (hanya ada di gempa terbaru / autogempa).
                    'image_url'   => !empty($g['Shakemap']) ? 'https://data.bmkg.go.id/DataMKG/TEWS/' . $g['Shakemap'] : null,
                    'image_credit'=> !empty($g['Shakemap']) ? 'Peta guncangan: BMKG' : null,
                    'summary'     => implode(' · ', array_filter([
                        'Magnitudo ' . $mag,
                        isset($g['Kedalaman']) ? 'kedalaman ' . $g['Kedalaman'] : null,
                        $wilayah ?: null,
                        $g['Potensi'] ?? null,
                        isset($g['Dirasakan']) ? 'Dirasakan: ' . $g['Dirasakan'] : null,
                    ])),
                ];
            }
        }
        return array_values($out);
    }

    public function gdacs(): array
    {
        $days = (int) config('disaster.lookback_days', 7);
        $json = $this->getJson(self::GDACS, [
            'country'  => 'Indonesia',
            'fromdate' => now()->subDays($days)->toDateString(),
            'todate'   => now()->toDateString(),
        ]);
        $out = [];
        foreach ($json['features'] ?? [] as $f) {
            $p = $f['properties'] ?? [];
            if (empty($p['eventtype']) || empty($p['eventid'])) {
                continue;
            }
            $sev = $p['severitydata']['severitytext'] ?? '';
            $out[] = [
                'source'      => 'gdacs',
                'external_id' => $p['eventtype'] . '-' . $p['eventid'] . '-' . ($p['episodeid'] ?? 0),
                'type'        => self::GDACS_TYPES[$p['eventtype']] ?? 'lainnya',
                'title'       => trim(preg_replace('/\s+/', ' ', (string) ($p['name'] ?? 'Bencana di Indonesia'))),
                'location'    => $p['country'] ?? 'Indonesia',
                'magnitude'   => $p['eventtype'] === 'EQ' ? (float) ($p['severitydata']['severity'] ?? 0) : null,
                'alert_level' => $p['alertlevel'] ?? null,
                'occurred_at' => isset($p['fromdate']) ? Carbon::parse($p['fromdate'], 'UTC') : now(),
                'url'         => $p['url']['report'] ?? 'https://www.gdacs.org',
                'image_url'   => null,
                'image_credit'=> null,
                'summary'     => trim('Level peringatan GDACS: ' . ($p['alertlevel'] ?? '-') . ($sev ? '. ' . $sev : '')),
            ];
        }
        return $out;
    }

    public function news(): array
    {
        try {
            $res = Http::timeout(15)->get(self::NEWS_RSS, [
                'q' => config('disaster.news_query') . ' when:1d', 'hl' => 'id', 'gl' => 'ID', 'ceid' => 'ID:id',
            ]);
            if (!$res->ok()) {
                return [];
            }
            $xml = @simplexml_load_string($res->body());
        } catch (\Throwable $e) {
            Log::warning('DisasterSources news: ' . $e->getMessage());
            return [];
        }
        if (!$xml || !isset($xml->channel->item)) {
            return [];
        }

        $skip = config('disaster.news_skip_words', []);
        $out = [];
        foreach ($xml->channel->item as $item) {
            $title = html_entity_decode(trim((string) $item->title), ENT_QUOTES);
            $lower = mb_strtolower($title);
            // Saring murah sebelum AI: berita kegiatan (apel, sosialisasi, ...) bukan kejadian.
            if ($title === '' || collect($skip)->contains(fn ($w) => str_contains($lower, $w))) {
                continue;
            }
            $link = (string) $item->link;
            $out[] = [
                'source'      => 'news',
                'external_id' => sha1($link ?: $title),
                'type'        => null, // ditentukan AI
                'title'       => mb_substr($title, 0, 250),
                'location'    => null,
                'magnitude'   => null,
                'alert_level' => null,
                'occurred_at' => ($d = (string) $item->pubDate) ? Carbon::parse($d) : now(),
                'url'         => $link,
                'image_url'   => null, // link Google News tidak bisa dibuka langsung ke artikel
                'image_credit'=> null,
                'summary'     => trim(strip_tags(html_entity_decode((string) $item->description, ENT_QUOTES))) ?: null,
            ];
            if (count($out) >= 20) {
                break;
            }
        }
        return $out;
    }

    /** Berita bencana dari feed media (ANTARA, CNN Indonesia) lengkap dengan fotonya. */
    public function mediaFeeds(): array
    {
        $skip = config('disaster.news_skip_words', []);
        $cutoff = now()->subDays(2);
        $out = [];
        foreach (self::MEDIA_FEEDS as $media => $feed) {
            try {
                $res = Http::timeout(15)->withHeaders(['User-Agent' => 'Mozilla/5.0 (E-Trace Radar Bencana)'])->get($feed);
                $xml = $res->ok() ? @simplexml_load_string($res->body()) : null;
            } catch (\Throwable $e) {
                Log::warning("DisasterSources {$media}: " . $e->getMessage());
                continue;
            }
            foreach ($xml->channel->item ?? [] as $item) {
                $title = html_entity_decode(trim((string) $item->title), ENT_QUOTES);
                $lower = ' ' . mb_strtolower($title) . ' ';
                // Feed ini berita umum: ambil hanya yang menyebut bencana, buang berita kegiatan.
                if (!collect(self::DISASTER_WORDS)->contains(fn ($w) => str_contains($lower, $w))
                    || collect($skip)->contains(fn ($w) => str_contains($lower, $w))) {
                    continue;
                }
                $when = ($d = (string) $item->pubDate) ? Carbon::parse($d) : now();
                if ($when->lt($cutoff)) {
                    continue;
                }
                $descHtml = html_entity_decode((string) $item->description, ENT_QUOTES);
                $image = null;
                if (isset($item->enclosure['url'])) {
                    $image = (string) $item->enclosure['url'];
                } elseif (($mrss = $item->children('http://search.yahoo.com/mrss/')->content) && isset($mrss->attributes()->url)) {
                    $image = (string) $mrss->attributes()->url;
                } elseif (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $descHtml, $m)) {
                    $image = $m[1];
                }
                $link = trim((string) $item->link);
                $out[] = [
                    'source'       => 'news',
                    'external_id'  => sha1($link ?: $title),
                    'type'         => null,
                    'title'        => mb_substr($title . ' - ' . $media, 0, 250),
                    'location'     => null,
                    'magnitude'    => null,
                    'alert_level'  => null,
                    'occurred_at'  => $when,
                    'url'          => $link,
                    'image_url'    => $image ?: null,
                    'image_credit' => $image ? 'Foto: ' . $media : null,
                    'summary'      => trim(strip_tags($descHtml)) ?: null,
                ];
            }
        }
        return $out;
    }

    private function getJson(string $url, array $query = []): array
    {
        try {
            $res = Http::timeout(15)->acceptJson()->get($url, $query);
            return $res->ok() ? ((array) $res->json()) : [];
        } catch (\Throwable $e) {
            Log::warning("DisasterSources {$url}: " . $e->getMessage());
            return [];
        }
    }
}

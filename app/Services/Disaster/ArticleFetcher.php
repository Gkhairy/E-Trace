<?php

namespace App\Services\Disaster;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Membaca artikel berita: foto utama (og:image), nama media, dan isi teksnya untuk
 * dinilai AI. URL bisa datang dari input pengawas, jadi hanya http(s) ke alamat publik
 * yang diizinkan (termasuk setiap redirect) agar tidak bisa dipakai menjangkau jaringan
 * internal server.
 *
 * @return array{image:?string, site:?string, text:?string}
 */
class ArticleFetcher
{
    private const MEDIA = [
        'antaranews.com' => 'ANTARA', 'cnnindonesia.com' => 'CNN Indonesia', 'detik.com' => 'detikcom',
        'kompas.com' => 'Kompas.com', 'tempo.co' => 'Tempo', 'okezone.com' => 'Okezone', 'liputan6.com' => 'Liputan6',
        'tribunnews.com' => 'Tribunnews', 'kumparan.com' => 'kumparan', 'bnpb.go.id' => 'BNPB', 'bmkg.go.id' => 'BMKG',
    ];

    public function fetch(string $url): array
    {
        $empty = ['image' => null, 'site' => null, 'text' => null];
        if (!$this->isPublicUrl($url) || str_contains(parse_url($url, PHP_URL_HOST) ?? '', 'news.google.com')) {
            return $empty; // link Google News tidak menunjuk langsung ke artikel
        }

        try {
            $res = Http::timeout(10)->withHeaders(['User-Agent' => 'Mozilla/5.0 (E-Trace Radar Bencana)'])
                ->withOptions(['allow_redirects' => [
                    'max' => 3,
                    'on_redirect' => function ($req, $resp, $uri) {
                        if (!$this->isPublicUrl((string) $uri)) {
                            throw new \RuntimeException('Redirect ke alamat tidak publik ditolak.');
                        }
                    },
                ]])->get($url);
            if (!$res->ok() || !str_contains((string) $res->header('Content-Type'), 'html')) {
                return $empty;
            }
            $html = mb_substr($res->body(), 0, 800000);
        } catch (\Throwable $e) {
            Log::info('ArticleFetcher ' . $url . ': ' . $e->getMessage());
            return $empty;
        }

        $meta = function (string $prop) use ($html): ?string {
            $p = preg_quote($prop, '/');
            if (preg_match('/<meta[^>]+(?:property|name)=["\']' . $p . '["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m)
                || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]*(?:property|name)=["\']' . $p . '["\']/i', $html, $m)) {
                return html_entity_decode(trim($m[1]), ENT_QUOTES);
            }
            return null;
        };

        // Isi artikel: gabungan paragraf <p> (cukup untuk kronologi & angka dampak).
        $paras = [];
        if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $mm)) {
            foreach ($mm[1] as $p) {
                $t = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($p), ENT_QUOTES)));
                if (mb_strlen($t) >= 60) {
                    $paras[] = $t;
                }
            }
        }
        $text = trim(($meta('og:description') ?? '') . "\n" . implode("\n", array_slice($paras, 0, 14)));

        $image = $meta('og:image') ?? $meta('twitter:image');
        return [
            'image' => $image && $this->isPublicUrl($image) ? $image : null,
            'site'  => $this->mediaName($url) ?? $meta('og:site_name'),
            'text'  => $text !== '' ? mb_substr($text, 0, 4000) : null,
        ];
    }

    /** Nama media dari domain (antaranews.com → ANTARA), atau null bila tak dikenal. */
    public function mediaName(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach (self::MEDIA as $domain => $name) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return $name;
            }
        }
        return null;
    }

    /** http(s) dan semua IP host-nya publik (bukan loopback/privat/reserved). */
    public function isPublicUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }
        $ips = filter_var($parts['host'], FILTER_VALIDATE_IP) ? [$parts['host']] : (gethostbynamel($parts['host']) ?: []);
        if (!$ips) {
            return false;
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }
        return true;
    }
}

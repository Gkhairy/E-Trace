<?php

namespace App\Support;

use Throwable;

/**
 * Nilai bantu untuk halaman error (resources/views/errors).
 *
 * SENGAJA tidak menyentuh session, DB, atau auth(): halaman 500 justru dirender
 * saat aplikasi sedang rusak (mis. database mati). Kalau halaman error ikut
 * gagal, Laravel jatuh kembali ke halaman bawaannya. Semua nilai di sini hanya
 * dari request mentah dan exception.
 */
class ErrorPage
{
    /** Halaman asal, HANYA bila masih di situs ini (cegah open redirect); selain itu beranda. */
    public static function backUrl(): string
    {
        $ref = (string) request()->headers->get('referer', '');
        $host = parse_url($ref, PHP_URL_HOST);
        return ($ref !== '' && $host === request()->getHost()) ? $ref : url('/');
    }

    /**
     * Tujuan tombol "Coba lagi". GET diulang apa adanya; POST tidak, karena memuat
     * ulang POST mengirim ulang formulir (bisa berarti bayar/kirim dua kali) —
     * pengguna diarahkan ke halaman formulirnya untuk mengirim secara sadar.
     */
    public static function retryUrl(): string
    {
        return request()->isMethod('GET') || request()->isMethod('HEAD')
            ? request()->fullUrl()
            : self::backUrl();
    }

    /** Detik tunggu dari header Retry-After (429/503), atau null. */
    public static function retryAfter(?Throwable $e): ?int
    {
        if (!$e || !method_exists($e, 'getHeaders')) {
            return null;
        }
        $v = $e->getHeaders()['Retry-After'] ?? null;
        return is_numeric($v) ? max(1, (int) $v) : null;
    }

    /**
     * Pesan dari abort(403, '...') bila itu kalimat untuk pengguna, bukan teks
     * bawaan framework. Hanya dipakai di 4xx — pesan 5xx tak pernah ditampilkan.
     */
    public static function message(?Throwable $e): ?string
    {
        $m = trim((string) ($e?->getMessage() ?? ''));
        $generic = ['', 'Forbidden', 'This action is unauthorized.', 'Not Found', 'Unauthorized.'];
        return in_array($m, $generic, true) ? null : $m;
    }

    /** Satu baris jejak permintaan: kode, metode, path, waktu. */
    public static function trace(int $code): array
    {
        return [
            'code'   => $code,
            'method' => request()->getMethod(),
            'path'   => '/' . ltrim(request()->path(), '/'),
            'time'   => now('Asia/Jakarta')->format('d M Y, H:i') . ' WIB',
        ];
    }
}

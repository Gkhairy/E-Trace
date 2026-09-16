<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile — verifikasi anti-bot (pengganti CAPTCHA) di form sensitif
 * (login/daftar). Aman-nonaktif: bila site key / secret belum diisi di .env, verifikasi
 * dilewati (return true) supaya dev/demo tetap jalan. Rahasia hanya di .env.
 */
class Turnstile
{
    /** Fitur aktif hanya bila kedua kunci terisi. */
    public static function enabled(): bool
    {
        return !empty(config('services.turnstile.site_key'))
            && !empty(config('services.turnstile.secret'));
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    /** Verifikasi token widget ke Cloudflare (server-side). True bila lolos / fitur mati. */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        if (!self::enabled()) {
            return true; // aman-nonaktif
        }
        if (empty($token)) {
            return false;
        }
        try {
            $res = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]);
            return $res->ok() && $res->json('success') === true;
        } catch (\Throwable $e) {
            Log::warning('Turnstile verify gagal: ' . $e->getMessage());
            return false; // gagal-menutup: kalau tak bisa verifikasi, tolak.
        }
    }
}

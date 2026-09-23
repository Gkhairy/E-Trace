<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar pada SETIAP response web.
 * - X-Frame-Options: cegah clickjacking (halaman tak boleh di-iframe lintas origin).
 * - X-Content-Type-Options: cegah MIME-sniffing.
 * - Referrer-Policy: batasi kebocoran URL saat navigasi lintas origin.
 * - Permissions-Policy: matikan API sensitif (geolocation/mic/camera) secara default.
 * - Strict-Transport-Security: paksa HTTPS (hanya saat request sudah secure).
 *
 * CSP SENGAJA TIDAK membatasi script (script-src/style-src) agar ethers.js, Tailwind
 * CDN, dan inline script tidak pecah. Yang dipasang hanya direktif yang tak menyentuh
 * script tapi menutup teknik serangan umum:
 *  - object-src 'none'  : tak ada plugin (<object>/<embed>).
 *  - base-uri 'self'    : injeksi <base> tak bisa membelokkan URL relatif ke domain lain.
 *  - form-action 'self' : form (login, PIN) tak bisa diarahkan kirim ke domain lain.
 *  - frame-ancestors    : padanan modern X-Frame-Options.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Content-Security-Policy',
            "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

        // Jangan umumkan versi PHP (memudahkan penyerang mencocokkan CVE).
        header_remove('X-Powered-By');

        // HSTS hanya bila koneksi sudah HTTPS (menghormati X-Forwarded-Proto via TrustProxies).
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

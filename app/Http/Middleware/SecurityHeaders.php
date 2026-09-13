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
 * CSP SENGAJA TIDAK dipasang ketat di sini agar ethers.js/Tailwind/inline script
 * tidak pecah. (Bisa ditambah CSP report-only terpisah bila diperlukan.)
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

        // HSTS hanya bila koneksi sudah HTTPS (menghormati X-Forwarded-Proto via TrustProxies).
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

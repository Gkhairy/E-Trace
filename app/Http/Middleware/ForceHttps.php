<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa HTTPS bila config('app.force_https') aktif (default: hanya production).
 * - Request http di-redirect 301 ke https.
 * - Semua response mendapat header HSTS (Strict-Transport-Security).
 * Lokal (http) tidak terpengaruh karena flag mati di luar production.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.force_https')) {
            return $next($request);
        }

        // Redirect http -> https (menghormati X-Forwarded-Proto dari proxy tepercaya).
        if (! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // HSTS: paksa browser memakai https 1 tahun ke depan (termasuk subdomain).
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );

        return $response;
    }
}

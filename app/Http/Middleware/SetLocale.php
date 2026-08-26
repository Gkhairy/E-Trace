<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Set bahasa aplikasi dari pilihan user (session), fallback ke config.
 * Hanya izinkan locale yang didukung.
 */
class SetLocale
{
    public const SUPPORTED = ['id', 'en'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', config('app.locale'));
        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }
        App::setLocale($locale);
        return $next($request);
    }
}

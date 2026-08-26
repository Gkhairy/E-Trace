<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** Ganti bahasa aktif lalu kembali ke halaman sebelumnya. */
    public function switch(Request $request, string $locale)
    {
        if (in_array($locale, SetLocale::SUPPORTED, true)) {
            $request->session()->put('locale', $locale);
        }
        // Kembali ke halaman asal (fallback beranda).
        return redirect(url()->previous() ?: '/');
    }
}

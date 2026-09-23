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
        // Kembali ke halaman asal, tapi HANYA bila masih di situs ini. url()->previous()
        // bersumber dari header Referer, jadi tanpa cek host ini /lang/en memantulkan
        // pengunjung ke domain mana pun yang menautkannya (open redirect).
        $prev = url()->previous();
        $sameHost = $prev && parse_url($prev, PHP_URL_HOST) === $request->getHost();
        return redirect($sameHost ? $prev : '/');
    }
}

@php
    // The page itself is React (resources/js/welcome). Blade only supplies the locale and
    // the translated copy: every key React uses is listed in strings.json.
    $welcomeKeys = json_decode(file_get_contents(resource_path('js/welcome/strings.json')), true) ?: [];
    $welcome = [
        'locale' => app()->getLocale(),
        'year' => (int) date('Y'),
        't' => collect($welcomeKeys)->mapWithKeys(fn ($k) => [$k => __($k)])->all(),
        'langUrls' => ['id' => route('lang.switch', 'id'), 'en' => route('lang.switch', 'en')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@include('partials.favicon')
<title>{{ __('E-Trace — Marketplace On-Chain yang Transparan Sepenuhnya') }}</title>
<meta name="description" content="{{ __('E-Trace: marketplace berbasis blockchain dengan escrow trustless, keranjang multi-penjual, dan transparansi on-chain penuh.') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="true">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>window.__ETRACE_WELCOME__ = @json($welcome);</script>
@viteReactRefresh
@vite('resources/js/welcome/main.jsx')
</head>
<body>
<div id="welcome-root"></div>
<noscript>
    <div style="max-width:640px;margin:120px auto;padding:0 24px;font-family:sans-serif;color:#334155;">
        <h1 style="color:#0f172a;">E-Trace</h1>
        <p>{{ __('E-Trace: marketplace berbasis blockchain dengan escrow trustless, keranjang multi-penjual, dan transparansi on-chain penuh.') }}</p>
        <p><a href="/products">{{ __('Lihat Katalog') }}</a> · <a href="/login">{{ __('Masuk Toko') }}</a></p>
    </div>
</noscript>
</body>
</html>

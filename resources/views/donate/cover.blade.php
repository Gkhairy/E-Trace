@php
    // Sampul campaign Radar Bencana: warna & ikon garis per jenis bencana.
    $themes = [
        'gempa'      => ['#7c2d12', '#c2410c', 'M22 12h-2.5a2 2 0 00-1.9 1.5l-2.4 8.3a.3.3 0 01-.5 0L9.2 2.2a.3.3 0 00-.5 0L6.4 10.5A2 2 0 014.5 12H2'],
        'tsunami'    => ['#0f3d5e', '#0e7490', 'M2 17c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2M3 12.5C4.5 7 9 4 14 4c3 0 5.5 1.4 7 3.5-3.6-1-7 .6-8 4.5'],
        'banjir'     => ['#1e3a8a', '#2563eb', 'M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1'],
        'longsor'    => ['#3f2d1d', '#78532f', 'M8 3l4 8 5-5 5 15H2L8 3zM4.5 16.5l3 1.5M9 19l2.5-1'],
        'kebakaran'  => ['#7f1d1d', '#ea580c', 'M8.5 14.5A2.5 2.5 0 0011 12c0-1.4-.5-2-1-3-1.1-2.1-.2-4.1 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 11-14 0c0-1.2.4-2.3 1-3a2.5 2.5 0 002.5 2.5z'],
        'erupsi'     => ['#27272a', '#b91c1c', 'M2 21h20l-6.5-11h-7L2 21zM8.5 10l1.5-3M15.5 10L14 7M12 6V2M9 4.5L7.5 3M15 4.5L16.5 3'],
        'angin'      => ['#1e293b', '#0369a1', 'M17.7 7.7A2.5 2.5 0 1119.5 12H2M9.6 4.6A2 2 0 1111 8H2M12.6 19.4A2 2 0 1014 16H2'],
        'kekeringan' => ['#713f12', '#ca8a04', 'M12 8a4 4 0 100 8 4 4 0 000-8zM12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M6.3 17.7l-1.4 1.4M19.1 4.9l-1.4 1.4'],
        'lainnya'    => ['#1e293b', '#475569', 'M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0zM12 9v4M12 17h.01'],
    ];
    [$from, $to, $icon] = $themes[$type] ?? $themes['lainnya'];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675" role="img" aria-label="{{ $label }}">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="{{ $from }}"/>
            <stop offset="1" stop-color="{{ $to }}"/>
        </linearGradient>
        <radialGradient id="glow" cx="0.78" cy="0.38" r="0.55">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.22"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
        <pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse">
            <path d="M48 0H0V48" fill="none" stroke="#ffffff" stroke-opacity="0.06" stroke-width="1.5"/>
        </pattern>
    </defs>
    <rect width="1200" height="675" fill="url(#bg)"/>
    <rect width="1200" height="675" fill="url(#grid)"/>
    <rect width="1200" height="675" fill="url(#glow)"/>

    {{-- Ikon besar di kanan, dengan cincin "radar" --}}
    <g transform="translate(930 262)" fill="none" stroke="#ffffff">
        <circle r="150" stroke-opacity="0.12" stroke-width="2"/>
        <circle r="215" stroke-opacity="0.07" stroke-width="2"/>
        <g transform="translate(-108 -108) scale(9)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="{{ $icon }}"/>
        </g>
    </g>

    <g font-family="'Hanken Grotesk', 'Segoe UI', Arial, sans-serif" fill="#ffffff">
        <text x="72" y="118" font-size="26" font-weight="700" letter-spacing="5" fill-opacity="0.75">TANGGAP DARURAT</text>
        <text x="72" y="210" font-size="{{ mb_strlen($label) > 16 ? 60 : 76 }}" font-weight="800" letter-spacing="-1.5">{{ $label }}</text>
        <g transform="translate(72 560)">
            <rect width="380" height="56" rx="28" fill="#ffffff" fill-opacity="0.14" stroke="#ffffff" stroke-opacity="0.3"/>
            <circle cx="30" cy="28" r="7" fill="#4ade80"/>
            <text x="52" y="37" font-size="23" font-weight="600">Radar Bencana AI · E-Trace</text>
        </g>
    </g>
</svg>

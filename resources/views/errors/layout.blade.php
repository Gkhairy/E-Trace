{{--
    Layout halaman error — MANDIRI, sengaja tidak memakai layouts.app.

    Halaman 500 dirender saat aplikasi sedang rusak (mis. database mati). layouts.app
    membaca keranjang & notifikasi dari DB, jadi kalau dipakai di sini halaman error
    ikut gagal dan Laravel jatuh kembali ke halaman bawaannya. Karena itu: tanpa DB,
    tanpa session, tanpa auth(), dan CSS ditulis langsung (tanpa Tailwind CDN).
--}}
@php
    $code  = (int) trim($__env->yieldContent('code', '500'));
    $trace = \App\Support\ErrorPage::trace($code);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — E-Trace</title>
    <link rel="icon" type="image/png" href="/favicon.png?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f7fb;
            --ink: #0f172a;
            --muted: #475569;
            --faint: #556274;
            --line: #e2e8f0;
            --line-strong: #cbd5e1;
            --brand: #2563eb;
            --brand-ink: #1d4ed8;
            --brand-soft: #eef4ff;
            --brand-edge: #d6e3fd;
            --ease: cubic-bezier(.16, 1, .3, 1);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-rows: auto 1fr;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Hanken Grotesk', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        .sr-only {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }

        /* ---------- Merek ---------- */
        .top { padding: 24px clamp(20px, 5vw, 48px); }
        .brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; border-radius: 10px; }
        .brand-tile {
            width: 32px; height: 32px; border-radius: 8px;
            display: grid; place-items: center;
            background: var(--brand); color: #fff;
            font-weight: 800; font-size: 14px;
        }
        .brand-word { font-size: 18px; font-weight: 700; letter-spacing: -0.02em; }
        .brand-word b { color: var(--brand); font-weight: 700; }

        /* ---------- Isi ---------- */
        .wrap {
            align-self: center;
            width: min(100% - 40px, 600px);
            margin: 0 auto;
            padding: 40px 0 72px;
        }
        h1 {
            margin: 0 0 14px;
            font-size: clamp(2rem, 1.35rem + 2.6vw, 2.875rem);
            line-height: 1.08;
            letter-spacing: -0.035em;
            font-weight: 800;
            text-wrap: balance;
        }
        .lead {
            margin: 0;
            max-width: 60ch;
            font-size: 1.0625rem;
            line-height: 1.6;
            color: var(--muted);
            text-wrap: pretty;
        }
        .detail { margin-top: 24px; }
        .detail:empty { display: none; }

        /* Catatan penting (bukan border-left berwarna — satu bidang lembut utuh). */
        .note {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 14px 16px;
            background: var(--brand-soft);
            border: 1px solid var(--brand-edge);
            border-radius: 12px;
            color: #1e3a8a;
            font-size: 15px; line-height: 1.55;
        }
        .note svg { flex: none; margin-top: 2px; }
        .note a { color: var(--brand-ink); font-weight: 600; text-underline-offset: 3px; }
        .note-plain {
            background: #fff; border-color: var(--line); color: var(--ink);
        }

        /* ---------- Tombol ---------- */
        .actions { margin-top: 32px; display: flex; flex-wrap: wrap; gap: 12px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            height: 46px; padding: 0 20px;
            border-radius: 12px; border: 1px solid transparent;
            font-family: inherit; font-size: 15px; font-weight: 600; line-height: 1;
            text-decoration: none; cursor: pointer;
            transition: background-color .2s var(--ease), border-color .2s var(--ease),
                        box-shadow .2s var(--ease), transform .2s var(--ease);
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary {
            background: var(--brand); color: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .08), 0 6px 14px -6px rgba(37, 99, 235, .55);
        }
        .btn-primary:hover { background: var(--brand-ink); }
        .btn-ghost { background: #fff; color: var(--ink); border-color: var(--line); }
        .btn-ghost:hover { border-color: var(--line-strong); background: #fbfcfe; }
        .btn[aria-disabled="true"] {
            background: #dbe3f0; color: #475569; box-shadow: none; pointer-events: none;
        }
        :where(a, button, input):focus-visible {
            outline: 3px solid rgba(37, 99, 235, .45);
            outline-offset: 2px;
        }

        /* ---------- Pencarian (404) ---------- */
        .search { display: flex; gap: 8px; }
        .search input {
            flex: 1; min-width: 0; height: 46px; padding: 0 16px;
            border: 1px solid var(--line); border-radius: 12px; background: #fff;
            font: inherit; font-size: 15px; color: var(--ink);
            transition: border-color .2s var(--ease), box-shadow .2s var(--ease);
        }
        .search input::placeholder { color: #64748b; }
        .search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 4px rgba(37, 99, 235, .15); }

        /* ---------- Jejak permintaan: satu entri ledger ----------
           Monospace di sini karena isinya memang data (kode, metode, path, waktu).
           Satu-satunya gerak di halaman: baris ini "tertulis" dari kiri, dari keadaan
           yang sudah terlihat bila animasi dimatikan. */
        .trace {
            margin-top: 48px; padding-top: 18px;
            border-top: 1px dashed var(--line-strong);
            display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px;
            font: 13px/1.5 ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace;
            color: var(--faint);
            animation: trace-in .9s var(--ease) .15s both;
        }
        .trace-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--brand); flex: none; }
        .trace-code { color: var(--brand); font-weight: 600; }
        .trace-chunk { white-space: nowrap; }
        /* Potongan permintaan boleh menyusut; path panjang dipotong dengan elipsis. */
        .trace-req { display: inline-flex; gap: .6ch; min-width: 0; max-width: 100%; }
        .trace-path {
            color: var(--ink); min-width: 0;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        @keyframes trace-in {
            from { clip-path: inset(0 100% 0 0); }
            to   { clip-path: inset(0 0 0 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .trace { animation: none; }
            .btn { transition: none; }
        }

        @media (max-width: 480px) {
            .wrap { padding-top: 24px; }
            .actions .btn { flex: 1 1 auto; }
        }
    </style>
</head>
<body>
    <header class="top">
        <a href="{{ url('/') }}" class="brand" aria-label="E-Trace — {{ __('errors.home') }}">
            <span class="brand-tile" aria-hidden="true">E</span>
            <span class="brand-word" aria-hidden="true">E-<b>Trace</b></span>
        </a>
    </header>

    <main class="wrap">
        <h1>@yield('title')</h1>
        <p class="lead">@yield('lead')</p>

        <div class="detail">@yield('detail')</div>

        <div class="actions">@yield('actions')</div>

        <p class="trace" aria-label="{{ __('errors.trace') }}">
            {{-- Tiap pemisah menempel pada potongan SESUDAHNYA, jadi saat baris patah
                 tak ada "·" yang menggantung di ujung baris. --}}
            <span class="trace-dot" aria-hidden="true"></span>
            <span class="trace-chunk">status <span class="trace-code">{{ $trace['code'] }}</span></span>
            <span class="trace-chunk trace-req"><span aria-hidden="true">·</span> {{ $trace['method'] }}
                <span class="trace-path" title="{{ $trace['path'] }}">{{ $trace['path'] }}</span></span>
            <span class="trace-chunk"><span aria-hidden="true">·</span> <time>{{ $trace['time'] }}</time></span>
        </p>
    </main>

    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@include('partials.favicon')
<title>E-Trace — {{ __('landing.footer.tagline') }}</title>
<meta name="description" content="{{ __('landing.hero.subtitle') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="true">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
<style>
  body { margin: 0; background: #f6f7fb; }
  ::selection { background: rgba(37,99,235,0.18); color: #0f172a; }
  a { color: #2563eb; text-decoration: none; }

  .reveal { opacity: 0; }
  @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; } }

  /* ---- Header states ---- */
  #site-header { transition: background .3s ease, border-color .3s ease, box-shadow .3s ease; }
  #site-header .hlink { color: #e2e8f0; transition: color .3s ease; }
  #site-header .hlogo { color: #fff; }
  #site-header.scrolled { background: rgba(246,247,251,0.88); backdrop-filter: blur(8px); border-bottom: 1px solid rgba(15,23,42,0.06); }
  #site-header.scrolled .hlink { color: #475569; }
  #site-header.scrolled .hlink:hover { color: #2563eb; }
  #site-header.scrolled .hlogo { color: #0f172a; }
  #site-header.scrolled .h-cta { background: #0f172a !important; color: #fff !important; }

  /* ---- Hero video ---- */
  #hero { background: #0a0e17 center/cover no-repeat; }
  #hero-video { transition: opacity .6s ease; }
  @media (prefers-reduced-motion: reduce) { #hero-video { display: none !important; } }

  /* ---- Buttons ---- */
  .btn-primary:hover { background: #1d4ed8 !important; }
  .btn-ghost:hover { border-color: rgba(255,255,255,0.6) !important; background: rgba(255,255,255,0.08) !important; }
  .btn-light:hover { background: #f1f5f9 !important; }
  .lnk-arrow:hover { gap: 12px; }

  /* ---- Sustainability ---- */
  .sustain-visual { animation: sfloat 7s ease-in-out infinite; }
  @keyframes sfloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
  @media (prefers-reduced-motion: reduce) { .sustain-visual { animation: none !important; } }

  /* ---- Mobile ---- */
  @media (max-width: 767px) {
    #hero-content h1 { font-size: clamp(2.1rem, 8vw, 3rem) !important; }
    #hero-content p  { font-size: 16px !important; }
    .sec { padding: 90px 22px !important; }
  }
</style>
</head>
<body>

<div id="page" style="position:relative;min-height:100vh;background:#f6f7fb;color:#334155;font-family:'Hanken Grotesk',sans-serif;overflow-x:hidden;">

  {{-- ===================== HEADER ===================== --}}
  <header id="site-header" style="position:fixed;top:0;left:0;right:0;z-index:50;">
    <div style="max-width:1200px;margin:0 auto;height:76px;display:flex;align-items:center;justify-content:space-between;padding:0 40px;">
      <a href="/" class="hlogo" style="display:flex;align-items:center;gap:9px;font-family:'Bricolage Grotesque',sans-serif;font-size:18px;">
        <span style="width:10px;height:10px;border-radius:50%;background:#2563eb;box-shadow:0 0 14px 3px rgba(37,99,235,0.6);display:inline-block;"></span>
        E-Trace
      </a>
      <div style="display:flex;align-items:center;gap:22px;">
        <a href="/products" class="hlink" style="font-size:14px;">{{ __('landing.nav.catalog') }}</a>
        <a href="#how" class="hlink" style="font-size:14px;">{{ __('landing.nav.how') }}</a>
        <a href="{{ route('lang.switch', app()->getLocale() === 'en' ? 'id' : 'en') }}" class="hlink" style="font-size:13px;font-weight:600;border:1px solid rgba(148,163,184,0.5);border-radius:8px;padding:4px 9px;">{{ app()->getLocale() === 'en' ? 'ID' : 'EN' }}</a>
        <a href="/login" class="hlink h-cta" style="font-size:14px;font-weight:600;padding:10px 20px;border-radius:999px;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.25);color:#fff;">{{ __('landing.nav.login') }}</a>
      </div>
    </div>
  </header>

  {{-- ===================== HERO (video sinematik) ===================== --}}
  <section id="hero" style="position:relative;height:100vh;height:100dvh;min-height:640px;overflow:hidden;" data-poster="/media/hero-poster.jpg">
    <video id="hero-video" autoplay muted loop playsinline poster="/media/hero-poster.jpg"
           style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
      <source src="/media/hero.mp4" type="video/mp4">
    </video>
    {{-- Overlay gelap agar teks terbaca --}}
    <div aria-hidden="true" style="position:absolute;inset:0;z-index:1;background:linear-gradient(90deg, rgba(6,10,20,0.86) 0%, rgba(6,10,20,0.6) 42%, rgba(6,10,20,0.28) 100%);"></div>
    <div aria-hidden="true" style="position:absolute;inset:0;z-index:1;background:linear-gradient(180deg, rgba(6,10,20,0.45), transparent 26%, transparent 55%, rgba(6,10,20,0.7));"></div>

    <div id="hero-content" style="position:relative;z-index:2;max-width:1200px;margin:0 auto;height:100%;padding:0 40px;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;">
      <div class="reveal" data-reveal="hero" style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);backdrop-filter:blur(6px);font-size:13px;color:#dbe4f5;margin-bottom:26px;">
        <span style="width:6px;height:6px;border-radius:50%;background:#2563eb;box-shadow:0 0 10px 1px rgba(37,99,235,0.9);display:inline-block;"></span>
        {{ __('landing.hero.badge') }}
      </div>
      <h1 class="reveal" data-reveal="hero" style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(2.7rem,6vw,4.8rem);line-height:1.03;letter-spacing:-0.03em;color:#fff;max-width:900px;margin:0;text-wrap:balance;text-shadow:0 2px 30px rgba(0,0,0,0.4);">
        {{ __('landing.hero.title') }} <span style="color:#60a5fa;">{{ __('landing.hero.title_hl') }}</span>
      </h1>
      <p class="reveal" data-reveal="hero" style="max-width:60ch;font-size:19px;line-height:1.6;color:#c7d2e5;margin-top:24px;text-shadow:0 1px 16px rgba(0,0,0,0.5);">
        {{ __('landing.hero.subtitle') }}
      </p>
      <div class="reveal" data-reveal="hero" style="display:flex;flex-wrap:wrap;gap:16px;margin-top:38px;">
        <a href="/login" class="btn-primary" style="padding:15px 32px;border-radius:999px;background:#2563eb;color:#fff;font-weight:600;font-size:15px;box-shadow:0 12px 30px rgba(37,99,235,0.4);">{{ __('landing.hero.cta') }}</a>
        <a href="#how" class="btn-ghost" style="padding:15px 30px;border-radius:999px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.35);color:#fff;font-weight:500;font-size:15px;backdrop-filter:blur(6px);cursor:pointer;">{{ __('landing.hero.cta2') }}</a>
      </div>
    </div>

    <div class="reveal" data-reveal="hero" style="position:absolute;bottom:30px;left:0;right:0;z-index:2;display:flex;justify-content:center;">
      <div style="display:flex;flex-direction:column;align-items:center;gap:8px;color:rgba(255,255,255,0.7);font-size:11px;letter-spacing:0.15em;">
        <span>{{ __('landing.hero.scroll') }}</span>
        <span style="width:1px;height:32px;background:linear-gradient(180deg,rgba(255,255,255,0.7),transparent);"></span>
      </div>
    </div>
  </section>

  {{-- ===================== VALUE PROP ===================== --}}
  <section class="sec" style="position:relative;padding:110px 40px;max-width:1000px;margin:0 auto;text-align:center;">
    <h2 class="reveal" data-reveal="fade" style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(1.7rem,3.6vw,2.7rem);line-height:1.22;letter-spacing:-0.02em;color:#0f172a;margin:0;text-wrap:balance;">
      {{ __('landing.value.title') }}
    </h2>
  </section>

  <div style="height:1px;background:linear-gradient(90deg,transparent,rgba(148,163,184,0.3),transparent);max-width:1200px;margin:0 auto;"></div>

  {{-- ===================== CARA KERJA ===================== --}}
  <section id="how" class="sec" style="position:relative;padding:120px 40px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="max-width:680px;margin-bottom:56px;">
      <p style="color:#2563eb;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">{{ __('landing.how.eyebrow') }}</p>
      <h2 style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(1.8rem,4vw,2.9rem);line-height:1.15;letter-spacing:-0.02em;color:#0f172a;margin:0 0 14px;">{{ __('landing.how.title') }}</h2>
      <p style="font-size:17px;line-height:1.6;color:#475569;margin:0;">{{ __('landing.how.subtitle') }}</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;">
      @foreach(__('landing.how.steps') as $i => $s)
        <div class="reveal" data-reveal="step" style="position:relative;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:16px;padding:26px 22px;">
          <div style="width:30px;height:30px;border-radius:9px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">{{ $i + 1 }}</div>
          <h3 style="font-family:'Bricolage Grotesque',sans-serif;font-size:16px;color:#0f172a;margin:0 0 7px;">{{ $s['t'] }}</h3>
          <p style="font-size:13.5px;line-height:1.55;color:#475569;margin:0;">{{ $s['d'] }}</p>
        </div>
      @endforeach
    </div>
    <div class="reveal" data-reveal="fade" style="margin-top:22px;display:flex;gap:12px;align-items:flex-start;background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:18px 22px;max-width:900px;">
      <span style="color:#2563eb;flex-shrink:0;font-size:16px;">ⓘ</span>
      <p style="font-size:14px;line-height:1.55;color:#1e3a8a;margin:0;">{{ __('landing.how.note') }}</p>
    </div>
  </section>

  {{-- ===================== FITUR LENGKAP ===================== --}}
  @php
    // Ikon + aksen per fitur (dipasangkan berdasarkan indeks dengan landing.features.items).
    $bl = ['#2563eb', '#eff6ff', '#bfdbfe']; // biru
    $rd = ['#e5121f', '#fef2f2', '#fecaca']; // merah
    $vt = ['#7c3aed', '#f5f3ff', '#ddd6fe']; // ungu
    $gr = ['#059669', '#ecfdf5', '#a7f3d0']; // hijau
    $featMeta = [
      ['<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>', $bl],
      ['<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>', $bl],
      ['<circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.5h4a1.5 1.5 0 0 1 0 3h-3a1.5 1.5 0 0 0 0 3h4"/>', $rd],
      ['<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="17" cy="14.5" r="1.2"/>', $bl],
      ['<circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>', $rd],
      ['<rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V4h6v3M9 13h.01M15 13h.01M12 2v2"/>', $vt],
      ['<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>', $gr],
      ['<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M8 12h1.5M11 9v6M14 11h1.5"/>', $bl],
      ['<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21.2l8.8-8.8a5.5 5.5 0 0 0 0-7.8z"/>', $rd],
      ['<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7v1M9 11h6"/>', $vt],
      ['<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', $bl],
      ['<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>', $gr],
      ['<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>', $vt],
    ];
    $featItems = __('landing.features.items');
  @endphp
  <section id="features" class="sec" style="position:relative;padding:120px 40px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="max-width:680px;margin-bottom:52px;">
      <p style="color:#2563eb;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">{{ __('landing.features.eyebrow') }}</p>
      <h2 style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(1.8rem,4vw,2.9rem);line-height:1.15;letter-spacing:-0.02em;color:#0f172a;margin:0 0 14px;">{{ __('landing.features.title') }}</h2>
      <p style="font-size:17px;line-height:1.6;color:#475569;margin:0;">{{ __('landing.features.subtitle') }}</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;">
      @foreach($featItems as $i => $f)
        @php $m = $featMeta[$i] ?? $featMeta[0]; $c = $m[1]; @endphp
        <div class="reveal" data-reveal="card" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:28px;">
          <div style="width:42px;height:42px;border-radius:11px;background:{{ $c[1] }};border:1px solid {{ $c[2] }};display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="{{ $c[0] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $m[0] !!}</svg>
          </div>
          <h3 style="font-family:'Bricolage Grotesque',sans-serif;font-size:17px;color:#0f172a;margin:0 0 8px;">{{ $f['t'] }}</h3>
          <p style="font-size:14px;line-height:1.55;color:#475569;margin:0;">{{ $f['d'] }}</p>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ===================== SUSTAINABILITY × BLOCKCHAIN ===================== --}}
  @php
    $sustainVisuals = [
      ['#059669', '#ecfdf5', '<path d="M4 12a8 8 0 0 1 8-8M20 12a8 8 0 0 1-8 8" /><path d="M12 4l3 3-3 3M12 20l-3-3 3-3"/>'], // loop
      ['#16a34a', '#f0fdf4', '<path d="M12 22V8M12 8l-4-3M12 8l4-3M12 14l-5-3M12 14l5-3"/><circle cx="12" cy="6" r="2"/>'], // pohon jaringan
      ['#0891b2', '#ecfeff', '<circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4M5 5l3 3M16 16l3 3M19 5l-3 3M8 16l-3 3"/>'], // node menyinari
    ];
    $sustainItems = __('landing.sustain.items');
  @endphp
  <section id="sustain" class="sec" style="position:relative;padding:130px 40px;background:linear-gradient(180deg,#f6f7fb, #f0fdf4 40%, #f6f7fb);overflow:hidden;">
    <div style="max-width:1200px;margin:0 auto;">
      <div class="reveal" data-reveal="fade" style="max-width:720px;margin-bottom:60px;">
        <p style="color:#059669;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">🌱 {{ __('landing.sustain.eyebrow') }}</p>
        <h2 style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(1.9rem,4.2vw,3rem);line-height:1.12;letter-spacing:-0.02em;color:#0f172a;margin:0 0 14px;text-wrap:balance;">{{ __('landing.sustain.title') }}</h2>
        <p style="font-size:17px;line-height:1.6;color:#475569;margin:0;">{{ __('landing.sustain.subtitle') }}</p>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;">
        @foreach($sustainItems as $i => $s)
          @php $v = $sustainVisuals[$i] ?? $sustainVisuals[0]; @endphp
          <div class="reveal" data-reveal="card" style="position:relative;background:#fff;border:1px solid #d1fae5;box-shadow:0 4px 20px rgba(5,150,105,0.06);border-radius:22px;padding:34px;overflow:hidden;">
            <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,{{ $v[1] }},transparent 70%);"></div>
            <div class="sustain-visual" style="position:relative;width:88px;height:88px;border-radius:24px;background:{{ $v[1] }};border:1px solid #a7f3d0;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="{{ $v[0] }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $v[2] !!}</svg>
              <span style="position:absolute;bottom:-9px;right:-9px;font-size:9px;font-weight:700;letter-spacing:0.05em;color:{{ $v[0] }};background:#fff;border:1px solid #a7f3d0;border-radius:999px;padding:2px 7px;">{{ __('landing.sustain.asset_note') }}</span>
            </div>
            <h3 style="font-family:'Bricolage Grotesque',sans-serif;font-size:20px;color:#0f172a;margin:0 0 10px;position:relative;">{{ $s['t'] }}</h3>
            <p style="font-size:14.5px;line-height:1.6;color:#475569;margin:0;position:relative;">{{ $s['d'] }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ===================== TRANSPARANSI ===================== --}}
  <section class="sec" style="position:relative;padding:120px 40px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="background:#0b1020;border-radius:26px;padding:64px 48px;text-align:center;position:relative;overflow:hidden;">
      <div aria-hidden="true" style="position:absolute;inset:0;background:radial-gradient(700px 380px at 50% 0%, rgba(37,99,235,0.28), transparent 60%);"></div>
      <div style="position:relative;">
        <h2 style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(1.8rem,4vw,2.8rem);line-height:1.15;letter-spacing:-0.02em;color:#fff;margin:0 auto 16px;max-width:720px;text-wrap:balance;">{{ __('landing.transparency.title') }}</h2>
        <p style="font-size:17px;line-height:1.6;color:#aab6cf;margin:0 auto 32px;max-width:620px;">{{ __('landing.transparency.desc') }}</p>
        <a href="/explorer" class="btn-light" style="display:inline-block;padding:15px 34px;border-radius:999px;background:#fff;color:#0b1020;font-weight:700;font-size:15px;box-shadow:0 12px 30px rgba(0,0,0,0.3);">{{ __('landing.transparency.cta') }} &rarr;</a>
      </div>
    </div>
  </section>

  {{-- ===================== UNTUK SIAPA ===================== --}}
  @php
    $personaMeta = [
      ['#2563eb', '#eff6ff', '#bfdbfe'],
      ['#e5121f', '#fef2f2', '#fecaca'],
      ['#7c3aed', '#f5f3ff', '#ddd6fe'],
    ];
    $personaItems = __('landing.personas.items');
  @endphp
  <section id="personas" class="sec" style="position:relative;padding:60px 40px 120px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="max-width:680px;margin-bottom:44px;">
      <h2 style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(1.8rem,4vw,2.7rem);line-height:1.15;letter-spacing:-0.02em;color:#0f172a;margin:0;">{{ __('landing.personas.title') }}</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
      @foreach($personaItems as $i => $p)
        @php $c = $personaMeta[$i] ?? $personaMeta[0]; @endphp
        <div class="reveal" data-reveal="card" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:20px;padding:32px;">
          <div style="width:44px;height:44px;border-radius:12px;background:{{ $c[1] }};border:1px solid {{ $c[2] }};display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $c[0] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
          </div>
          <h3 style="font-family:'Bricolage Grotesque',sans-serif;font-size:19px;color:#0f172a;margin:0 0 9px;">{{ $p['t'] }}</h3>
          <p style="font-size:14.5px;line-height:1.6;color:#475569;margin:0;">{{ $p['d'] }}</p>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ===================== CTA PENUTUP ===================== --}}
  <section class="sec" style="position:relative;padding:0 40px 120px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="background:linear-gradient(135deg,#2563eb,#1e40af);border-radius:26px;padding:70px 48px;text-align:center;position:relative;overflow:hidden;">
      <div aria-hidden="true" style="position:absolute;inset:0;background:radial-gradient(600px 300px at 80% 120%, rgba(229,18,31,0.3), transparent 60%);"></div>
      <div style="position:relative;">
        <h2 style="font-family:'Bricolage Grotesque',sans-serif;font-size:clamp(1.9rem,4.2vw,3rem);line-height:1.12;letter-spacing:-0.02em;color:#fff;margin:0 auto 16px;max-width:720px;text-wrap:balance;">{{ __('landing.cta.title') }}</h2>
        <p style="font-size:18px;line-height:1.6;color:#dbe4f5;margin:0 auto 34px;max-width:560px;">{{ __('landing.cta.desc') }}</p>
        <a href="/login" class="btn-light" style="display:inline-block;padding:16px 40px;border-radius:999px;background:#fff;color:#1e40af;font-weight:700;font-size:16px;box-shadow:0 14px 34px rgba(0,0,0,0.25);">{{ __('landing.cta.button') }}</a>
        <p style="font-size:12.5px;line-height:1.55;color:rgba(219,228,245,0.75);margin:28px auto 0;max-width:640px;">{{ __('landing.cta.disclaimer') }}</p>
      </div>
    </div>
  </section>

  {{-- ===================== FOOTER ===================== --}}
  <footer style="border-top:1px solid rgba(15,23,42,0.06);padding:40px;">
    <div style="max-width:1200px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
      <div style="display:flex;align-items:center;gap:9px;font-family:'Bricolage Grotesque',sans-serif;font-size:16px;color:#0f172a;">
        <span style="width:9px;height:9px;border-radius:50%;background:#2563eb;display:inline-block;"></span>
        E-Trace
      </div>
      <p style="font-size:13px;color:#64748b;margin:0;">{{ __('landing.footer.tagline') }}</p>
      <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#64748b;">
        <span style="width:7px;height:7px;border-radius:50%;background:#059669;display:inline-block;"></span>
        BNB Smart Chain Testnet
      </div>
    </div>
  </footer>

</div>

<script>
(function () {
  // Header: transparan di atas hero, jadi terang saat scroll.
  var header = document.getElementById('site-header');
  var onScroll = function () {
    if (window.scrollY > 60) header.classList.add('scrolled');
    else header.classList.remove('scrolled');
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  // Video hero: fade-in saat siap; sembunyikan bila gagal (poster tetap tampil via bg).
  var v = document.getElementById('hero-video');
  var hero = document.getElementById('hero');
  if (v && hero) {
    hero.style.backgroundImage = "url('" + hero.getAttribute('data-poster') + "')";
    v.addEventListener('error', function () { v.style.display = 'none'; });
  }

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Fallback: GSAP gagal → tampilkan semua konten apa adanya.
  if (typeof window.gsap === 'undefined' || typeof window.ScrollTrigger === 'undefined') {
    document.querySelectorAll('.reveal').forEach(function (el) { el.style.opacity = 1; el.style.transform = 'none'; });
    return;
  }

  var gsap = window.gsap, ScrollTrigger = window.ScrollTrigger, Lenis = window.Lenis;
  gsap.registerPlugin(ScrollTrigger);

  var lenis = null;
  if (!reduceMotion && Lenis) {
    lenis = new Lenis({ lerp: 0.1, smoothWheel: true });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
    gsap.ticker.lagSmoothing(0);
  }
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var target = document.querySelector(a.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      if (lenis) lenis.scrollTo(target, { duration: 1.1 });
      else target.scrollIntoView({ behavior: 'smooth' });
    });
  });

  document.querySelectorAll('[data-reveal="hero"]').forEach(function (el, i) {
    gsap.fromTo(el, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 1, delay: 0.15 + i * 0.1, ease: 'power3.out' });
  });
  document.querySelectorAll('[data-reveal="fade"]').forEach(function (el) {
    gsap.fromTo(el, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 85%' } });
  });
  document.querySelectorAll('[data-reveal="step"]').forEach(function (el, i) {
    gsap.fromTo(el, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.6, delay: (i % 5) * 0.08, ease: 'power3.out', scrollTrigger: { trigger: el.closest('section'), start: 'top 75%' } });
  });
  document.querySelectorAll('[data-reveal="card"]').forEach(function (el, i) {
    gsap.fromTo(el, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.6, delay: (i % 4) * 0.08, ease: 'power3.out', scrollTrigger: { trigger: el.closest('section'), start: 'top 80%' } });
  });
})();
</script>

</body>
</html>

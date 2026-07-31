<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MyCryptoShop — Marketplace On-Chain yang Transparan Sepenuhnya</title>
<meta name="description" content="MyCryptoShop: marketplace berbasis blockchain dengan escrow trustless, keranjang multi-penjual, dan transparansi on-chain penuh.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="true">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/three@0.128.0/build/three.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
<style>
  body { margin: 0; background: #f6f7fb; }
  ::selection { background: rgba(8,145,178,0.18); color: #0f172a; }
  a { color: #2563eb; text-decoration: none; }
  a:hover { color: #1d4ed8; }

  /* Reveal awal (di-animate GSAP). Kalau reduced-motion / GSAP gagal -> tampil. */
  .reveal { opacity: 0; }
  @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; } }

  /* Hover tombol (menimpa inline style) */
  .lnk-cta:hover  { background: #1d4ed8 !important; color: #fff !important; }
  .btn-primary:hover { background: #1d4ed8 !important; }
  .btn-ghost:hover   { border-color: rgba(37,99,235,0.4) !important; }
</style>
</head>
<body>

<div id="page" style="position:relative;min-height:100vh;background:#f6f7fb;color:#334155;font-family:Inter,sans-serif;overflow-x:hidden;">

  <header style="position:fixed;top:0;left:0;right:0;z-index:50;background:linear-gradient(180deg, rgba(246,247,251,0.9), rgba(246,247,251,0.75));backdrop-filter:blur(6px);border-bottom:1px solid rgba(15,23,42,0.06);">
    <div style="max-width:1200px;margin:0 auto;height:80px;display:flex;align-items:center;justify-content:space-between;padding:0 40px;">
      <a href="/" style="display:flex;align-items:center;gap:9px;font-family:'Space Grotesk',sans-serif;font-size:18px;color:#0f172a;">
        <span style="width:10px;height:10px;border-radius:50%;background:#2563eb;box-shadow:0 0 14px 3px rgba(37,99,235,0.6);display:inline-block;"></span>
        MyCryptoShop
      </a>
      <div style="display:flex;align-items:center;gap:24px;">
        <a href="/products" style="font-size:14px;color:#475569;">Lihat Katalog</a>
        <a href="/login" class="lnk-cta" style="font-size:14px;font-weight:500;padding:11px 22px;border-radius:999px;background:#0f172a;color:#fff;">Masuk Toko</a>
      </div>
    </div>
  </header>

  <section id="hero" style="position:relative;height:100vh;min-height:640px;overflow:hidden;">
    <div style="position:absolute;inset:0;z-index:-1;background:radial-gradient(circle at 50% 45%, rgba(37,99,235,0.14), transparent 70%);"></div>
    <div id="hero-canvas" style="position:absolute;top:0;right:0;bottom:0;width:58%;z-index:0;"></div>
    <div id="hero-fallback" style="position:absolute;inset:0;display:none;background:radial-gradient(circle at 50% 40%, rgba(37,99,235,0.14), transparent 60%), radial-gradient(circle at 70% 70%, rgba(168,85,247,0.14), transparent 55%);"></div>

    <div style="position:relative;z-index:10;max-width:1200px;margin:0 auto;height:100%;padding:0 40px;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;">
      <div class="reveal" data-reveal="hero" style="display:inline-flex;align-items:center;gap:8px;padding:7px 16px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);font-size:13px;color:#2563eb;margin-bottom:28px;">
        <span style="width:6px;height:6px;border-radius:50%;background:#2563eb;display:inline-block;"></span>
        Escrow trustless &middot; Ethereum &middot; Token TLKM
      </div>
      <h1 class="reveal" data-reveal="hero" style="font-family:'Space Grotesk',sans-serif;font-size:clamp(2.6rem,6vw,4.6rem);line-height:1.04;color:#0f172a;max-width:760px;margin:0;">
        Marketplace yang <span style="background:linear-gradient(90deg,#2563eb,#6366f1,#9333ea);-webkit-background-clip:text;background-clip:text;color:transparent;">transparan sepenuhnya.</span>
      </h1>
      <p class="reveal" data-reveal="hero" style="max-width:560px;font-size:19px;line-height:1.65;color:#475569;margin-top:26px;">
        Login dengan wallet, tanpa password. Setiap pembayaran ditahan smart contract hingga barang diterima — dan setiap transaksi bisa diverifikasi siapa saja di block explorer.
      </p>
      <div class="reveal" data-reveal="hero" style="display:flex;flex-wrap:wrap;gap:16px;margin-top:38px;">
        <a href="/login" class="btn-primary" style="padding:15px 30px;border-radius:999px;background:#2563eb;color:#fff;font-weight:600;font-size:15px;">Masuk Toko</a>
        <a href="#solusi" class="btn-ghost" style="padding:15px 30px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#1e293b;font-weight:500;font-size:15px;box-shadow:0 1px 2px rgba(15,23,42,0.04);cursor:pointer;">Pelajari</a>
      </div>
    </div>

    <div class="reveal" data-reveal="hero" style="position:absolute;bottom:32px;left:0;right:0;display:flex;justify-content:center;">
      <div style="display:flex;flex-direction:column;align-items:center;gap:8px;color:#64748b;font-size:11px;letter-spacing:0.1em;">
        <span>SCROLL</span>
        <span style="width:1px;height:32px;background:linear-gradient(180deg,#64748b,transparent);"></span>
      </div>
    </div>
  </section>

  <section id="solusi" style="position:relative;padding:130px 40px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="max-width:660px;margin-bottom:60px;">
      <p style="color:#2563eb;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">Masalah &amp; Solusi</p>
      <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.8rem,4vw,2.9rem);color:#0f172a;line-height:1.2;margin:0;">Marketplace biasa menahan dana Anda di tempat yang gelap.</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;">
      <div class="reveal" data-reveal="left" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:44px;">
        <p style="color:#64748b;font-size:13px;margin:0 0 8px;">Cara konvensional</p>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:22px;color:#475569;margin:0 0 22px;">Perantara memegang dana</h3>
        <div style="display:flex;flex-direction:column;gap:16px;color:#475569;font-size:15px;line-height:1.5;">
          <div style="display:flex;gap:12px;"><span style="color:#475569;">—</span>Dana pembeli dipegang platform, prosesnya tidak terlihat.</div>
          <div style="display:flex;gap:12px;"><span style="color:#475569;">—</span>Sengketa diselesaikan lewat tiket &amp; kebijakan sepihak.</div>
          <div style="display:flex;gap:12px;"><span style="color:#475569;">—</span>Tidak ada cara publik memverifikasi transaksi terjadi.</div>
        </div>
      </div>
      <div class="reveal" data-reveal="right" style="position:relative;background:#fff;border:1px solid #bfdbfe;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:44px;">
        <p style="color:#2563eb;font-size:13px;margin:0 0 8px;position:relative;">Pendekatan MyCryptoShop</p>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:22px;color:#0f172a;margin:0 0 22px;position:relative;">Smart contract memegang dana</h3>
        <div style="display:flex;flex-direction:column;gap:16px;color:#334155;font-size:15px;line-height:1.5;position:relative;">
          <div style="display:flex;gap:12px;"><span style="color:#2563eb;">✓</span>Dana ditahan escrow on-chain, dilepas hanya saat pembeli konfirmasi.</div>
          <div style="display:flex;gap:12px;"><span style="color:#2563eb;">✓</span>Tidak diterima barang → refund, bukan keluhan berlarut.</div>
          <div style="display:flex;gap:12px;"><span style="color:#2563eb;">✓</span>Setiap transaksi tercatat &amp; terbuka di block explorer.</div>
        </div>
      </div>
    </div>
  </section>

  <div style="height:1px;background:linear-gradient(90deg,transparent,rgba(148,163,184,0.3),transparent);max-width:1200px;margin:0 auto;"></div>

  <section style="position:relative;padding:130px 40px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="max-width:660px;margin-bottom:70px;">
      <p style="color:#2563eb;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">Cara Kerja</p>
      <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.8rem,4vw,2.9rem);color:#0f172a;line-height:1.2;margin:0;">Tiga langkah, dana selalu di bawah kendali Anda.</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:40px;">
      <div class="reveal" data-reveal="step" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:32px;">
        <span style="font-family:'Space Grotesk',sans-serif;font-size:46px;color:transparent;-webkit-text-stroke:1.5px rgba(15,23,42,0.4);display:block;margin-bottom:20px;">01</span>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:20px;color:#0f172a;margin:0 0 10px;">Connect Wallet</h3>
        <p style="color:#475569;line-height:1.6;margin:0;">Login dengan MetaMask — tanpa email, tanpa password. Identitas Anda adalah wallet Anda.</p>
      </div>
      <div class="reveal" data-reveal="step" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:32px;">
        <span style="font-family:'Space Grotesk',sans-serif;font-size:46px;color:transparent;-webkit-text-stroke:1.5px rgba(15,23,42,0.4);display:block;margin-bottom:20px;">02</span>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:20px;color:#0f172a;margin:0 0 10px;">Bayar via Escrow</h3>
        <p style="color:#475569;line-height:1.6;margin:0;">Bayar dengan token TLKM. Dana masuk ke smart contract escrow, terpisah per penjual.</p>
      </div>
      <div class="reveal" data-reveal="step" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:32px;">
        <span style="font-family:'Space Grotesk',sans-serif;font-size:46px;color:transparent;-webkit-text-stroke:1.5px rgba(15,23,42,0.4);display:block;margin-bottom:20px;">03</span>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:20px;color:#0f172a;margin:0 0 10px;">Konfirmasi &amp; Dana Lepas</h3>
        <p style="color:#475569;line-height:1.6;margin:0;">Barang diterima, Anda konfirmasi — escrow melepas dana ke penjual. Bermasalah? Ajukan refund.</p>
      </div>
    </div>
  </section>

  <div style="height:1px;background:linear-gradient(90deg,transparent,rgba(148,163,184,0.3),transparent);max-width:1200px;margin:0 auto;"></div>

  <section style="position:relative;padding:130px 40px;max-width:1200px;margin:0 auto;">
    <div class="reveal" data-reveal="fade" style="max-width:660px;margin-bottom:52px;">
      <p style="color:#2563eb;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">Fitur Unggulan</p>
      <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.8rem,4vw,2.9rem);color:#0f172a;line-height:1.2;margin:0;">Dibangun untuk kepercayaan yang bisa dibuktikan.</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
      <div class="reveal" data-reveal="card" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:32px;">
        <div style="width:40px;height:40px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
        </div>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:17px;color:#0f172a;margin:0 0 8px;">Escrow Trustless</h3>
        <p style="color:#475569;font-size:14px;line-height:1.6;margin:0;">Dana ditahan smart contract, dilepas hanya saat pembeli konfirmasi barang diterima.</p>
      </div>
      <div class="reveal" data-reveal="card" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:32px;">
        <div style="width:40px;height:40px;border-radius:10px;background:#f5f3ff;border:1px solid #ddd6fe;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        </div>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:17px;color:#0f172a;margin:0 0 8px;">Multi-Penjual</h3>
        <p style="color:#475569;font-size:14px;line-height:1.6;margin:0;">Satu pembayaran, banyak penjual — tiap penjual punya escrow terpisah yang independen.</p>
      </div>
      <div class="reveal" data-reveal="card" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:32px;">
        <div style="width:40px;height:40px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
        </div>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:17px;color:#0f172a;margin:0 0 8px;">Token TLKM</h3>
        <p style="color:#475569;font-size:14px;line-height:1.6;margin:0;">Token ERC-20 milik platform, digunakan untuk seluruh transaksi di jaringan Ethereum.</p>
      </div>
      <div class="reveal" data-reveal="card" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:32px;">
        <div style="width:40px;height:40px;border-radius:10px;background:#f5f3ff;border:1px solid #ddd6fe;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"></path><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"></path></svg>
        </div>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:17px;color:#0f172a;margin:0 0 8px;">Transparansi On-Chain</h3>
        <p style="color:#475569;font-size:14px;line-height:1.6;margin:0;">Setiap transaksi tercatat permanen dan bisa diverifikasi siapa saja di block explorer.</p>
      </div>
    </div>
  </section>

  <section style="position:relative;padding:130px 40px;max-width:1200px;margin:0 auto;overflow:hidden;">
    <div style="position:relative;display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:56px;align-items:center;">
      <div class="reveal" data-reveal="left">
        <p style="color:#2563eb;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 12px;">Transparansi Penuh</p>
        <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.8rem,4vw,2.9rem);color:#0f172a;line-height:1.2;margin:0 0 20px;">Tak perlu percaya kami. Verifikasi sendiri.</h2>
        <p style="color:#475569;font-size:17px;line-height:1.65;max-width:460px;margin:0;">Tiap escrow, pembayaran, dan pelepasan dana adalah entri publik di blockchain Ethereum — bisa dibuka lewat block explorer mana pun. Cocok untuk lembaga dan mitra yang butuh jejak akuntabilitas yang tidak bisa diubah sepihak.</p>
      </div>
      <div class="reveal" data-reveal="right" style="background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04);border-radius:18px;padding:26px;">
        <p style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 16px;">Contoh entri escrow on-chain</p>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:13px 16px;">
            <div><p style="font-size:14px;color:#1e293b;font-family:monospace;margin:0;">0x8f...2a91</p><p style="font-size:12px;color:#64748b;margin:4px 0 0;">Escrow dibuat &middot; Penjual A</p></div>
            <span style="font-size:11px;padding:5px 10px;border-radius:999px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">Ditahan</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:13px 16px;">
            <div><p style="font-size:14px;color:#1e293b;font-family:monospace;margin:0;">0x3c...9e0d</p><p style="font-size:12px;color:#64748b;margin:4px 0 0;">Konfirmasi diterima &middot; Penjual B</p></div>
            <span style="font-size:11px;padding:5px 10px;border-radius:999px;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;">Dilepas</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:13px 16px;">
            <div><p style="font-size:14px;color:#1e293b;font-family:monospace;margin:0;">0x1b...7f44</p><p style="font-size:12px;color:#64748b;margin:4px 0 0;">Refund diproses &middot; Penjual C</p></div>
            <span style="font-size:11px;padding:5px 10px;border-radius:999px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;">Refund</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section style="position:relative;padding:150px 40px;max-width:1200px;margin:0 auto;text-align:center;overflow:hidden;">
    <div class="reveal" data-reveal="fade" style="position:relative;">
      <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3.6rem);color:#0f172a;line-height:1.15;max-width:760px;margin:0 auto;">Belanja dengan transparansi yang bisa Anda buktikan sendiri.</h2>
      <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:16px;margin-top:40px;">
        <a href="/login" class="btn-primary" style="padding:16px 34px;border-radius:999px;background:#2563eb;color:#fff;font-weight:600;font-size:15px;">Masuk Toko</a>
        <a href="/products" class="btn-ghost" style="padding:16px 34px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#1e293b;font-weight:500;font-size:15px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">Lihat Katalog</a>
      </div>
    </div>
  </section>

  <footer style="border-top:1px solid rgba(15,23,42,0.08);padding:36px 40px;">
    <div style="max-width:1200px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;font-size:13px;color:#64748b;">
      <p style="margin:0;">© {{ date('Y') }} MyCryptoShop. Marketplace berbasis blockchain.</p>
      <div style="display:flex;gap:24px;">
        <a href="/products" style="color:#475569;">Katalog</a>
        <a href="/login" style="color:#475569;">Masuk</a>
      </div>
    </div>
  </footer>

</div>

<script>
(function () {
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isMobile = window.innerWidth < 768;

  // Fallback aman: kalau GSAP gagal load, tampilkan semua konten & pakai fallback hero.
  if (typeof window.gsap === 'undefined' || typeof window.ScrollTrigger === 'undefined') {
    document.querySelectorAll('.reveal').forEach(function (el) { el.style.opacity = 1; el.style.transform = 'none'; });
    var fb0 = document.getElementById('hero-fallback');
    if (fb0) fb0.style.display = 'block';
    return;
  }

  var gsap = window.gsap, ScrollTrigger = window.ScrollTrigger, THREE = window.THREE, Lenis = window.Lenis;
  gsap.registerPlugin(ScrollTrigger);

  // ---- Lenis smooth scroll ----
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

  // ---- Reveal (transform + opacity) ----
  document.querySelectorAll('[data-reveal="hero"]').forEach(function (el, i) {
    gsap.fromTo(el, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 1, delay: 0.15 + i * 0.1, ease: 'power3.out' });
  });
  document.querySelectorAll('[data-reveal="fade"]').forEach(function (el) {
    gsap.fromTo(el, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 82%' } });
  });
  document.querySelectorAll('[data-reveal="left"]').forEach(function (el) {
    gsap.fromTo(el, { opacity: 0, x: -40 }, { opacity: 1, x: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 82%' } });
  });
  document.querySelectorAll('[data-reveal="right"]').forEach(function (el) {
    gsap.fromTo(el, { opacity: 0, x: 40 }, { opacity: 1, x: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 82%' } });
  });
  document.querySelectorAll('[data-reveal="step"]').forEach(function (el, i) {
    gsap.fromTo(el, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.7, delay: (i % 3) * 0.15, ease: 'power3.out', scrollTrigger: { trigger: el.closest('section'), start: 'top 70%' } });
  });
  document.querySelectorAll('[data-reveal="card"]').forEach(function (el, i) {
    gsap.fromTo(el, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.7, delay: (i % 4) * 0.1, ease: 'power3.out', scrollTrigger: { trigger: el.closest('section'), start: 'top 75%' } });
  });

  // ---- 3D hero (Three.js): kristal wireframe + jaringan node, di kanan 58% ----
  var enable3D = !reduceMotion && !isMobile && typeof THREE !== 'undefined';
  var heroSection = document.getElementById('hero');
  var wrap = document.getElementById('hero-canvas');
  var fallback = document.getElementById('hero-fallback');
  if (!enable3D) { if (fallback) fallback.style.display = 'block'; return; }

  var cyanHex = 0x2563eb, violetHex = 0x7c3aed;
  var width = wrap.clientWidth, height = wrap.clientHeight;
  var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(width, height);
  wrap.appendChild(renderer.domElement);

  var scene = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(50, width / height, 0.1, 100);
  camera.position.set(0, 0, 7);

  var group = new THREE.Group();
  scene.add(group);

  var icoGeo = new THREE.IcosahedronGeometry(1.15, 1);
  var edges = new THREE.EdgesGeometry(icoGeo);
  var crystal = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: cyanHex, transparent: true, opacity: 0.85 }));
  group.add(crystal);
  var crystalFill = new THREE.Mesh(icoGeo, new THREE.MeshBasicMaterial({ color: 0x1e293b, transparent: true, opacity: 0.18 }));
  group.add(crystalFill);

  var NODE_COUNT = isMobile ? 40 : 90;
  var RADIUS = 2.5;
  var positions = [];
  for (var i = 0; i < NODE_COUNT; i++) {
    var v = new THREE.Vector3((Math.random() - 0.5) * 2, (Math.random() - 0.5) * 2, (Math.random() - 0.5) * 2)
      .normalize().multiplyScalar(RADIUS * (0.55 + Math.random() * 0.45));
    positions.push(v);
  }
  var pointsGeo = new THREE.BufferGeometry();
  var posArr = new Float32Array(NODE_COUNT * 3);
  positions.forEach(function (v, i) { posArr[i * 3] = v.x; posArr[i * 3 + 1] = v.y; posArr[i * 3 + 2] = v.z; });
  pointsGeo.setAttribute('position', new THREE.BufferAttribute(posArr, 3));

  var dotCanvas = document.createElement('canvas');
  dotCanvas.width = dotCanvas.height = 64;
  var dctx = dotCanvas.getContext('2d');
  var grad = dctx.createRadialGradient(32, 32, 0, 32, 32, 32);
  grad.addColorStop(0, 'rgba(255,255,255,1)');
  grad.addColorStop(1, 'rgba(255,255,255,0)');
  dctx.fillStyle = grad; dctx.fillRect(0, 0, 64, 64);
  var dotTexture = new THREE.CanvasTexture(dotCanvas);

  var pointCloud = new THREE.Points(pointsGeo, new THREE.PointsMaterial({
    size: 0.09, color: cyanHex, map: dotTexture, transparent: true, opacity: 0.9, blending: THREE.AdditiveBlending, depthWrite: false
  }));
  group.add(pointCloud);

  var linePositions = [];
  var THRESH = 1.2;
  for (var a = 0; a < positions.length; a++) {
    for (var b = a + 1; b < positions.length; b++) {
      if (positions[a].distanceTo(positions[b]) < THRESH) {
        linePositions.push(positions[a].x, positions[a].y, positions[a].z, positions[b].x, positions[b].y, positions[b].z);
      }
    }
  }
  var lineGeo = new THREE.BufferGeometry();
  lineGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(linePositions), 3));
  var network = new THREE.LineSegments(lineGeo, new THREE.LineBasicMaterial({ color: violetHex, transparent: true, opacity: 0.22 }));
  group.add(network);

  scene.add(new THREE.AmbientLight(0x8fd7ff, 0.6));

  var mouseX = 0, mouseY = 0, targetRotX = 0, targetRotY = 0;
  window.addEventListener('mousemove', function (e) {
    mouseX = (e.clientX / window.innerWidth) * 2 - 1;
    mouseY = (e.clientY / window.innerHeight) * 2 - 1;
  });

  var scrollProgress = { v: 0 };
  ScrollTrigger.create({ trigger: heroSection, start: 'top top', end: 'bottom top', scrub: true, onUpdate: function (self) { scrollProgress.v = self.progress; } });
  gsap.to(wrap, { opacity: 0.15, scrollTrigger: { trigger: heroSection, start: 'top top', end: 'bottom top', scrub: true } });

  var visible = true;
  document.addEventListener('visibilitychange', function () { visible = document.visibilityState === 'visible'; });
  var heroInView = true;
  var io = new IntersectionObserver(function (entries) { heroInView = entries[0].isIntersecting; }, { threshold: 0.01 });
  io.observe(heroSection);

  var clock = new THREE.Clock();
  function animate() {
    requestAnimationFrame(animate);
    if (!visible || !heroInView) return;
    var t = clock.getElapsedTime();
    targetRotX += (mouseY * 0.35 - targetRotX) * 0.05;
    targetRotY += (mouseX * 0.45 - targetRotY) * 0.05;
    group.rotation.x = targetRotX + t * 0.04 + scrollProgress.v * 0.6;
    group.rotation.y = targetRotY + t * 0.06 + scrollProgress.v * 1.1;
    camera.position.z = 7 - scrollProgress.v * 1.8;
    crystal.rotation.y = -t * 0.08;
    renderer.render(scene, camera);
  }
  animate();

  window.addEventListener('resize', function () {
    var w = wrap.clientWidth, h = wrap.clientHeight;
    renderer.setSize(w, h);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
  });
})();
</script>

</body>
</html>

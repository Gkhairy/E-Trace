import { useEffect, useRef, useState } from 'react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import { ParticleField } from './particles';

gsap.registerPlugin(ScrollTrigger);

function Logo({ size = 26 }) {
    return (
        <svg width={size} height={size} viewBox="0 0 512 512" aria-hidden="true">
            <defs>
                <linearGradient id="et-g" gradientUnits="userSpaceOnUse" x1="80" y1="90" x2="430" y2="430">
                    <stop offset="0" stopColor="#3b82f6" />
                    <stop offset="1" stopColor="#1d4ed8" />
                </linearGradient>
            </defs>
            <g fill="none" stroke="url(#et-g)" strokeWidth="46" strokeLinecap="round" strokeLinejoin="round">
                <path d="M 392.4 141.1 A 150 150 0 1 0 392.4 370.9" />
                <path d="M 100 212 H 398" />
                <path d="M 300 212 V 326" />
                <path d="M 100 302 H 218" />
            </g>
        </svg>
    );
}

const Icon = {
    lock: <><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></>,
    cart: <><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /></>,
    clock: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" /></>,
    link: <><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1" /><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1" /></>,
    coins: <><circle cx="8" cy="8" r="6" /><path d="M18.09 10.37A6 6 0 1 1 10.34 18" /><path d="M7 6h1v4" /><path d="m16.71 13.88.7.71-2.82 2.82" /></>,
    card: <><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>,
    users: <><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></>,
    file: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6" /><path d="M8 13h8M8 17h5" /></>,
    heart: <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z" />,
    shield: <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />,
};

function Badge({ icon, tone = 'blue' }) {
    return (
        <div className={`badge badge-${tone}`}>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                {Icon[icon]}
            </svg>
        </div>
    );
}

function Divider() {
    return <div className="divider" />;
}

// A real 3D wireframe cube (six CSS faces, edges only) with a glowing neon outline, which
// keeps tumbling slowly. Each feature starts from its own pose and turns its own way, so
// the set never looks like five copies of one icon.
function Cube({ neon, spin }) {
    const style = {
        '--neon': neon,
        '--rx': `${spin.rx}deg`, '--ry': `${spin.ry}deg`, '--rz': `${spin.rz}deg`,
        '--sx': spin.sx, '--sy': spin.sy, '--dur': `${spin.dur}s`,
    };
    return (
        <span className="cube3d" style={style} aria-hidden="true">
            <i className="f-front" /><i className="f-back" /><i className="f-right" />
            <i className="f-left" /><i className="f-top" /><i className="f-bottom" />
        </span>
    );
}

// Feature blocks "called out" of the particle field: each drops out of the scatter above
// and settles into a loose, uneven cluster, then its label fades in (driven by the scroll
// scene). Clicking a block swaps the copy on the right for that feature's explanation.
const FEATURES = [
    { id: 'paylater', title: 'Paylater — Pinjam & Danai', neon: '#d946ef',
        spin: { rx: -22, ry: 32, rz: 12, sx: 1, sy: 1, dur: 16 },
        body: (t) => <>{t('Belanja sekarang bayar nanti dengan jaminan, atau')} <b>{t('danai')}</b> {t('pool likuiditas dan dapat bagi hasil. Bunga, jangka, & pool semua tercatat on-chain.')}</> },
    { id: 'wallet', title: 'E-Wallet Crypto', neon: '#06b6d4',
        spin: { rx: 38, ry: -42, rz: -18, sx: -1, sy: 1, dur: 12 },
        body: (t) => t('Dompet dalam aplikasi untuk menyimpan & mengirim TLKM/stablecoin, dilindungi PIN.') },
    { id: 'multisig', title: 'Dompet Bersama + Multisig', neon: '#8b5cf6',
        spin: { rx: -48, ry: 125, rz: 28, sx: 1, sy: -1, dur: 19 },
        body: (t) => <>{t('Dana komunitas yang butuh persetujuan beberapa orang (')}<b>{t('M dari N')}</b>{t(') sebelum dicairkan. Setiap usulan & persetujuan tercatat dan bisa diaudit publik.')}</> },
    { id: 'reports', title: 'Laporan Otomatis Penjual', neon: '#0ea5e9',
        spin: { rx: 160, ry: 210, rz: -32, sx: -1, sy: -1, dur: 14 },
        body: (t) => <>{t('Penjual mengunduh laporan penjualan')} <b>{t('Excel & PDF')}</b> {t('otomatis — harian (rincian transaksi) dan bulanan (rekap) — langsung dari transaksi di aplikasi.')}</> },
    { id: 'donation', title: 'Donasi Transparan & Anti-Beku', neon: '#c026d3',
        spin: { rx: 62, ry: -18, rz: 48, sx: 1, sy: 1, dur: 21 },
        body: (t) => t('Donasi tercatat on-chain dan tersalur lewat smart contract — tidak bisa dibekukan sepihak. Belajar dari kasus donasi yang pernah dibekukan di Indonesia: transparansi & desentralisasi menjaga dana tetap sampai ke tujuan.') },
];

function Ecosystem({ t }) {
    const [active, setActive] = useState(null);
    const feature = FEATURES.find((f) => f.id === active);
    const swapRef = useRef(null);
    const firstRender = useRef(true);

    // Fade the copy in whenever the chosen feature changes (not on first paint, so the
    // text is never left invisible if an animation fails to run).
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        gsap.fromTo(swapRef.current, { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 0.45, ease: 'power2.out' });
    }, [active]);

    return (
        <section id="ekosistem" className="section container scene">
            <div className="eco-layout">
                <div className="eco-field">
                    {FEATURES.map((f, i) => (
                        <button
                            key={f.id}
                            type="button"
                            className={`feat feat-${i + 1}${active === f.id ? ' is-active' : ''}${active && active !== f.id ? ' is-dim' : ''}`}
                            aria-pressed={active === f.id}
                            onClick={() => setActive(active === f.id ? null : f.id)}
                        >
                            <span className="feat-block"><span className="cube-wrap"><Cube neon={f.neon} spin={f.spin} /></span></span>
                            <span className="feat-label">{t(f.title)}</span>
                        </button>
                    ))}
                </div>
                <div className="eco-copy" aria-live="polite">
                    <div ref={swapRef} className="eco-swap">
                        {feature ? (
                            <>
                                <p className="eyebrow">{t('Lebih dari Marketplace')}</p>
                                <h2 className="h2-lg">{t(feature.title)}</h2>
                                <p className="lead">{feature.body(t)}</p>
                                <button type="button" className="eco-back" onClick={() => setActive(null)}>← {t('Semua fitur')}</button>
                            </>
                        ) : (
                            <>
                                <p className="eyebrow">{t('Lebih dari Marketplace')}</p>
                                <h2 className="h2-lg">{t('Ekosistem keuangan yang transparan & bisa diaudit.')}</h2>
                                <p className="lead">{t('Pilih salah satu fitur untuk melihat cara kerjanya.')}</p>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

export default function App({ t, year, locale, langUrls }) {
    const fieldRef = useRef(null);

    useEffect(() => {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const mobile = window.innerWidth < 768;

        let field = null;
        try {
            field = new ParticleField(fieldRef.current, {
                count: mobile ? 2200 : 5000,
                reduceMotion,
                font: "'Bricolage Grotesque', 'Arial Black', sans-serif",
                photo: '/img/tlkm-coins.png?v=2',
            });
        } catch (err) {
            // No WebGL: the page still reads fine without the particles.
            console.warn('Particle field disabled:', err);
        }

        let lenis = null;
        let tickerFn = null;
        if (!reduceMotion) {
            lenis = new Lenis({ lerp: 0.1, smoothWheel: true });
            lenis.on('scroll', ScrollTrigger.update);
            tickerFn = (time) => lenis.raf(time * 1000);
            gsap.ticker.add(tickerFn);
            gsap.ticker.lagSmoothing(0);
        }
        if (import.meta.env.DEV) Object.assign(window, { __welcomeLenis: lenis, __welcomeField: field });

        const anchorClicks = [...document.querySelectorAll('a[href^="#"]')].map((a) => {
            const fn = (e) => {
                const target = document.querySelector(a.getAttribute('href'));
                if (!target) return;
                e.preventDefault();
                if (lenis) lenis.scrollTo(target, { duration: 1.1 });
                else target.scrollIntoView({ behavior: 'smooth' });
            };
            a.addEventListener('click', fn);
            return [a, fn];
        });

        // Scrollytelling. Scenes are pinned while scroll plays them; between scenes a
        // "segment" trigger carries the particles from one shape to the next. The stage is
        // simply the sum of segment progress:
        // 0 sphere, 1 coin, 2 scatter (problem), 3 bulb (solution), 4 scatter, 5 logo.
        // Triggers are created in page order so each accounts for the pin spacing above it.
        const segments = [];
        let heroScene = null;
        let coinScene = null;
        const hilite = (color, glow) => ({ opacity: 1, scale: 1.03, borderColor: color, boxShadow: `0 20px 44px -20px ${glow}` });
        const rest = { scale: 1, borderColor: '#e2e8f0', boxShadow: '0 1px 2px rgba(15,23,42,0.04)' };

        const ctx = gsap.context(() => {
            const animate = !reduceMotion;
            // Scenes only pin when they fit on one screen. On phones they are taller than the
            // viewport, so the same timelines play as the section scrolls past instead.
            const canPin = window.innerWidth >= 768 && window.innerHeight >= 620;
            const scene = (trigger, length) => (canPin || trigger === '#hero'
                ? { trigger, start: 'top top', end: `+=${length}%`, pin: true, scrub: true, anticipatePin: 1 }
                : { trigger, start: 'top 60%', end: 'bottom 60%', scrub: true });

            if (animate) {
                gsap.utils.toArray('[data-reveal="hero"]').forEach((el, i) =>
                    gsap.fromTo(el, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 1, delay: 0.15 + i * 0.1, ease: 'power3.out' }),
                );
            }

            // 1. Hero: held while the camera pushes into the sphere and the copy lifts away.
            if (animate) {
                const tl = gsap.timeline({ scrollTrigger: scene('#hero', 90) });
                tl.to('#hero .hero-content', { scale: 1.1, opacity: 0, y: -30, ease: 'power1.in' })
                    .to('#hero .scroll-hint', { opacity: 0, duration: 0.3 }, 0);
                heroScene = tl.scrollTrigger;
            }
            segments.push(ScrollTrigger.create({ trigger: '#paylater', start: 'top bottom', end: 'top top' }));

            // 2. Paylater: the coin turns with the scroll while Borrow, then Supply, light up.
            if (animate) {
                const tl = gsap.timeline({ scrollTrigger: scene('#paylater', 140) });
                tl.fromTo('#paylater .pl-borrow', { opacity: 0.4, scale: 0.96 }, { ...hilite('#2563eb', 'rgba(37,99,235,0.45)'), duration: 1 })
                    .fromTo('#paylater .pl-supply', { opacity: 0.4, scale: 0.96 }, { ...hilite('#e5121f', 'rgba(229,18,31,0.4)'), duration: 1 }, '+=0.4')
                    .to('#paylater .pl-borrow', { ...rest, opacity: 0.6, duration: 1 }, '<')
                    .to({}, { duration: 0.4 });
                coinScene = tl.scrollTrigger;
            }
            segments.push(ScrollTrigger.create({ trigger: '#masalah', start: 'top bottom', end: 'top top' }));

            // 3. Problem: the conventional way, point by point, over scattered particles.
            if (animate) {
                gsap.timeline({ scrollTrigger: scene('#masalah', 80) })
                    .fromTo('#masalah .old-item', { opacity: 0, x: -24 }, { opacity: 1, x: 0, duration: 1, stagger: 0.8 })
                    .to({}, { duration: 0.6 });
            }
            segments.push(ScrollTrigger.create({ trigger: '#solusi', start: 'top bottom', end: 'top top' }));

            // 4. Solution: the particles light up as an idea bulb while each point arrives.
            if (animate) {
                gsap.timeline({ scrollTrigger: scene('#solusi', 110) })
                    .fromTo('#solusi .sol-item', { opacity: 0, y: 26 }, { opacity: 1, y: 0, duration: 1, stagger: 0.7 })
                    .to({}, { duration: 0.6 });
            }
            segments.push(ScrollTrigger.create({ trigger: '#cara-kerja', start: 'top bottom', end: 'top top' }));

            // 5. How it works: steps 01 -> 02 -> 03 light up in turn along a progress bar.
            if (animate) {
                const steps = gsap.utils.toArray('#cara-kerja .step');
                const tl = gsap.timeline({ scrollTrigger: scene('#cara-kerja', 160) });
                tl.fromTo('#cara-kerja .steps-bar i', { scaleX: 0 }, { scaleX: 1, ease: 'none', duration: steps.length }, 0);
                steps.forEach((el, i) => {
                    tl.fromTo(el, { opacity: 0.3, y: 24, scale: 0.96 }, { ...hilite('#2563eb', 'rgba(37,99,235,0.4)'), y: 0, duration: 0.5 }, i);
                    if (i < steps.length - 1) tl.to(el, { ...rest, duration: 0.5 }, i + 0.6);
                });
            }

            // 6. Ecosystem: feature blocks drop one at a time out of the particle field above
            // the top of the screen, fading in as they fall, then settle and get their label
            // (like the reference's investors).
            if (animate) {
                const tl = gsap.timeline({ scrollTrigger: { ...scene('#ekosistem', 130), invalidateOnRefresh: true } });
                tl.fromTo('#ekosistem .eco-copy', { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.8 });
                const sec = document.getElementById('ekosistem');
                gsap.utils.toArray('#ekosistem .feat').forEach((el, i) => {
                    const block = el.querySelector('.feat-block');
                    const r = gsap.utils.random;
                    // Distance from the block up past the top edge of the (pinned) section.
                    const fall = () => block.getBoundingClientRect().top - sec.getBoundingClientRect().top + r(160, 380);
                    tl.fromTo(block,
                        { opacity: 0, scale: 0.35, x: () => r(-140, 140), y: () => -fall() },
                        { opacity: 1, scale: 1, x: 0, y: 0, duration: 1.1, ease: 'power3.out' }, 0.4 + i * 0.5)
                        .fromTo(el.querySelector('.feat-label'), { opacity: 0, x: -14 }, { opacity: 1, x: 0, duration: 0.5 }, 1.1 + i * 0.5);
                });
                tl.to({}, { duration: 0.6 });
            }

            segments.push(ScrollTrigger.create({ trigger: '#cta', start: 'top bottom', end: 'center center' }));

            // Unpinned sections: a gentle zoom-in as they arrive, plus the usual reveals.
            if (animate) {
                gsap.utils.toArray('main > section:not(.scene):not(#hero)').forEach((sec) => {
                    gsap.fromTo(sec, { scale: 0.92, opacity: 0.3 }, {
                        scale: 1, opacity: 1, ease: 'none',
                        scrollTrigger: { trigger: sec, start: 'top bottom', end: 'top 40%', scrub: true },
                    });
                });
                const onEnter = (el, from, extra = {}) =>
                    gsap.fromTo(el, { opacity: 0, ...from }, {
                        opacity: 1, x: 0, y: 0, duration: 0.9, ease: 'power3.out',
                        scrollTrigger: { trigger: el, start: 'top 82%' }, ...extra,
                    });
                gsap.utils.toArray('[data-reveal="fade"]').forEach((el) => onEnter(el, { y: 28 }));
                gsap.utils.toArray('[data-reveal="left"]').forEach((el) => onEnter(el, { x: -40 }));
                gsap.utils.toArray('[data-reveal="right"]').forEach((el) => onEnter(el, { x: 40 }));
                gsap.utils.toArray('[data-reveal="card"]').forEach((el, i) =>
                    onEnter(el, { y: 24 }, { duration: 0.7, delay: (i % 4) * 0.1, scrollTrigger: { trigger: el.closest('section'), start: 'top 75%' } }),
                );
            }
        });

        const drive = () => {
            if (!field) return;
            field.setStage(segments.reduce((sum, st) => sum + st.progress, 0));
            field.setPush(heroScene ? heroScene.progress * (1 - segments[0].progress) : 0);
            field.setTurn(coinScene ? (coinScene.progress - 0.5) * 0.9 : 0);
        };
        gsap.ticker.add(drive);

        const ro = new ResizeObserver(() => ScrollTrigger.refresh());
        ro.observe(document.body);

        return () => {
            gsap.ticker.remove(drive);
            ctx.revert();
            ro.disconnect();
            anchorClicks.forEach(([a, fn]) => a.removeEventListener('click', fn));
            if (tickerFn) gsap.ticker.remove(tickerFn);
            lenis?.destroy();
            field?.dispose();
        };
    }, []);

    return (
        <div className="wp">
            <div ref={fieldRef} className="field" aria-hidden="true" />
            <header className="nav">
                <div className="nav-inner">
                    <a href="/" className="brand">
                        <Logo />
                        E-Trace
                    </a>
                    <div className="nav-links">
                        <a href="/products" className="nav-link">{t('Lihat Katalog')}</a>
                        <a href="/login" className="btn btn-dark">{t('Masuk Toko')}</a>
                    </div>
                </div>
            </header>

            <main>
                <section id="hero" className="hero">
                    <div className="container hero-content">
                        <div className="chip" data-reveal="hero">
                            <span className="dot" />
                            {t('Escrow trustless · BNB Chain · Token TLKM')}
                        </div>
                        <h1 data-reveal="hero">
                            {t('Marketplace yang')} <span className="accent">{t('transparan sepenuhnya.')}</span>
                        </h1>
                        <p className="lead" data-reveal="hero">
                            {t('Login dengan wallet, tanpa password. Setiap pembayaran ditahan smart contract hingga barang diterima — dan setiap transaksi bisa diverifikasi siapa saja di block explorer.')}
                        </p>
                        <div className="actions" data-reveal="hero">
                            <a href="/login" className="btn btn-primary">{t('Masuk Toko')}</a>
                            <a href="#masalah" className="btn btn-ghost">{t('Pelajari')}</a>
                        </div>
                    </div>
                    <div className="scroll-hint" data-reveal="hero">
                        <span>SCROLL</span>
                        <i />
                    </div>
                </section>

                <section id="paylater" className="section split scene">
                    <div className="container split-grid split-reverse">
                        {/* The particle field rebuilds the TLKM coins here as a pixel mosaic. */}
                        <div className="split-visual" />
                        <div>
                            <h2 className="h2-lg" data-reveal="left">
                                {t('Belanja dulu, bayar nanti —')} <span className="accent">{t('atau danai, panen bagi hasil.')}</span>
                            </h2>
                            <p className="lead" data-reveal="left">
                                {t('TLKM bukan cuma alat bayar. Kunci jaminan untuk dapat limit belanja, atau setor ke pool likuiditas dan dapat bagi hasil dari bunga peminjam. Semua tercatat di smart contract — pool, bunga, dan jangka waktunya terbuka untuk siapa saja.')}
                            </p>
                            <div className="pair">
                                <div className="card card-blue pl-borrow">
                                    <div className="card-title"><span className="dot" />{t('Pinjam')}</div>
                                    <p>{t('Jaminkan aset, dapat limit TLKM. Checkout sekarang, lunasi sesuai jangka yang kamu pilih.')}</p>
                                </div>
                                <div className="card card-red pl-supply">
                                    <div className="card-title"><span className="dot dot-red" />{t('Danai')}</div>
                                    <p>{t('Setor TLKM ke pool — fleksibel, 30, atau 90 hari. Bagi hasil naik seiring jangka.')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <Divider />

                {/* Problem: the conventional way, over scattered particles. */}
                <section id="masalah" className="section container scene">
                    <div className="heading" data-reveal="fade">
                        <p className="eyebrow">{t('Masalah & Solusi')}</p>
                        <h2>{t('Marketplace biasa menahan dana Anda di tempat yang gelap.')}</h2>
                    </div>
                    <div className="card card-lg problem-card">
                        <p className="muted-label">{t('Cara konvensional')}</p>
                        <h3>{t('Perantara memegang dana')}</h3>
                        <ul className="list list-dash">
                            <li className="old-item">{t('Dana pembeli dipegang platform, prosesnya tidak terlihat.')}</li>
                            <li className="old-item">{t('Sengketa bergantung keputusan sepihak platform, tanpa bukti terbuka.')}</li>
                            <li className="old-item">{t('Tidak ada cara publik memverifikasi transaksi terjadi.')}</li>
                        </ul>
                    </div>
                </section>

                {/* Solution: the particles light up as an idea bulb on the left. */}
                <section id="solusi" className="section split scene">
                    <div className="container split-grid split-reverse">
                        <div className="split-visual" />
                        <div>
                            <p className="eyebrow sol-item">{t('Pendekatan E-Trace')}</p>
                            <h2 className="h2-lg sol-item">{t('Smart contract memegang dana')}</h2>
                            <ul className="list list-check list-lg">
                                <li className="sol-item">{t('Dana ditahan escrow on-chain, dilepas hanya saat pembeli konfirmasi.')}</li>
                                <li className="sol-item">{t('Ada masalah? Ajukan sengketa dengan bukti — diputus pengawas, bukan refund otomatis.')}</li>
                                <li className="sol-item">{t('Setiap transaksi tercatat & terbuka di block explorer.')}</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <Divider />

                <section id="cara-kerja" className="section container scene">
                    <div className="heading" data-reveal="fade">
                        <p className="eyebrow">{t('Cara Kerja')}</p>
                        <h2>{t('Tiga langkah, dana selalu di bawah kendali Anda.')}</h2>
                    </div>
                    <div className="steps-bar" aria-hidden="true"><i /></div>
                    <div className="grid grid-3">
                        {[
                            ['01', 'Daftar & Dapat Wallet', 'Daftar dengan email + PIN atau MetaMask — tanpa seed phrase. Wallet dibuat otomatis untuk Anda.'],
                            ['02', 'Bayar via Escrow', 'Bayar dengan token TLKM. Dana masuk ke smart contract escrow, terpisah per penjual.'],
                            ['03', 'Konfirmasi & Dana Lepas', 'Barang diterima, Anda konfirmasi — escrow melepas dana ke penjual. Bermasalah? Ajukan sengketa dengan bukti; pengawas yang memutus.'],
                        ].map(([n, title, body]) => (
                            <div className="card step" key={n}>
                                <span className="num">{n}</span>
                                <h3>{t(title)}</h3>
                                <p>{t(body)}</p>
                            </div>
                        ))}
                    </div>
                </section>

                <Divider />

                <section id="sengketa" className="section container">
                    <div className="heading" data-reveal="fade">
                        <p className="eyebrow">{t('Penyelesaian Sengketa')}</p>
                        <h2>
                            {t('Adil untuk pembeli')} <span className="accent">{t('dan')}</span> {t('penjual.')}
                        </h2>
                        <p className="lead">
                            {t('Escrow menahan dana sampai transaksi selesai. Jika ada masalah, keputusan tidak diambil sepihak — sengketa diputus berdasarkan')}{' '}
                            <b>{t('bukti')}</b>{t(', bukan sekadar komplain.')}
                        </p>
                    </div>
                    <div className="grid grid-3">
                        <div className="card" data-reveal="card">
                            <span className="num num-blue">01</span>
                            <h3>{t('Dana ditahan escrow')}</h3>
                            <p>{t('Pembayaran dikunci di smart contract. Penjual tidak bisa kabur membawa uang, pembeli tidak bisa menahan barang tanpa bayar.')}</p>
                        </div>
                        <div className="card" data-reveal="card">
                            <span className="num num-blue">02</span>
                            <h3>{t('Ajukan sengketa + bukti')}</h3>
                            <p>{t('Pembeli maupun penjual melampirkan bukti: nomor resi/tracking pengiriman, foto barang, dan kronologi. Semua tercatat.')}</p>
                        </div>
                        <div className="card card-blue" data-reveal="card">
                            <span className="num num-blue">03</span>
                            <h3>{t('Pengawas memutus')}</h3>
                            <p>
                                {t('Pengawas (supervisor) meninjau bukti kedua pihak dan memutuskan. Dana dilepas ke pihak yang benar —')}{' '}
                                <b>{t('bukan refund otomatis')}</b> {t('hanya karena komplain.')}
                            </p>
                        </div>
                    </div>
                    <div className="note" data-reveal="fade">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" strokeWidth="2">{Icon.shield}</svg>
                        <p><b>{t('Melindungi kedua belah pihak:')}</b> {t('pembeli tidak bisa asal klaim untuk menahan uang, dan penjual tidak bisa kabur dengan dana. Keputusan berbasis bukti dan tercatat on-chain.')}</p>
                    </div>
                </section>

                <Divider />

                <section className="section container">
                    <div className="heading" data-reveal="fade">
                        <p className="eyebrow">{t('Fitur Unggulan')}</p>
                        <h2>{t('Dibangun untuk kepercayaan yang bisa dibuktikan.')}</h2>
                    </div>
                    <div className="grid grid-4">
                        {[
                            ['lock', 'blue', 'Escrow Trustless', 'Dana ditahan smart contract, dilepas hanya saat pembeli konfirmasi barang diterima.'],
                            ['cart', 'violet', 'Multi-Penjual', 'Satu pembayaran, banyak penjual — tiap penjual punya escrow terpisah yang independen.'],
                            ['clock', 'blue', 'Token TLKM', 'Token BEP-20 milik platform, digunakan untuk seluruh transaksi di jaringan BNB Smart Chain.'],
                            ['link', 'violet', 'Transparansi On-Chain', 'Setiap transaksi tercatat permanen dan bisa diverifikasi siapa saja di block explorer.'],
                        ].map(([icon, tone, title, body]) => (
                            <div className="card" key={title} data-reveal="card">
                                <Badge icon={icon} tone={tone} />
                                <h3>{t(title)}</h3>
                                <p>{t(body)}</p>
                            </div>
                        ))}
                    </div>
                </section>

                <Divider />

                <Ecosystem t={t} />

                <Divider />

                <section id="transparansi" className="section container">
                    <div className="grid grid-2 center">
                        <div data-reveal="left">
                            <p className="eyebrow">{t('Transparansi Penuh')}</p>
                            <h2>{t('Tak perlu percaya kami. Verifikasi sendiri.')}</h2>
                            <p className="lead">{t('Tiap escrow, pembayaran, dan pelepasan dana adalah entri publik di BNB Smart Chain — bisa dibuka lewat block explorer mana pun. Cocok untuk lembaga dan mitra yang butuh jejak akuntabilitas yang tidak bisa diubah sepihak.')}</p>
                        </div>
                        <div className="card" data-reveal="right">
                            <p className="muted-label caps">{t('Contoh entri escrow on-chain')}</p>
                            <div className="ledger">
                                {[
                                    ['0x8f...2a91', 'Escrow dibuat · Penjual A', 'Ditahan', 'held'],
                                    ['0x3c...9e0d', 'Konfirmasi diterima · Penjual B', 'Dilepas', 'released'],
                                    ['0x1b...7f44', 'Refund diproses · Penjual C', 'Refund', 'refund'],
                                ].map(([hash, label, status, tone]) => (
                                    <div className="ledger-row" key={hash}>
                                        <div>
                                            <p className="mono">{hash}</p>
                                            <p className="small">{t(label)}</p>
                                        </div>
                                        <span className={`pill pill-${tone}`}>{t(status)}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                <section id="cta" className="section container cta">
                    <div className="split-grid">
                        <div data-reveal="fade">
                            <h2 className="h2-xl">{t('Belanja dengan transparansi yang bisa Anda buktikan sendiri.')}</h2>
                            <div className="actions">
                                <a href="/login" className="btn btn-primary">{t('Masuk Toko')}</a>
                                <a href="/products" className="btn btn-ghost">{t('Lihat Katalog')}</a>
                            </div>
                        </div>
                        {/* The particle field forms the E-Trace mark here. */}
                        <div className="split-visual" />
                    </div>
                </section>
            </main>

            <footer className="footer">
                <div className="container footer-inner">
                    <p>© {year} E-Trace. {t('footer.rights')}</p>
                    <div className="footer-links">
                        <a href="/products">{t('footer.catalog')}</a>
                        <a href="/login">{t('footer.login')}</a>
                        <span className="lang">
                            <a href={langUrls.id} className={locale === 'id' ? 'on' : ''}>ID</a>
                            <a href={langUrls.en} className={locale === 'en' ? 'on' : ''}>EN</a>
                        </span>
                    </div>
                </div>
            </footer>
        </div>
    );
}

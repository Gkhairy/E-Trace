import { useEffect, useRef } from 'react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import { ParticleField } from './particles';

gsap.registerPlugin(ScrollTrigger);

// Section id -> particle shape it should show when centred on screen.
// 0 sphere, 1 coin, 2 scatter, 3 logo. Repeating a stage holds that shape across sections.
const ANCHORS = [
    ['hero', 0],
    ['paylater', 1],
    ['solusi', 2],
    ['transparansi', 2],
    ['cta', 3],
];

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
            });
        } catch (err) {
            // No WebGL: the page still reads fine without the particles.
            console.warn('Particle field disabled:', err);
        }

        // Scroll position -> stage, from the centre of each anchor section.
        let points = [];
        const measure = () => {
            const vh = window.innerHeight;
            const max = document.documentElement.scrollHeight - vh;
            points = ANCHORS.map(([id, stage]) => {
                const el = document.getElementById(id);
                if (!el) return null;
                const at = id === 'hero' ? 0 : el.offsetTop + el.offsetHeight / 2 - vh / 2;
                return [Math.min(Math.max(at, 0), max), stage];
            }).filter(Boolean);
        };
        const stageAt = (y) => {
            if (!points.length || y <= points[0][0]) return points[0]?.[1] ?? 0;
            for (let i = 1; i < points.length; i++) {
                const [y1, s1] = points[i];
                const [y0, s0] = points[i - 1];
                if (y <= y1) return s0 + (s1 - s0) * ((y - y0) / Math.max(1, y1 - y0));
            }
            return points[points.length - 1][1];
        };
        const onScroll = (y) => field?.setStage(stageAt(y));

        measure();
        const ro = new ResizeObserver(() => {
            measure();
            ScrollTrigger.refresh();
        });
        ro.observe(document.body);

        let lenis = null;
        let tickerFn = null;
        const onNativeScroll = () => onScroll(window.scrollY);
        if (!reduceMotion) {
            lenis = new Lenis({ lerp: 0.1, smoothWheel: true });
            lenis.on('scroll', ({ scroll }) => {
                ScrollTrigger.update();
                onScroll(scroll);
            });
            tickerFn = (time) => lenis.raf(time * 1000);
            gsap.ticker.add(tickerFn);
            gsap.ticker.lagSmoothing(0);
        } else {
            window.addEventListener('scroll', onNativeScroll, { passive: true });
        }
        onScroll(window.scrollY);
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

        const ctx = gsap.context(() => {
            if (reduceMotion) return;
            gsap.utils.toArray('[data-reveal="hero"]').forEach((el, i) =>
                gsap.fromTo(el, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 1, delay: 0.15 + i * 0.1, ease: 'power3.out' }),
            );
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
        });

        return () => {
            ctx.revert();
            ro.disconnect();
            anchorClicks.forEach(([a, fn]) => a.removeEventListener('click', fn));
            window.removeEventListener('scroll', onNativeScroll);
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
                            <a href="#solusi" className="btn btn-ghost">{t('Pelajari')}</a>
                        </div>
                    </div>
                    <div className="scroll-hint" data-reveal="hero">
                        <span>SCROLL</span>
                        <i />
                    </div>
                </section>

                <section id="paylater" className="section split">
                    <div className="container split-grid">
                        <div>
                            <h2 className="h2-lg" data-reveal="left">
                                {t('Belanja dulu, bayar nanti —')} <span className="accent">{t('atau danai, panen bagi hasil.')}</span>
                            </h2>
                            <p className="lead" data-reveal="left">
                                {t('TLKM bukan cuma alat bayar. Kunci jaminan untuk dapat limit belanja, atau setor ke pool likuiditas dan dapat bagi hasil dari bunga peminjam. Semua tercatat di smart contract — pool, bunga, dan jangka waktunya terbuka untuk siapa saja.')}
                            </p>
                            <div className="pair" data-reveal="left">
                                <div className="card card-blue">
                                    <div className="card-title"><span className="dot" />{t('Pinjam')}</div>
                                    <p>{t('Jaminkan aset, dapat limit TLKM. Checkout sekarang, lunasi sesuai jangka yang kamu pilih.')}</p>
                                </div>
                                <div className="card card-red">
                                    <div className="card-title"><span className="dot dot-red" />{t('Danai')}</div>
                                    <p>{t('Setor TLKM ke pool — fleksibel, 30, atau 90 hari. Bagi hasil naik seiring jangka.')}</p>
                                </div>
                            </div>
                        </div>
                        {/* The coin is drawn by the particle field in this space. */}
                        <div className="split-visual" />
                    </div>
                </section>

                <Divider />

                <section id="solusi" className="section container">
                    <div className="heading" data-reveal="fade">
                        <p className="eyebrow">{t('Masalah & Solusi')}</p>
                        <h2>{t('Marketplace biasa menahan dana Anda di tempat yang gelap.')}</h2>
                    </div>
                    <div className="grid grid-2">
                        <div className="card card-lg" data-reveal="left">
                            <p className="muted-label">{t('Cara konvensional')}</p>
                            <h3 className="h3-muted">{t('Perantara memegang dana')}</h3>
                            <ul className="list list-dash">
                                <li>{t('Dana pembeli dipegang platform, prosesnya tidak terlihat.')}</li>
                                <li>{t('Sengketa bergantung keputusan sepihak platform, tanpa bukti terbuka.')}</li>
                                <li>{t('Tidak ada cara publik memverifikasi transaksi terjadi.')}</li>
                            </ul>
                        </div>
                        <div className="card card-lg card-blue" data-reveal="right">
                            <p className="accent-label">{t('Pendekatan E-Trace')}</p>
                            <h3>{t('Smart contract memegang dana')}</h3>
                            <ul className="list list-check">
                                <li>{t('Dana ditahan escrow on-chain, dilepas hanya saat pembeli konfirmasi.')}</li>
                                <li>{t('Ada masalah? Ajukan sengketa dengan bukti — diputus pengawas, bukan refund otomatis.')}</li>
                                <li>{t('Setiap transaksi tercatat & terbuka di block explorer.')}</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <Divider />

                <section className="section container">
                    <div className="heading" data-reveal="fade">
                        <p className="eyebrow">{t('Cara Kerja')}</p>
                        <h2>{t('Tiga langkah, dana selalu di bawah kendali Anda.')}</h2>
                    </div>
                    <div className="grid grid-3">
                        {[
                            ['01', 'Daftar & Dapat Wallet', 'Daftar dengan email + PIN atau MetaMask — tanpa seed phrase. Wallet dibuat otomatis untuk Anda.'],
                            ['02', 'Bayar via Escrow', 'Bayar dengan token TLKM. Dana masuk ke smart contract escrow, terpisah per penjual.'],
                            ['03', 'Konfirmasi & Dana Lepas', 'Barang diterima, Anda konfirmasi — escrow melepas dana ke penjual. Bermasalah? Ajukan sengketa dengan bukti; pengawas yang memutus.'],
                        ].map(([n, title, body]) => (
                            <div className="card" key={n} data-reveal="card">
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

                <section className="section container">
                    <div className="heading" data-reveal="fade">
                        <p className="eyebrow">{t('Lebih dari Marketplace')}</p>
                        <h2>{t('Ekosistem keuangan yang transparan & bisa diaudit.')}</h2>
                    </div>
                    <div className="grid grid-5">
                        <div className="card" data-reveal="card">
                            <Badge icon="coins" tone="red" />
                            <h3>{t('Paylater — Pinjam & Danai')}</h3>
                            <p>{t('Belanja sekarang bayar nanti dengan jaminan, atau')} <b>{t('danai')}</b> {t('pool likuiditas dan dapat bagi hasil. Bunga, jangka, & pool semua tercatat on-chain.')}</p>
                        </div>
                        <div className="card" data-reveal="card">
                            <Badge icon="card" />
                            <h3>{t('E-Wallet Crypto')}</h3>
                            <p>{t('Dompet dalam aplikasi untuk menyimpan & mengirim TLKM/stablecoin, dilindungi PIN.')}</p>
                        </div>
                        <div className="card" data-reveal="card">
                            <Badge icon="users" tone="violet" />
                            <h3>{t('Dompet Bersama + Multisig')}</h3>
                            <p>{t('Dana komunitas yang butuh persetujuan beberapa orang (')}<b>{t('M dari N')}</b>{t(') sebelum dicairkan. Setiap usulan & persetujuan tercatat dan bisa diaudit publik.')}</p>
                        </div>
                        <div className="card" data-reveal="card">
                            <Badge icon="file" />
                            <h3>{t('Laporan Otomatis Penjual')}</h3>
                            <p>{t('Penjual mengunduh laporan penjualan')} <b>{t('Excel & PDF')}</b> {t('otomatis — harian (rincian transaksi) dan bulanan (rekap) — langsung dari transaksi di aplikasi.')}</p>
                        </div>
                        <div className="card" data-reveal="card">
                            <Badge icon="heart" tone="red" />
                            <h3>{t('Donasi Transparan & Anti-Beku')}</h3>
                            <p>{t('Donasi tercatat on-chain dan tersalur lewat smart contract — tidak bisa dibekukan sepihak. Belajar dari kasus donasi yang pernah dibekukan di Indonesia: transparansi & desentralisasi menjaga dana tetap sampai ke tujuan.')}</p>
                        </div>
                    </div>
                </section>

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

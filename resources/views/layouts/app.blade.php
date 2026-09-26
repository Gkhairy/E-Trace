<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>{{ $title ?? 'E-Trace' }}</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Palet LIGHT e-commerce (nama token lama dipertahankan agar
                        // referensi sisa tetap tampil terang).
                        darkbg:   '#f7f8fa',  // background halaman (abu sangat terang)
                        darkcard: '#ffffff',  // kartu / panel putih
                        darkcard2:'#f1f5f9',  // panel abu halus (slate-100)
                        brand:    '#2563eb',  // biru primary (blue-600)
                    },
                    fontFamily: {
                        sans: ['Hanken Grotesk', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Ethers v6 (UMD build — WAJIB pakai .umd, kalau .min.js (ESM) 'ethers is not defined') -->
    <script src="https://cdn.jsdelivr.net/npm/ethers@6.7.1/dist/ethers.umd.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        html { scroll-behavior: smooth; }
        body { background: #f7f8fa; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: #eef1f5; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { animation: spin .7s linear infinite; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes toastIn { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: translateX(0); } }
        .anim-fade  { animation: fadeIn .18s ease-out; }
        .anim-slide { animation: slideUp .22s cubic-bezier(.16,1,.3,1); }
        .anim-toast { animation: toastIn .25s cubic-bezier(.16,1,.3,1); }

        /* ============ LENDING DESK (halaman Dompet) — tema terang ============ */
        .ld-num{ font-variant-numeric:tabular-nums; letter-spacing:-.01em; }
        .ld-nisbah{ font-variant-numeric:tabular-nums; font-weight:800; color:#16a34a; }

        /* --- custom animated term dropdown (light) --- */
        .ld-select{ position:relative; }
        .ld-trigger{
            display:flex; align-items:center; gap:.6rem; width:100%;
            background:#fff; border:1px solid #cbd5e1; color:#0f172a;
            border-radius:12px; padding:.6rem .85rem; cursor:pointer; transition:border-color .15s, box-shadow .15s;
        }
        .ld-trigger:hover{ border-color:#86efac; }
        .ld-select[data-open="true"] .ld-trigger{ border-color:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.15); }
        .ld-chev{ margin-left:auto; transition:transform .28s cubic-bezier(.16,1,.3,1); color:#94a3b8; }
        .ld-select[data-open="true"] .ld-chev{ transform:rotate(180deg); }
        .ld-menu{
            position:absolute; z-index:30; left:0; right:0; bottom:calc(100% + 8px);
            background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:.35rem;
            box-shadow:0 20px 40px -16px rgba(15,23,42,.28);
            opacity:0; transform:translateY(8px) scale(.97); transform-origin:bottom center; pointer-events:none;
            transition:opacity .16s ease, transform .24s cubic-bezier(.16,1,.3,1);
        }
        .ld-select[data-open="true"] .ld-menu{ opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
        .ld-opt{
            display:flex; align-items:center; gap:.7rem; padding:.55rem .65rem; border-radius:10px; cursor:pointer;
            opacity:0; transform:translateY(6px); transition:background .15s;
        }
        .ld-select[data-open="true"] .ld-opt{ animation:ldOptIn .32s cubic-bezier(.16,1,.3,1) forwards; }
        .ld-select[data-open="true"] .ld-opt:nth-child(1){ animation-delay:.03s; }
        .ld-select[data-open="true"] .ld-opt:nth-child(2){ animation-delay:.07s; }
        .ld-select[data-open="true"] .ld-opt:nth-child(3){ animation-delay:.11s; }
        @keyframes ldOptIn{ to{ opacity:1; transform:translateY(0); } }
        .ld-opt:hover{ background:#f1f5f9; }
        .ld-opt[aria-selected="true"]{ background:#f0fdf4; }
        .ld-opt-check{ margin-left:auto; color:#16a34a; opacity:0; transition:opacity .15s; }
        .ld-opt[aria-selected="true"] .ld-opt-check{ opacity:1; }
        @media (prefers-reduced-motion: reduce){
            .ld-chev,.ld-menu,.ld-opt{ transition:none; }
            .ld-select[data-open="true"] .ld-opt{ animation:none; opacity:1; transform:none; }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex flex-col">

    <!-- ================= HEADER ================= -->
    <header class="sticky top-0 z-40 w-full bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 md:px-8 h-16 flex items-center gap-4">

            <!-- LOGO -->
            <a href="/products" class="flex items-center gap-2 shrink-0">
                <img src="{{ asset('favicon.svg') }}" alt="" class="w-8 h-8 shrink-0">
                <span class="text-lg font-bold tracking-tight text-slate-900 hidden sm:inline">E-<span class="text-blue-600">Trace</span></span>
            </a>

            <!-- SEARCH (lebar, tengah) — cari produk, toko, orang -->
            <form action="/search" method="GET" role="search" class="relative flex-1 max-w-2xl">
                <svg class="w-5 h-5 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="navSearchInput" type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('nav.search_placeholder') }}" autocomplete="off"
                    class="w-full bg-slate-100 focus:bg-white text-sm rounded-xl pl-10 pr-4 py-2.5 outline-none text-slate-800 placeholder-slate-400 border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                {{-- Saran produk langsung (autocomplete) --}}
                <div id="navSearchSuggest" class="hidden absolute left-0 right-0 top-full mt-2 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden z-[60]"></div>
            </form>
            <script>
            (function () {
                const input = document.getElementById('navSearchInput');
                const box = document.getElementById('navSearchSuggest');
                if (!input || !box) return;
                let timer = null, lastQ = null;
                const esc = (s) => (s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
                const hide = () => { box.classList.add('hidden'); box.innerHTML = ''; };

                function render(q, products) {
                    if (!products.length) {
                        box.innerHTML = `<div class="px-4 py-3 text-sm text-slate-400">Tidak ada produk untuk "${esc(q)}"</div>`;
                        box.classList.remove('hidden');
                        return;
                    }
                    const rows = products.map((p) => `
                        <a href="${esc(p.url)}" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 transition">
                            <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
                                ${p.image ? `<img src="${esc(p.image)}" onerror="this.style.display='none'" class="w-full h-full object-contain">` : '<svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v12H4z"/></svg>'}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-800 truncate">${esc(p.name)}</p>
                                <p class="text-xs text-slate-400 truncate">${p.store ? esc(p.store) + ' · ' : ''}${esc(p.price)} TLKM</p>
                            </div>
                        </a>`).join('');
                    const footer = `<a href="/search?q=${encodeURIComponent(q)}" class="block px-4 py-2.5 text-sm text-blue-600 hover:bg-slate-50 border-t border-slate-100 font-medium">Lihat semua hasil untuk "${esc(q)}" →</a>`;
                    box.innerHTML = rows + footer;
                    box.classList.remove('hidden');
                }

                async function run() {
                    const q = input.value.trim();
                    if (q.length < 2) { hide(); lastQ = null; return; }
                    if (q === lastQ) { if (box.innerHTML) box.classList.remove('hidden'); return; }
                    lastQ = q;
                    try {
                        const d = await (await fetch('/search/suggest?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })).json();
                        if (input.value.trim() !== q) return; // hasil basi
                        render(q, d.products || []);
                    } catch (_) { hide(); }
                }

                input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(run, 180); });
                input.addEventListener('focus', () => { if (input.value.trim().length >= 2) run(); });
                input.addEventListener('keydown', (e) => { if (e.key === 'Escape') hide(); });
                document.addEventListener('click', (e) => { if (!box.contains(e.target) && e.target !== input) hide(); });
            })();
            </script>

            <!-- NAV (desktop) — ringkas; pintasan fitur ada sebagai ikon di halaman /products -->
            <nav class="hidden lg:flex items-center gap-6 text-slate-600 text-sm font-medium shrink-0">
                <a href="/products" class="hover:text-blue-600 transition">{{ __('nav.products') }}</a>
                <a href="/explorer" class="hover:text-blue-600 transition">{{ __('nav.explorer') }}</a>
                @auth<a href="/orders" class="hover:text-blue-600 transition">{{ __('nav.orders') }}</a>@endauth
                @if(auth()->user()?->isSupervisor())<a href="/supervisor" class="hover:text-blue-600 transition">{{ __('nav.supervisor') }}</a>@endif
            </nav>

            @auth
                @php $cartCount = \App\Models\CartItem::where('user_id', auth()->id())->sum('quantity'); @endphp
                <!-- KERANJANG -->
                <a href="/cart" class="relative shrink-0 p-2 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-blue-600 transition" title="Keranjang">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3M17 13l2.3 2.3M9 20a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    <span id="cartBadge" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                </a>

                <!-- NOTIFIKASI -->
                @php $unreadNotif = \App\Models\AppNotification::where('user_id', auth()->id())->whereNull('read_at')->count(); @endphp
                <a href="/notifications" class="relative shrink-0 p-2 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-blue-600 transition" title="{{ __('nav.notifications') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span id="notifBadge" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center {{ $unreadNotif > 0 ? '' : 'hidden' }}">{{ $unreadNotif > 9 ? '9+' : $unreadNotif }}</span>
                </a>

                <!-- WALLET PILL (alamat + saldo TLKM) -->
                <div id="walletPill" class="hidden md:flex items-center gap-2 bg-slate-100 border border-slate-200 rounded-full pl-3 pr-1.5 py-1.5 text-xs shrink-0">
                    <span class="w-2 h-2 rounded-full bg-slate-400" id="walletDot"></span>
                    <span id="walletLabel" class="text-slate-600 font-mono">Wallet</span>
                    <span id="walletBalance" class="hidden items-center gap-1 bg-white border border-slate-200 rounded-full px-2 py-1 font-semibold text-blue-600">—</span>
                </div>

                <!-- PROFIL -->
                <a href="/profile" class="shrink-0 p-2 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-blue-600 transition" title="Profil">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </a>

                <!-- LOGOUT -->
                <form action="/logout" method="POST" class="shrink-0" id="logoutForm" onsubmit="return confirmLogout(event)">
                    @csrf
                    <button type="submit" class="bg-slate-100 hover:bg-red-50 border border-slate-200 hover:border-red-300 p-2 rounded-lg text-slate-600 hover:text-red-600 transition" title="{{ __('nav.logout') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            @endauth

            @guest
                <!-- LOGIN / DAFTAR -->
                <div class="flex items-center gap-2 shrink-0">
                    <a href="/login" class="text-sm font-medium px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:border-blue-300 hover:text-blue-600 transition">{{ __('nav.login') }}</a>
                    <a href="/register" class="text-sm font-semibold px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm">{{ __('nav.register') }}</a>
                </div>
            @endguest
        </div>

        @auth
        <!-- SUB-NAV (khusus mobile: pintasan Riwayat Order) -->
        <div class="border-t border-slate-100 bg-white lg:hidden">
            <div class="max-w-7xl mx-auto px-4 md:px-8 h-10 flex items-center gap-5 text-xs text-slate-500 overflow-x-auto">
                <a href="/products" class="hover:text-blue-600 whitespace-nowrap font-medium">{{ __('nav.products') }}</a>
                <a href="/orders" class="hover:text-blue-600 whitespace-nowrap">{{ __('nav.orders') }}</a>
                <a href="/explorer" class="hover:text-blue-600 whitespace-nowrap">{{ __('nav.explorer') }}</a>
                @if(auth()->user()->isSupervisor())<a href="/supervisor" class="hover:text-blue-600 whitespace-nowrap">{{ __('nav.supervisor') }}</a>@endif
            </div>
        </div>
        @endauth
    </header>

    <!-- ================= TICKER HARGA CRYPTO (kartu putih, selebar hero) ================= -->
    <div class="max-w-7xl mx-auto w-full px-4 md:px-8 pt-6">
        @include('partials.ticker', ['variant' => 'card'])
    </div>

    <!-- ================= MAIN ================= -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 md:px-8 py-6">
        @yield('content')
    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="border-t border-slate-200 bg-white mt-8">
        <div class="max-w-7xl mx-auto px-4 md:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <p>© {{ date('Y') }} E-Trace — {{ __('footer.rights') }}</p>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span>{{ config('chain.name', 'BNB Smart Chain Testnet') }}</span>
                </div>
                @include('partials.lang-switcher')
            </div>
        </div>
    </footer>

    <!-- ================= TOAST CONTAINER ================= -->
    <div id="toastRoot" class="fixed top-20 right-4 z-[100] flex flex-col gap-2 w-[calc(100%-2rem)] sm:w-80"></div>

    <!-- ================= MODAL ROOT ================= -->
    <div id="modalRoot" class="fixed inset-0 z-[90] hidden items-center justify-center p-4">
        <div id="modalBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm anim-fade"></div>
        <div id="modalCard" class="relative w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-2xl anim-slide overflow-hidden"></div>
    </div>

    <!-- =========================================================
         WEB3 CONFIG + FUNGSI (BNB Smart Chain Testnet, chainId 97) — dari config/chain.php
    ========================================================== -->
    <script>
    // CSRF token untuk request POST via fetch.
    const CSRF_TOKEN = "{{ csrf_token() }}";

    // Modal input PIN (embedded wallet). Return Promise<string|null>.
    function askPin(title = 'Masukkan PIN') {
        return new Promise((resolve) => {
            openModal(`
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-1">${title}</h3>
                    <p class="text-sm text-slate-500 mb-4">Masukkan PIN 6 angka untuk menandatangani transaksi.</p>
                    <input id="pinModalInput" type="password" inputmode="numeric" maxlength="6" autofocus autocomplete="one-time-code" data-lpignore="true" data-1p-ignore data-form-type="other" class="w-full text-center tracking-[0.4em] text-xl font-bold px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 focus:bg-white focus:border-blue-500 outline-none mb-4" placeholder="••••••">
                    <div class="flex gap-3">
                        <button id="pinCancel" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Batal</button>
                        <button id="pinOk" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Konfirmasi</button>
                    </div>
                </div>`);
            const done = (v) => { closeModal(); resolve(v); };
            document.getElementById('pinCancel').onclick = () => done(null);
            const ok = () => { const v = (document.getElementById('pinModalInput').value || '').trim(); if (/^\d{6}$/.test(v)) done(v); else showToast('PIN harus 6 angka', 'warn'); };
            document.getElementById('pinOk').onclick = ok;
            document.getElementById('pinModalInput').onkeydown = (e) => { if (e.key === 'Enter') ok(); };
        });
    }

    // Helper: POST JSON ke endpoint PIN, kembalikan tx_hash (lempar pesan bila gagal).
    async function pinTx(url, body) {
        // Accept JSON: tanpa ini Laravel membalas error sebagai halaman HTML, dan pesan
        // aslinya (PIN salah, gas habis, dst.) hilang jadi "Transaksi PIN gagal."
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.tx_hash) { throw new Error(data.message || 'Transaksi PIN gagal.'); }
        return data.tx_hash;
    }

    // Wallet yang TERIKAT ke akun login (lowercase) — untuk guard wallet-mismatch.
    // NOTE: dideklarasikan di ATAS pemakaian (mis. `if (NEEDS_PIN)`) agar tidak kena
    // temporal-dead-zone ReferenceError yang bisa mematikan seluruh script.
    const ACCOUNT_WALLET = @json(auth()->check() ? strtolower(auth()->user()->wallet_address ?? '') : null);
    const IS_EMBEDDED    = @json(auth()->check() ? (bool) auth()->user()->is_embedded : false);
    const NEEDS_PIN      = @json(auth()->check() ? empty(auth()->user()->pin_hash) : false);

    // Popup setup PIN untuk akun lama yang belum punya PIN.
    function promptPinSetup() {
        openModal(`
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Buat PIN Transaksi</h3>
                <p class="text-sm text-slate-500 mb-4">Akunmu belum punya PIN. Buat PIN 6 angka untuk login cepat &amp; konfirmasi pembayaran.</p>
                <div id="pinSetupErr" class="hidden bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm mb-3"></div>
                <input id="psPin" type="password" inputmode="numeric" maxlength="6" autocomplete="one-time-code" data-lpignore="true" data-1p-ignore data-form-type="other" placeholder="PIN 6 angka" class="w-full text-center tracking-[0.4em] text-lg font-bold px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 mb-2">
                <input id="psPin2" type="password" inputmode="numeric" maxlength="6" autocomplete="one-time-code" data-lpignore="true" data-1p-ignore data-form-type="other" placeholder="Ulangi PIN" class="w-full text-center tracking-[0.4em] text-lg font-bold px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 mb-2">
                <input id="psPass" type="password" placeholder="Password akun (konfirmasi)" class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 text-sm mb-4">
                <div class="flex gap-3">
                    <button onclick="try{sessionStorage.setItem('pinPromptDismissed','1')}catch(e){}; closeModal()" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Nanti saja</button>
                    <button id="psOk" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Set PIN</button>
                </div>
            </div>`);
        document.getElementById('psOk').onclick = async () => {
            const pin = (document.getElementById('psPin').value || '').trim();
            const pin2 = (document.getElementById('psPin2').value || '').trim();
            const pass = document.getElementById('psPass').value || '';
            const err = (m) => { const e = document.getElementById('pinSetupErr'); e.textContent = m; e.classList.remove('hidden'); };
            if (!/^\d{6}$/.test(pin)) return err('PIN harus 6 angka.');
            if (pin !== pin2) return err('Konfirmasi PIN tidak cocok.');
            if (!pass) return err('Masukkan password akun.');
            try {
                const res = await fetch('/pin/setup', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ pin, pin_confirmation: pin2, password: pass }) });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) return err(data.message || 'Gagal set PIN.');
                closeModal(); showToast('PIN berhasil dibuat.', 'success');
            } catch (e) { err('Terjadi kendala. Coba lagi.'); }
        };
    }
    if (NEEDS_PIN) {
        window.addEventListener('DOMContentLoaded', () => {
            let dismissed = false; try { dismissed = sessionStorage.getItem('pinPromptDismissed') === '1'; } catch (e) {}
            if (!dismissed) setTimeout(promptPinSetup, 900);
        });
    }

    // ---- KONFIGURASI KONTRAK (PaymentGateway v3) — dari config/chain.php (.env) ----
    const TLKM_ADDRESS            = @json(config('chain.tlkm'));
    const PAYMENT_GATEWAY_ADDRESS = @json(config('chain.gateway'));
    const DONATION_POOL_ADDRESS   = @json(config('chain.donation_pool'));
    const PAYLATER_ADDRESS        = @json(config('chain.paylater_address'));
    const PAYLATER_RATE_TLKM_PER_BNB = {{ (int) config('chain.paylater_rate_tlkm_per_bnb', 1000000) }};
    const PAYLATER_INTEREST_BPS      = {{ (int) config('chain.paylater_interest_bps', 300) }};
    const TOKEN_DECIMALS = 18;
    const PLATFORM_FEE_BPS = {{ (int) config('chain.platform_fee_bps', 100) }}; // 1% — dipotong dari penjual saat dana dilepas

    // Basis URL explorer (BscScan Testnet) untuk semua tautan tx/address.
    const EXPLORER_URL = @json(config('chain.explorer_url'));

    // ---- JARINGAN TARGET: BNB Smart Chain Testnet (chainId 97 / 0x61) — dari config ----
    const TARGET_NETWORK = {
        chainIdHex: "0x{{ dechex((int) config('chain.chain_id', 97)) }}",
        chainIdNum: {{ (int) config('chain.chain_id', 97) }}n,
        chainName: @json(config('chain.name', 'BNB Smart Chain Testnet')),
        rpcUrls: [@json(config('chain.rpc_url'))],
        nativeCurrency: { name: "tBNB", symbol: "tBNB", decimals: 18 },
        blockExplorerUrls: [@json(config('chain.explorer_url'))]
    };

    const ERC20_ABI = [
        "function balanceOf(address owner) view returns (uint256)",
        "function approve(address spender, uint256 value) returns (bool)",
        "function allowance(address owner, address spender) view returns (uint256)",
        "function transfer(address to, uint256 value) returns (bool)"
    ];
    // ABI v3: token dikunci di kontrak -> payCart TIDAK menerima argumen token.
    const PAYMENT_ABI = [
        "function payCart(address[] sellers, uint256[] amounts, string[] productIds, string orderId)",
        "function confirmItem(string orderId, uint256 index)",
        "function refundItem(string orderId, uint256 index)",
        "function disputeItem(string orderId, uint256 index)",
        "function arbiterRelease(string orderId, uint256 index)",
        "function arbiterRefund(string orderId, uint256 index)"
    ];
    // ABI kotak donasi (DonationPool berbasis campaign).
    const DONATION_ABI = [
        "function donate(bytes32 campaignId, uint256 amount)",
        "function disburse(bytes32 campaignId, address to)",
        "function balance(bytes32) view returns (uint256)"
    ];
    // ABI Paylater (kredit berjaminan on-chain, DEMO).
    const PAYLATER_ABI = [
        "function depositCollateral() payable",
        "function borrow(uint256 amount)",
        "function repay(uint256 amount)",
        "function withdrawCollateral(uint256 amount)",
        "function supply(uint8 term, uint256 amount)",
        "function withdrawSupply(uint8 term, uint256 shareAmount)",
        "function creditLimit(address u) view returns (uint256)",
        "function positionOf(address u) view returns (uint256 collateral, uint256 principal, uint256 dueAmount, uint256 dueDate, uint256 limit)",
        "function availableLiquidity() view returns (uint256)"
    ];

    // =========================================================
    //  UI HELPERS: toast, modal konfirmasi, modal progres tx
    // =========================================================
    const ICONS = {
        success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
        error:   '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
        info:    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warn:    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
    };
    const TOAST_STYLE = {
        success: 'text-green-600',
        error:   'text-red-600',
        info:    'text-blue-600',
        warn:    'text-amber-600',
    };

    function showToast(message, type = 'info', ms = 4500) {
        const root = document.getElementById('toastRoot');
        const el = document.createElement('div');
        el.className = `anim-toast flex items-start gap-3 bg-white border border-slate-200 ${TOAST_STYLE[type]||TOAST_STYLE.info} rounded-xl px-4 py-3 shadow-lg`;
        el.innerHTML = `<div class="shrink-0 mt-0.5">${ICONS[type]||ICONS.info}</div>
                        <div class="text-sm text-slate-700 leading-snug flex-1 break-words">${message}</div>`;
        root.appendChild(el);
        setTimeout(() => { el.style.transition='opacity .3s, transform .3s'; el.style.opacity='0'; el.style.transform='translateX(24px)'; setTimeout(()=>el.remove(), 300); }, ms);
    }

    const modalRoot = () => document.getElementById('modalRoot');
    const modalCard = () => document.getElementById('modalCard');
    function openModal(html) {
        modalCard().innerHTML = html;
        const r = modalRoot();
        r.classList.remove('hidden'); r.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        const r = modalRoot();
        r.classList.add('hidden'); r.classList.remove('flex');
        document.body.style.overflow = '';
        modalCard().innerHTML = '';
    }
    document.addEventListener('click', (e) => {
        if (e.target && e.target.id === 'modalBackdrop') { if (!window.__txBusy) closeModal(); }
    });

    // Modal konfirmasi -> Promise<boolean>
    function uiConfirm({ title = 'Konfirmasi', message = '', confirmText = 'Ya, lanjut', cancelText = 'Batal', danger = false } = {}) {
        return new Promise((resolve) => {
            openModal(`
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-2">${title}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">${message}</p>
                    <div class="flex gap-3 mt-6">
                        <button id="mOk" class="flex-1 py-2.5 rounded-xl ${danger?'bg-red-600 hover:bg-red-700':'bg-blue-600 hover:bg-blue-700'} text-white text-sm font-semibold transition">${confirmText}</button>
                        <button id="mCancel" class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">${cancelText}</button>
                    </div>
                </div>`);
            document.getElementById('mCancel').onclick = () => { closeModal(); resolve(false); };
            document.getElementById('mOk').onclick     = () => { closeModal(); resolve(true); };
        });
    }

    // Escape teks untuk modal: uiConfirm menyisipkan title/message sebagai HTML, jadi teks
    // yang berasal dari pengguna (mis. nama produk) wajib di-escape agar tak jadi celah XSS.
    function uiEsc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Pengganti onsubmit="return confirm(...)": tahan submit, tampilkan modal aplikasi,
    // lalu kirim form bila disetujui. title/message diperlakukan sebagai teks biasa.
    // prototype.submit dipakai agar tak memicu onsubmit lagi (dan tetap jalan meski form
    // punya input bernama "submit").
    function confirmSubmit(event, opts = {}) {
        event.preventDefault();
        const form = event.currentTarget || event.target;
        uiConfirm({ ...opts, title: uiEsc(opts.title ?? 'Konfirmasi'), message: uiEsc(opts.message ?? '') })
            .then(ok => { if (ok) HTMLFormElement.prototype.submit.call(form); });
        return false;
    }

    // Konfirmasi logout via modal (bukan submit langsung).
    let __logoutOK = false;
    async function confirmLogout(e) {
        if (__logoutOK) return true;           // sudah dikonfirmasi -> lanjutkan submit
        e.preventDefault();
        const ok = await uiConfirm({
            title: @json(__('auth.logout_confirm_title')),
            message: @json(__('auth.logout_confirm_body')),
            confirmText: @json(__('nav.logout')),
            cancelText: @json(__('common.cancel')),
            danger: true,
        });
        if (ok) { __logoutOK = true; document.getElementById('logoutForm').submit(); }
        return false;
    }

    // Modal info sederhana -> Promise
    function uiAlert({ title = 'Info', message = '', type = 'info' } = {}) {
        const color = type==='error'?'text-red-600':type==='success'?'text-green-600':type==='warn'?'text-amber-600':'text-blue-600';
        return new Promise((resolve) => {
            openModal(`
                <div class="p-6">
                    <div class="flex items-center gap-2 ${color} mb-2">${ICONS[type]||ICONS.info}<h3 class="text-lg font-bold text-slate-900">${title}</h3></div>
                    <div class="text-sm text-slate-500 leading-relaxed break-words">${message}</div>
                    <button id="mOk2" class="mt-6 w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">Oke</button>
                </div>`);
            document.getElementById('mOk2').onclick = () => { closeModal(); resolve(true); };
        });
    }

    // Modal INPUT (pengganti prompt() bawaan) -> Promise<string|null>
    function uiPrompt({ title = 'Masukkan nilai', label = '', placeholder = '', value = '', type = 'text', min = null, step = null, confirmText = 'Lanjut', cancelText = 'Batal' } = {}) {
        const attrs = [
            `type="${type}"`, `value="${value}"`, `placeholder="${placeholder}"`,
            min !== null ? `min="${min}"` : '', step !== null ? `step="${step}"` : '',
        ].join(' ');
        return new Promise((resolve) => {
            openModal(`
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-1">${title}</h3>
                    ${label ? `<p class="text-sm text-slate-500 mb-3">${label}</p>` : '<div class="mb-3"></div>'}
                    <input id="mPromptInput" ${attrs} autocomplete="off" data-lpignore="true" data-1p-ignore data-form-type="other" name="q-${Date.now()}" class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 text-sm mb-4">
                    <div class="flex gap-3">
                        <button id="mPromptOk" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">${confirmText}</button>
                        <button id="mPromptCancel" class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">${cancelText}</button>
                    </div>
                </div>`);
            const inp = document.getElementById('mPromptInput');
            setTimeout(() => { inp.focus(); inp.select(); }, 50);
            const done = (v) => { closeModal(); resolve(v); };
            document.getElementById('mPromptOk').onclick = () => done(inp.value);
            document.getElementById('mPromptCancel').onclick = () => done(null);
            inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); done(inp.value); } });
        });
    }

    // ---- Modal PROGRES TRANSAKSI (multi-langkah) ----
    const txProgress = {
        steps: [],
        open(title, steps) {
            window.__txBusy = true;
            this.steps = steps;
            const rows = steps.map((s, i) => `
                <div id="step-${i}" class="flex items-center gap-3 py-2">
                    <div id="stepIcon-${i}" class="w-6 h-6 rounded-full border border-slate-300 flex items-center justify-center text-xs text-slate-400 shrink-0">${i+1}</div>
                    <span id="stepLabel-${i}" class="text-sm text-slate-500">${s}</span>
                </div>`).join('');
            openModal(`
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-1">${title}</h3>
                    <p class="text-xs text-slate-400 mb-4">Konfirmasi setiap langkah di dompet MetaMask kamu.</p>
                    <div class="divide-y divide-slate-100">${rows}</div>
                    <div id="txHint" class="mt-4 text-xs text-slate-400"></div>
                </div>`);
        },
        active(i, hint) {
            const icon = document.getElementById('stepIcon-'+i);
            const label = document.getElementById('stepLabel-'+i);
            if (icon) { icon.className = 'w-6 h-6 rounded-full border-2 border-blue-500 border-t-transparent spinner shrink-0'; icon.innerHTML=''; }
            if (label) label.className = 'text-sm text-slate-900 font-medium';
            if (hint) document.getElementById('txHint').textContent = hint;
        },
        done(i) {
            const icon = document.getElementById('stepIcon-'+i);
            const label = document.getElementById('stepLabel-'+i);
            if (icon) { icon.className='w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0'; icon.innerHTML=ICONS.success.replace('w-5 h-5','w-3.5 h-3.5'); }
            if (label) label.className='text-sm text-slate-400 line-through decoration-green-400';
        },
        fail(i) {
            const icon = document.getElementById('stepIcon-'+i);
            if (icon) { icon.className='w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0'; icon.innerHTML=ICONS.error.replace('w-5 h-5','w-3.5 h-3.5'); }
        },
        close() { window.__txBusy = false; closeModal(); }
    };

    // =========================================================
    //  WEB3
    // =========================================================
    async function connectWallet() {
        if (!window.ethereum) { showToast('MetaMask belum terpasang. Pasang ekstensinya dulu untuk melanjutkan.', 'warn'); throw new Error('MetaMask tidak ditemukan'); }
        await window.ethereum.request({ method: "eth_requestAccounts" });
        const provider = new ethers.BrowserProvider(window.ethereum);
        const signer = await provider.getSigner();
        const address = await signer.getAddress();

        // GUARD: wallet MetaMask aktif WAJIB sama dengan wallet akun yang login.
        // Cegah user login akun A membayar/konfirmasi memakai wallet B.
        if (ACCOUNT_WALLET && address.toLowerCase() !== ACCOUNT_WALLET) {
            showToast('Wallet MetaMask aktif tidak cocok dengan wallet akunmu.', 'error');
            refreshWalletPill();
            throw new Error('WALLET_MISMATCH');
        }
        return { provider, signer, address };
    }

    async function checkNetwork() {
        if (!window.ethereum) return;
        const provider = new ethers.BrowserProvider(window.ethereum);
        const network = await provider.getNetwork();
        if (network.chainId !== TARGET_NETWORK.chainIdNum) {
            try {
                await window.ethereum.request({ method: "wallet_switchEthereumChain", params: [{ chainId: TARGET_NETWORK.chainIdHex }] });
            } catch (err) {
                if (err.code === 4902) {
                    await window.ethereum.request({ method: "wallet_addEthereumChain", params: [{
                        chainId: TARGET_NETWORK.chainIdHex, chainName: TARGET_NETWORK.chainName,
                        rpcUrls: TARGET_NETWORK.rpcUrls, nativeCurrency: TARGET_NETWORK.nativeCurrency,
                        blockExplorerUrls: TARGET_NETWORK.blockExplorerUrls }] });
                } else { throw err; }
            }
        }
    }

    async function getTokenBalance() {
        const { signer, address } = await connectWallet();
        const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
        const bal = await token.balanceOf(address);
        return ethers.formatUnits(bal, TOKEN_DECIMALS);
    }

    async function approveToken(amountToken) {
        const { signer } = await connectWallet();
        const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
        const amount = ethers.parseUnits(amountToken.toString(), TOKEN_DECIMALS);
        const tx = await token.approve(PAYMENT_GATEWAY_ADDRESS, amount);
        await tx.wait();
        return tx.hash;
    }

    // ===== PAYLATER (kredit berjaminan on-chain, DEMO) — MetaMask & embedded (PIN) =====
    // Tiap fungsi mengembalikan tx hash, atau null bila user membatalkan PIN.
    function _paylaterGuard() { if (!PAYLATER_ADDRESS) throw new Error('Paylater belum dikonfigurasi (PAYLATER_ADDRESS kosong).'); }

    async function depositCollateralPaylater(amountBnb) {
        _paylaterGuard();
        if (IS_EMBEDDED) { const pin = await askPin('Deposit Agunan'); if (!pin) return null; return await pinTx('/pin/paylater-deposit', { pin, amount: amountBnb }); }
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYLATER_ADDRESS, PAYLATER_ABI, signer);
        const tx = await c.depositCollateral({ value: ethers.parseEther(amountBnb.toString()) });
        return (await tx.wait()).hash;
    }

    async function borrowPaylater(amountTlkm) {
        _paylaterGuard();
        if (IS_EMBEDDED) { const pin = await askPin('Pinjam TLKM'); if (!pin) return null; return await pinTx('/pin/paylater-borrow', { pin, amount: amountTlkm }); }
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYLATER_ADDRESS, PAYLATER_ABI, signer);
        const tx = await c.borrow(ethers.parseUnits(amountTlkm.toString(), TOKEN_DECIMALS));
        return (await tx.wait()).hash;
    }

    async function repayPaylater(amountTlkm) {
        _paylaterGuard();
        if (IS_EMBEDDED) { const pin = await askPin('Lunasi Paylater'); if (!pin) return null; return await pinTx('/pin/paylater-repay', { pin, amount: amountTlkm }); }
        const { signer } = await connectWallet();
        const amount = ethers.parseUnits(amountTlkm.toString(), TOKEN_DECIMALS);
        const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
        const allow = await token.allowance(await signer.getAddress(), PAYLATER_ADDRESS);
        if (allow < amount) { const atx = await token.approve(PAYLATER_ADDRESS, amount); await atx.wait(); }
        const c = new ethers.Contract(PAYLATER_ADDRESS, PAYLATER_ABI, signer);
        const tx = await c.repay(amount);
        return (await tx.wait()).hash;
    }

    async function withdrawCollateralPaylater(amountBnb) {
        _paylaterGuard();
        if (IS_EMBEDDED) { const pin = await askPin('Tarik Agunan'); if (!pin) return null; return await pinTx('/pin/paylater-withdraw', { pin, amount: amountBnb }); }
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYLATER_ADDRESS, PAYLATER_ABI, signer);
        const tx = await c.withdrawCollateral(ethers.parseEther(amountBnb.toString()));
        return (await tx.wait()).hash;
    }

    // ===== Sisi PENYUPLAI (lender/earn) — dengan jangka (term 0/1/2) =====
    async function supplyPaylater(term, amountTlkm) {
        _paylaterGuard();
        if (IS_EMBEDDED) { const pin = await askPin('Danai Pool'); if (!pin) return null; return await pinTx('/pin/paylater-supply', { pin, term, amount: amountTlkm }); }
        const { signer } = await connectWallet();
        const amount = ethers.parseUnits(amountTlkm.toString(), TOKEN_DECIMALS);
        const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
        const allow = await token.allowance(await signer.getAddress(), PAYLATER_ADDRESS);
        if (allow < amount) { const atx = await token.approve(PAYLATER_ADDRESS, amount); await atx.wait(); }
        const c = new ethers.Contract(PAYLATER_ADDRESS, PAYLATER_ABI, signer);
        const tx = await c.supply(term, amount);
        return (await tx.wait()).hash;
    }

    // sharesRaw = jumlah share EKSAK (integer wei-scale) dari backend.
    async function withdrawSupplyPaylater(term, sharesRaw) {
        _paylaterGuard();
        if (IS_EMBEDDED) { const pin = await askPin('Tarik Dana'); if (!pin) return null; return await pinTx('/pin/paylater-withdraw-supply', { pin, term, shares: sharesRaw.toString() }); }
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYLATER_ADDRESS, PAYLATER_ABI, signer);
        const tx = await c.withdrawSupply(term, sharesRaw.toString());
        return (await tx.wait()).hash;
    }

    // Catat aksi paylater ke server setelah tx sukses (best-effort).
    async function recordPaylater(action, amount, txHash) {
        try { await fetch('/paylater/record', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ action, amount, tx_hash: txHash }) }); } catch (_) {}
    }

    // ===== CHECKOUT MULTI-PENJUAL v3 (escrow terpisah per item) =====
    // Bayar SEKALI untuk banyak item; token TLKM dikunci di kontrak (tanpa arg token).
    async function payCart({ sellers, amounts, productIds, orderId }) {
        const { signer } = await connectWallet();
        const gateway = new ethers.Contract(PAYMENT_GATEWAY_ADDRESS, PAYMENT_ABI, signer);
        const amountsWei = amounts.map(a => ethers.parseUnits(a.toString(), TOKEN_DECIMALS));
        const tx = await gateway.payCart(sellers, amountsWei, productIds, orderId);
        const receipt = await tx.wait();
        return { txHash: receipt.hash };
    }

    // Konfirmasi SATU item -> dana item itu lepas ke penjualnya (item lain tak terpengaruh).
    async function confirmItem(orderId, index) {
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYMENT_GATEWAY_ADDRESS, PAYMENT_ABI, signer);
        const tx = await c.confirmItem(orderId, index);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    // Refund SATU item -> kembali ke pembeli (setelah timeout).
    async function refundItem(orderId, index) {
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYMENT_GATEWAY_ADDRESS, PAYMENT_ABI, signer);
        const tx = await c.refundItem(orderId, index);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    // Pembeli mengajukan SENGKETA atas 1 item (dana tetap ditahan sampai pengawas memutus).
    async function disputeItem(orderId, index) {
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYMENT_GATEWAY_ADDRESS, PAYMENT_ABI, signer);
        const tx = await c.disputeItem(orderId, index);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    // PENGAWAS (arbiter): lepas dana item ke penjual.
    async function arbiterRelease(orderId, index) {
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYMENT_GATEWAY_ADDRESS, PAYMENT_ABI, signer);
        const tx = await c.arbiterRelease(orderId, index);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    // PENGAWAS (arbiter): refund item ke pembeli.
    async function arbiterRefund(orderId, index) {
        const { signer } = await connectWallet();
        const c = new ethers.Contract(PAYMENT_GATEWAY_ADDRESS, PAYMENT_ABI, signer);
        const tx = await c.arbiterRefund(orderId, index);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    // ===== DONASI (per-campaign) =====
    // Donatur: approve TLKM ke pool -> donate(campaignId, amount). Nominal bebas.
    async function donateCampaign(campaignId, amountToken) {
        const { signer } = await connectWallet();
        const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
        const pool  = new ethers.Contract(DONATION_POOL_ADDRESS, DONATION_ABI, signer);
        const amount = ethers.parseUnits(amountToken.toString(), TOKEN_DECIMALS);
        const ap = await token.approve(DONATION_POOL_ADDRESS, amount);
        await ap.wait();
        const tx = await pool.donate(campaignId, amount);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    // PENGAWAS (validator): salurkan SELURUH saldo campaign ke wallet penerima.
    async function disburseCampaign(campaignId, toAddress) {
        const { signer } = await connectWallet();
        const pool = new ethers.Contract(DONATION_POOL_ADDRESS, DONATION_ABI, signer);
        const tx = await pool.disburse(campaignId, toAddress);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    // ===== KIRIM TLKM P2P (ERC20 transfer langsung) =====
    async function sendTLKM(toAddress, amountToken) {
        const { signer } = await connectWallet();
        const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
        const amount = ethers.parseUnits(amountToken.toString(), TOKEN_DECIMALS);
        const tx = await token.transfer(toAddress, amount);
        const receipt = await tx.wait();
        return receipt.hash;
    }

    function niceError(e) {
        if (!e) return 'Transaksi dibatalkan.';
        if (e.message === 'WALLET_MISMATCH') return 'Wallet MetaMask aktif tidak cocok dengan wallet akunmu. Ganti dulu ke wallet yang terdaftar di akun ini.';
        if (e.code === 'ACTION_REJECTED' || e.code === 4001) return 'Kamu membatalkan transaksi di MetaMask.';
        if (e.code === 'INSUFFICIENT_FUNDS') return 'Saldo tBNB tidak cukup untuk biaya gas. Isi tBNB testnet (faucet) dulu, lalu coba lagi.';
        // Gali pesan revert sebenarnya dari berbagai lokasi yang dipakai ethers v6.
        var real = e.reason
            || (e.info && e.info.error && e.info.error.message)
            || (e.error && e.error.message)
            || (e.data && e.data.message)
            || e.shortMessage
            || e.message;
        return real || 'Transaksi gagal. Coba lagi sebentar lagi.';
    }

    // ===== Jaring pengaman order: simpan draft di localStorage, kirim & retry =====
    // Kalau pay on-chain sukses tapi POST /order/store gagal (RPC lag/browser tutup),
    // draft tetap tersimpan dan dicoba lagi otomatis saat halaman dibuka -> order tak hilang.
    async function submitOrder(payload) {
        const res = await fetch('/order/store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(payload)
        });
        let data = {};
        try { data = await res.json(); } catch (_) {}
        if ((res.ok && data.success) || res.status === 409) {
            localStorage.removeItem('pendingOrder:' + payload.order_id); // sukses / sudah tercatat
            return data;
        }
        throw new Error(data.message || ('Gagal menyimpan order (' + res.status + ')'));
    }

    async function retryPendingOrders() {
        const keys = [];
        for (let i = 0; i < localStorage.length; i++) {
            const k = localStorage.key(i);
            if (k && k.startsWith('pendingOrder:')) keys.push(k);
        }
        for (const k of keys) {
            try {
                const p = JSON.parse(localStorage.getItem(k));
                if (p && p.tx_hash) await submitOrder(p);
            } catch (_) { /* biarkan, coba lagi lain waktu */ }
        }
    }

    // Update badge jumlah item keranjang di header (dipakai setelah add-to-cart).
    function updateCartBadge(count) {
        const b = document.getElementById('cartBadge');
        if (!b) return;
        b.textContent = count;
        b.classList.toggle('hidden', !(count > 0));
    }

    // Tambah produk ke keranjang (dipakai di katalog & detail; user harus login).
    async function addToCart(productId, btn, opts = {}) {
        try {
            if (btn) btn.disabled = true;
            const res = await fetch('/cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: opts.quantity || 1 })
            });
            if (!res.ok) throw new Error('gagal');
            const data = await res.json();
            if (data.success) {
                updateCartBadge(data.count);
                if (opts.redirect) { window.location.href = opts.redirect; return; }
                showToast('Ditambahkan ke keranjang 🛒', 'success');
            } else {
                showToast(data.message || 'Gagal menambah ke keranjang', 'error');
            }
        } catch (e) {
            showToast('Gagal menambah ke keranjang', 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // Provider read-only untuk baca saldo tanpa popup MetaMask. SELALU lewat RPC jaringan
    // target, bukan window.ethereum: dompet bawaan browser (Brave Wallet, MetaMask) bisa
    // sedang di jaringan lain, lalu balanceOf gagal dan saldo tidak tampil sama sekali.
    const READ_RPCS = [TARGET_NETWORK.rpcUrls[0], 'https://bsc-testnet-rpc.publicnode.com'];
    const _readProviders = [];
    function readProvider(i = 0) {
        if (!_readProviders[i]) {
            const net = ethers.Network.from(Number(TARGET_NETWORK.chainIdNum));
            _readProviders[i] = new ethers.JsonRpcProvider(READ_RPCS[i], net, { staticNetwork: net });
        }
        return _readProviders[i];
    }
    async function fetchTlkmBalance(address) {
        let lastErr;
        for (let i = 0; i < READ_RPCS.length; i++) { // RPC cadangan bila yang utama gagal
            try {
                const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, readProvider(i));
                return ethers.formatUnits(await token.balanceOf(address), TOKEN_DECIMALS);
            } catch (e) { lastErr = e; }
        }
        throw lastErr;
    }

    // Tampilkan saldo TLKM di pill untuk sebuah alamat (best-effort).
    async function showPillBalance(address) {
        const balEl = document.getElementById('walletBalance');
        if (!balEl) return;
        try {
            const raw = await fetchTlkmBalance(address);
            balEl.textContent = Number(raw).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' TLKM';
            balEl.classList.remove('hidden');
            balEl.classList.add('inline-flex');
        } catch (_) { balEl.classList.add('hidden'); }
    }

    async function refreshWalletPill() {
        const dot = document.getElementById('walletDot');
        const label = document.getElementById('walletLabel');
        const balEl = document.getElementById('walletBalance');
        if (!dot || !label) return;   // guest: elemen wallet pill tidak dirender

        // EMBEDDED WALLET: dompet bawaan akun. Wallet aktif = wallet akun,
        // jadi TIDAK PERNAH "tidak cocok" — cek kecocokan hanya relevan untuk MetaMask.
        if (IS_EMBEDDED) {
            if (!ACCOUNT_WALLET) return;
            dot.className = 'w-2 h-2 rounded-full bg-green-500';
            label.textContent = ACCOUNT_WALLET.slice(0,6) + '…' + ACCOUNT_WALLET.slice(-4);
            label.title = 'Dompet bawaan akun (embedded)';
            showPillBalance(ACCOUNT_WALLET);
            return;
        }

        // METAMASK: bandingkan wallet aktif dengan wallet terverifikasi akun.
        if (!window.ethereum) return;
        try {
            const accs = await window.ethereum.request({ method: 'eth_accounts' });
            if (accs && accs.length) {
                const a = accs[0];
                if (ACCOUNT_WALLET && a.toLowerCase() !== ACCOUNT_WALLET) {
                    dot.className = 'w-2 h-2 rounded-full bg-red-500';
                    label.textContent = 'Wallet tidak cocok';
                    label.title = a;
                    if (balEl) balEl.classList.add('hidden');
                } else {
                    dot.className = 'w-2 h-2 rounded-full bg-green-500';
                    label.textContent = a.slice(0,6) + '…' + a.slice(-4);
                    label.title = '';
                    showPillBalance(a);
                }
            }
        } catch (_) {}
    }

    checkNetwork();
    refreshWalletPill();
    if (ACCOUNT_WALLET) retryPendingOrders();   // tambal order yang gagal tersimpan
    if (window.ethereum) {
        window.ethereum.on?.('accountsChanged', refreshWalletPill);
    }

    // ===== Polling badge notifikasi (tiap 45 dtk) =====
    @auth
    async function pollNotif() {
        try {
            const res = await fetch('/notifications/unread-count', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const d = await res.json();
            const badge = document.getElementById('notifBadge');
            if (!badge) return;
            if (d.count > 0) { badge.textContent = d.count > 9 ? '9+' : d.count; badge.classList.remove('hidden'); }
            else { badge.classList.add('hidden'); }
        } catch (_) {}
    }
    setInterval(pollNotif, 45000);
    @endauth
    </script>

    <!-- ================= CHATBOT WIDGET (mengambang) ================= -->
    <div id="chatFab" onclick="toggleChat()" class="fixed bottom-5 right-5 z-50 w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center cursor-pointer transition" title="Tanya Asisten E-Trace">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-11.6 7.1L4 20l1-4.3A8 8 0 1121 12z"/></svg>
    </div>
    <div id="chatPanel" class="hidden fixed bottom-5 right-5 z-50 w-[92vw] max-w-sm h-[70vh] max-h-[560px] bg-white border border-slate-200 rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        <div class="bg-blue-600 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-300"></span>
                <span class="font-semibold text-sm">EVA</span>
            </div>
            <button onclick="toggleChat()" class="text-white/80 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div id="chatBody" class="flex-1 overflow-y-auto p-3 space-y-3 bg-slate-50 text-sm">
            {{-- Sapaan pembuka. Tiap contoh pertanyaan adalah tombol yang langsung mengirimnya. --}}
            <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-sm px-3.5 py-3 max-w-[92%] text-slate-700 leading-relaxed">
                <p class="font-semibold text-slate-900">Halo! 👋 Aku EVA, asisten belanjamu di E-Trace.</p>
                <p class="mt-1">Aku siap bantu kamu menemukan produk, memahami cara belanja, atau menjawab pertanyaan seputar transaksi dan transparansi.</p>
                <p class="mt-2.5 text-slate-600">Coba tanyakan apa saja, misalnya:</p>
                <ul class="mt-1.5 space-y-1.5">
                    @foreach([
                        ['🛍️', 'Cari sepatu'],
                        ['💳', 'Gimana cara kerja escrow?'],
                        ['🔐', 'Apa itu PIN?'],
                        ['🎁', 'Aku mau donasi'],
                        ['🔎', 'Apa itu Explorer?'],
                        ['📊', 'Berapa pengeluaran KPK bulan ini?'],
                    ] as [$ico, $q])
                        <li>
                            <button type="button" onclick="askEva(this.dataset.q)" data-q="{{ $q }}"
                                    class="w-full text-left flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-slate-700 hover:text-blue-800 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                <span aria-hidden="true">{{ $ico }}</span><span>“{{ $q }}”</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-2.5 font-semibold text-slate-900">Yuk, mulai belanja dengan lebih mudah dan transparan bersama E-Trace.</p>
            </div>
        </div>
        <div class="p-2.5 border-t border-slate-100 flex items-center gap-2">
            <input id="chatInput" onkeydown="if(event.key==='Enter')sendChat()" placeholder="Tulis pesan…" class="flex-1 px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 focus:bg-white focus:border-blue-500 outline-none text-sm">
            <button onclick="sendChat()" class="w-9 h-9 rounded-xl bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg></button>
        </div>
    </div>
    <script>
        const chatHistory = [];
        // Panel EVA dan panel chat teman menempati pojok yang sama, jadi hanya satu yang
        // boleh terbuka: membuka EVA menutup chat teman dan menyembunyikan tombolnya.
        function toggleChat() {
            const panel = document.getElementById('chatPanel');
            const open = panel.classList.contains('hidden');
            if (open) {
                const friend = document.getElementById('chatPanelWrap');
                if (friend && !friend.classList.contains('hidden') && window.chatToggle) window.chatToggle();
            }
            panel.classList.toggle('hidden', !open);
            document.getElementById('chatFab').classList.toggle('hidden', open);
            const launcher = document.getElementById('chatLauncher');
            if (launcher) launcher.classList.toggle('hidden', open);
            const i = document.getElementById('chatInput'); if (i && open) i.focus();
        }
        function askEva(q) {
            const i = document.getElementById('chatInput');
            if (!i) return;
            i.value = q;
            sendChat();
        }
        function chatBubble(text, who) {
            const body = document.getElementById('chatBody');
            const mine = who === 'user';
            const div = document.createElement('div');
            div.className = 'max-w-[85%] px-3 py-2 rounded-2xl ' + (mine ? 'ml-auto bg-blue-600 text-white rounded-tr-sm' : 'bg-white border border-slate-200 text-slate-700 rounded-tl-sm');
            div.innerHTML = text;
            body.appendChild(div); body.scrollTop = body.scrollHeight;
            return div;
        }
        async function sendChat() {
            const input = document.getElementById('chatInput');
            const msg = (input.value || '').trim();
            if (!msg) return;
            input.value = '';
            chatBubble(msg.replace(/</g,'&lt;'), 'user');
            chatHistory.push({ role: 'user', content: msg });
            const typing = chatBubble('<span class="text-slate-400">mengetik…</span>', 'bot');
            try {
                const res = await fetch('/chatbot', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ message: msg, history: chatHistory.slice(-8) })
                });
                const data = await res.json();
                // Tebalkan **teks** jadi <b> lalu escape sisanya secukupnya.
                let html = (data.reply || 'Maaf, terjadi kendala.')
                    .replace(/</g,'&lt;')
                    .replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')
                    .replace(/\n/g,'<br>');
                if (Array.isArray(data.products) && data.products.length) {
                    html += '<div class="mt-2 space-y-1">' + data.products.map(p =>
                        `<a href="${p.url}" class="block text-xs bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 hover:border-blue-400"><b>${(p.name||'').replace(/</g,'&lt;')}</b> · ${p.price} TLKM ↗</a>`
                    ).join('') + '</div>';
                }
                // Tombol menuju halaman Explorer wallet (fitur transparansi).
                if (data.explorer && data.explorer.url) {
                    const nm = (data.explorer.name || 'wallet').replace(/</g,'&lt;');
                    const vb = data.explorer.verified ? ' <span class="text-green-600">✓</span>' : '';
                    html += `<a href="${data.explorer.url}" class="mt-2 flex items-center justify-center gap-1.5 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-3 py-2 transition">`
                        + `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>`
                        + `Lihat ${nm}${vb} di Explorer ↗</a>`;
                }
                typing.innerHTML = html;
                chatHistory.push({ role: 'assistant', content: data.reply || '' });
            } catch (e) {
                typing.innerHTML = 'Maaf, gagal menghubungi asisten.';
            }
            document.getElementById('chatBody').scrollTop = 1e9;
        }
    </script>

    @auth
        @include('partials.chat-widget')
    @endauth

    @yield('scripts')
</body>
</html>

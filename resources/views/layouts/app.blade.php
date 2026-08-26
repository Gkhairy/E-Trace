<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>

<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex flex-col">

    <!-- ================= HEADER ================= -->
    <header class="sticky top-0 z-40 w-full bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 md:px-8 h-16 flex items-center gap-4">

            <!-- LOGO -->
            <a href="/products" class="flex items-center gap-2 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-extrabold text-sm text-white">E</div>
                <span class="text-lg font-bold tracking-tight text-slate-900 hidden sm:inline">E-<span class="text-blue-600">Trace</span></span>
            </a>

            <!-- SEARCH (lebar, tengah) -->
            <div class="relative flex-1 max-w-2xl">
                <svg class="w-5 h-5 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Cari produk di E-Trace…"
                    class="w-full bg-slate-100 focus:bg-white text-sm rounded-xl pl-10 pr-4 py-2.5 outline-none text-slate-800 placeholder-slate-400 border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
            </div>

            <!-- NAV (desktop) — ringkas; pintasan fitur ada sebagai ikon di halaman /products -->
            <nav class="hidden lg:flex items-center gap-6 text-slate-600 text-sm font-medium shrink-0">
                <a href="/products" class="hover:text-blue-600 transition">Produk</a>
                <a href="/explorer" class="hover:text-blue-600 transition">Explorer</a>
            </nav>

            @auth
                @php $cartCount = \App\Models\CartItem::where('user_id', auth()->id())->sum('quantity'); @endphp
                <!-- KERANJANG -->
                <a href="/cart" class="relative shrink-0 p-2 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-blue-600 transition" title="Keranjang">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3M17 13l2.3 2.3M9 20a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    <span id="cartBadge" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
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
                <form action="/logout" method="POST" class="shrink-0">
                    @csrf
                    <button class="bg-slate-100 hover:bg-red-50 border border-slate-200 hover:border-red-300 p-2 rounded-lg text-slate-600 hover:text-red-600 transition" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            @endauth

            @guest
                <!-- LOGIN / DAFTAR -->
                <div class="flex items-center gap-2 shrink-0">
                    <a href="/login" class="text-sm font-medium px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:border-blue-300 hover:text-blue-600 transition">Login</a>
                    <a href="/register" class="text-sm font-semibold px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm">Daftar</a>
                </div>
            @endguest
        </div>

        <!-- SUB-NAV (mobile + kategori ringan, ala Amazon) -->
        <div class="border-t border-slate-100 bg-white">
            <div class="max-w-7xl mx-auto px-4 md:px-8 h-10 flex items-center gap-5 text-xs text-slate-500 overflow-x-auto">
                <a href="/products" class="hover:text-blue-600 whitespace-nowrap font-medium">Semua Produk</a>
                @auth
                    <a href="/orders" class="hover:text-blue-600 whitespace-nowrap lg:hidden">Riwayat Order</a>
                @endauth
                <span class="text-slate-300 hidden sm:inline">|</span>
                <span class="whitespace-nowrap hidden sm:inline">Bayar pakai <b class="text-slate-700">TLKM</b></span>
                <span class="text-slate-300 hidden sm:inline">|</span>
                <span class="whitespace-nowrap hidden sm:inline">Transaksi tercatat blockchain</span>
            </div>
        </div>
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
            <p>© {{ date('Y') }} E-Trace — E-commerce berbasis blockchain.</p>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <span>Jaringan: Ethereum Sepolia (Testnet)</span>
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
         WEB3 CONFIG + FUNGSI (Ethereum Sepolia, chainId 11155111)
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
                    <input id="pinModalInput" type="password" inputmode="numeric" maxlength="6" autofocus class="w-full text-center tracking-[0.4em] text-xl font-bold px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 focus:bg-white focus:border-blue-500 outline-none mb-4" placeholder="••••••">
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
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify(body) });
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
                <input id="psPin" type="password" inputmode="numeric" maxlength="6" placeholder="PIN 6 angka" class="w-full text-center tracking-[0.4em] text-lg font-bold px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 mb-2">
                <input id="psPin2" type="password" inputmode="numeric" maxlength="6" placeholder="Ulangi PIN" class="w-full text-center tracking-[0.4em] text-lg font-bold px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 mb-2">
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

    // ---- KONFIGURASI KONTRAK (PaymentGateway v3, Sepolia) ----
    const TLKM_ADDRESS            = "0xFbaa7F02bE3f151920D036cA4Eed2Fb1Ca3e0aEB";
    const PAYMENT_GATEWAY_ADDRESS = "0x0D6F824F6734B6369EdeBbfD6db37f965fa55f26";
    const DONATION_POOL_ADDRESS   = @json(config('chain.donation_pool'));
    const TOKEN_DECIMALS = 18;
    const PLATFORM_FEE_BPS = 100; // 1% — dipotong dari penjual saat dana dilepas

    const TARGET_NETWORK = {
        chainIdHex: "0xaa36a7",
        chainIdNum: 11155111n,
        chainName: "Sepolia",
        rpcUrls: ["https://ethereum-sepolia-rpc.publicnode.com"],
        nativeCurrency: { name: "Ethereum", symbol: "ETH", decimals: 18 },
        blockExplorerUrls: ["https://sepolia.etherscan.io"]
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
                        <button id="mCancel" class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">${cancelText}</button>
                        <button id="mOk" class="flex-1 py-2.5 rounded-xl ${danger?'bg-red-600 hover:bg-red-700':'bg-blue-600 hover:bg-blue-700'} text-white text-sm font-semibold transition">${confirmText}</button>
                    </div>
                </div>`);
            document.getElementById('mCancel').onclick = () => { closeModal(); resolve(false); };
            document.getElementById('mOk').onclick     = () => { closeModal(); resolve(true); };
        });
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
        if (e.code === 'INSUFFICIENT_FUNDS') return 'Saldo ETH Sepolia tidak cukup untuk biaya gas. Isi ETH testnet dulu, lalu coba lagi.';
        return e.reason || e.shortMessage || 'Transaksi gagal. Coba lagi sebentar lagi.';
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

    // Provider read-only untuk baca saldo tanpa popup MetaMask.
    // Embedded wallet (tanpa MetaMask) tetap bisa baca lewat RPC publik.
    function readProvider() {
        if (window.ethereum) return new ethers.BrowserProvider(window.ethereum);
        return new ethers.JsonRpcProvider(TARGET_NETWORK.rpcUrls[0]);
    }
    async function fetchTlkmBalance(address) {
        const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, readProvider());
        const bal = await token.balanceOf(address);
        return ethers.formatUnits(bal, TOKEN_DECIMALS);
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
    </script>

    <!-- ================= CHATBOT WIDGET (mengambang) ================= -->
    <div id="chatFab" onclick="toggleChat()" class="fixed bottom-5 right-5 z-50 w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center cursor-pointer transition" title="Tanya Asisten E-Trace">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-11.6 7.1L4 20l1-4.3A8 8 0 1121 12z"/></svg>
    </div>
    <div id="chatPanel" class="hidden fixed bottom-5 right-5 z-50 w-[92vw] max-w-sm h-[70vh] max-h-[560px] bg-white border border-slate-200 rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        <div class="bg-blue-600 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-300"></span>
                <span class="font-semibold text-sm">Asisten E-Trace</span>
            </div>
            <button onclick="toggleChat()" class="text-white/80 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div id="chatBody" class="flex-1 overflow-y-auto p-3 space-y-3 bg-slate-50 text-sm">
            <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%] text-slate-700">Halo! Aku asisten E-Trace. Tanya soal cara belanja, TLKM, escrow, PIN, donasi, atau cari produk (mis. "cari sepatu").</div>
        </div>
        <div class="p-2.5 border-t border-slate-100 flex items-center gap-2">
            <input id="chatInput" onkeydown="if(event.key==='Enter')sendChat()" placeholder="Tulis pesan…" class="flex-1 px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 focus:bg-white focus:border-blue-500 outline-none text-sm">
            <button onclick="sendChat()" class="w-9 h-9 rounded-xl bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg></button>
        </div>
    </div>
    <script>
        const chatHistory = [];
        function toggleChat() {
            document.getElementById('chatPanel').classList.toggle('hidden');
            document.getElementById('chatFab').classList.toggle('hidden');
            const i = document.getElementById('chatInput'); if (i && !document.getElementById('chatPanel').classList.contains('hidden')) i.focus();
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
                let html = (data.reply || 'Maaf, terjadi kendala.').replace(/</g,'&lt;').replace(/\n/g,'<br>');
                if (Array.isArray(data.products) && data.products.length) {
                    html += '<div class="mt-2 space-y-1">' + data.products.map(p =>
                        `<a href="${p.url}" class="block text-xs bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 hover:border-blue-400"><b>${(p.name||'').replace(/</g,'&lt;')}</b> · ${p.price} TLKM ↗</a>`
                    ).join('') + '</div>';
                }
                typing.innerHTML = html;
                chatHistory.push({ role: 'assistant', content: data.reply || '' });
            } catch (e) {
                typing.innerHTML = 'Maaf, gagal menghubungi asisten.';
            }
            document.getElementById('chatBody').scrollTop = 1e9;
        }
    </script>

    @yield('scripts')
</body>
</html>

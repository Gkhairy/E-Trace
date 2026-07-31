<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MyCrypto Shop' }}</title>

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
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-extrabold text-sm text-white">M</div>
                <span class="text-lg font-bold tracking-tight text-slate-900 hidden sm:inline">MyCrypto<span class="text-blue-600">Shop</span></span>
            </a>

            <!-- SEARCH (lebar, tengah) -->
            <div class="relative flex-1 max-w-2xl">
                <svg class="w-5 h-5 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Cari produk di MyCryptoShop…"
                    class="w-full bg-slate-100 focus:bg-white text-sm rounded-xl pl-10 pr-4 py-2.5 outline-none text-slate-800 placeholder-slate-400 border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
            </div>

            <!-- NAV (desktop) -->
            <nav class="hidden lg:flex items-center gap-6 text-slate-600 text-sm font-medium shrink-0">
                <a href="/products"  class="hover:text-blue-600 transition">Produk</a>
                <a href="/explorer"  class="hover:text-blue-600 transition">Explorer</a>
                @auth
                    @if(auth()->user()->isSeller())
                        <a href="/seller" class="hover:text-blue-600 transition inline-flex items-center gap-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7l2-4h14l2 4M3 7h18M3 7v13a1 1 0 001 1h16a1 1 0 001-1V7"/></svg>
                            Toko Saya
                        </a>
                    @endif
                    @if(auth()->user()->isSupervisor())
                        <a href="/supervisor/disputes" class="hover:text-blue-600 transition inline-flex items-center gap-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Pengawas
                        </a>
                    @endif
                    <a href="/orders"    class="hover:text-blue-600 transition inline-flex items-center gap-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3M17 13l2.3 2.3M9 20a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                        Order
                    </a>
                @endauth
            </nav>

            @auth
                @php $cartCount = \App\Models\CartItem::where('user_id', auth()->id())->sum('quantity'); @endphp
                <!-- KERANJANG -->
                <a href="/cart" class="relative shrink-0 p-2 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-blue-600 transition" title="Keranjang">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3M17 13l2.3 2.3M9 20a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    <span id="cartBadge" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                </a>

                <!-- WALLET PILL -->
                <div id="walletPill" class="hidden md:flex items-center gap-2 bg-slate-100 border border-slate-200 rounded-full px-3 py-2 text-xs shrink-0">
                    <span class="w-2 h-2 rounded-full bg-slate-400" id="walletDot"></span>
                    <span id="walletLabel" class="text-slate-600 font-mono">Wallet</span>
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
            <p>© {{ date('Y') }} MyCryptoShop — E-commerce berbasis blockchain.</p>
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

    // Wallet yang TERIKAT ke akun login (lowercase) — untuk guard wallet-mismatch.
    const ACCOUNT_WALLET = @json(auth()->check() ? strtolower(auth()->user()->wallet_address ?? '') : null);

    // ---- KONFIGURASI KONTRAK (PaymentGateway v3, Sepolia) ----
    const TLKM_ADDRESS            = "0xFbaa7F02bE3f151920D036cA4Eed2Fb1Ca3e0aEB";
    const PAYMENT_GATEWAY_ADDRESS = "0x0D6F824F6734B6369EdeBbfD6db37f965fa55f26";
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
        "function allowance(address owner, address spender) view returns (uint256)"
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

    async function refreshWalletPill() {
        const dot = document.getElementById('walletDot');
        const label = document.getElementById('walletLabel');
        if (!window.ethereum || !dot || !label) return;   // guest: elemen wallet pill tidak dirender
        try {
            const accs = await window.ethereum.request({ method: 'eth_accounts' });
            if (accs && accs.length) {
                const a = accs[0];
                // Tandai merah "tidak cocok" bila wallet aktif ≠ wallet akun.
                if (ACCOUNT_WALLET && a.toLowerCase() !== ACCOUNT_WALLET) {
                    dot.className = 'w-2 h-2 rounded-full bg-red-500';
                    label.textContent = 'Wallet tidak cocok';
                    label.title = a;
                } else {
                    dot.className = 'w-2 h-2 rounded-full bg-green-500';
                    label.textContent = a.slice(0,6) + '…' + a.slice(-4);
                    label.title = '';
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

    @yield('scripts')
</body>
</html>

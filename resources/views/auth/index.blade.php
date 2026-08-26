<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($mode ?? 'login') === 'register' ? 'Daftar' : 'Masuk' }} — E-Trace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/ethers@6.7.1/dist/ethers.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Hanken Grotesk', system-ui, sans-serif; }

        .auth-card {
            position: relative; width: 820px; max-width: 100%; min-height: 640px;
            background: #fff; border-radius: 28px; overflow: hidden;
            box-shadow: 0 24px 60px rgba(2, 6, 23, 0.18);
        }
        .form-col {
            position: absolute; top: 0; height: 100%; width: 50%;
            display: flex; flex-direction: column; justify-content: center;
            padding: 40px 48px; overflow-y: auto; transition: all .6s ease-in-out;
        }
        .col-signin { left: 0; z-index: 2; }
        .col-signup { left: 0; opacity: 0; z-index: 1; }
        .auth-card.show-signup .col-signin { transform: translateX(100%); opacity: 0; z-index: 1; }
        .auth-card.show-signup .col-signup { transform: translateX(100%); opacity: 1; z-index: 5; animation: reveal .6s; }
        @keyframes reveal { 0%,49.99% { opacity: 0; z-index: 1; } 50%,100% { opacity: 1; z-index: 5; } }

        .overlay-wrap {
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%;
            overflow: hidden; z-index: 100; transition: transform .6s ease-in-out;
        }
        .auth-card.show-signup .overlay-wrap { transform: translateX(-100%); }
        .overlay {
            position: relative; left: -100%; height: 100%; width: 200%;
            background: #2563eb; color: #fff; transform: translateX(0); transition: transform .6s ease-in-out;
        }
        .auth-card.show-signup .overlay { transform: translateX(50%); }
        .overlay-panel {
            position: absolute; top: 0; width: 50%; height: 100%;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            text-align: center; padding: 40px; transition: transform .6s ease-in-out;
        }
        .overlay-left  { transform: translateX(-20%); }
        .auth-card.show-signup .overlay-left  { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .auth-card.show-signup .overlay-right { transform: translateX(20%); }

        .in-field { width: 100%; background: #f1f5f9; border: 1px solid transparent; border-radius: 12px; padding: 11px 16px; font-size: 14px; outline: none; transition: .15s; }
        .in-field:focus { background: #fff; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .ghost-btn { border: 1.5px solid rgba(255,255,255,.85); color: #fff; border-radius: 999px; padding: 11px 40px; font-weight: 600; font-size: 14px; transition: .15s; }
        .ghost-btn:hover { background: rgba(255,255,255,.12); }

        .mobile-switch { display: none; }
        @media (max-width: 820px) {
            .auth-card { width: 100%; min-height: auto; border-radius: 22px; }
            .overlay-wrap { display: none; }
            .form-col { position: static; width: 100%; opacity: 1 !important; transform: none !important; padding: 32px 24px; }
            .col-signup { display: none; }
            .auth-card.show-signup .col-signin { display: none; }
            .auth-card.show-signup .col-signup { display: flex; }
            .mobile-switch { display: block; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 text-slate-900">

@php
    $startRegister = ($mode ?? 'login') === 'register' || $errors->hasAny(['name', 'phone', 'wallet_address', 'password_confirmation']);
@endphp

<div class="w-full max-w-[820px]">
    <div class="flex items-center justify-center gap-2 mb-6">
        <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center font-extrabold text-white">E</div>
        <span class="text-xl font-bold tracking-tight">E-<span class="text-blue-600">Trace</span></span>
    </div>

    <div class="auth-card {{ $startRegister ? 'show-signup' : '' }}" id="authCard">

        {{-- ===== MASUK ===== --}}
        <div class="form-col col-signin">
            <h1 class="text-2xl font-bold text-slate-900">Masuk</h1>
            <p class="text-sm text-slate-500 mt-1 mb-5">Belanja aman dengan pembayaran crypto.</p>

            @if($errors->any() && !$startRegister)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-4 text-sm">{{ $errors->first() }}</div>
            @endif

            <button id="mmBtn" type="button" onclick="loginWithWallet()"
                class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-60 text-white text-sm font-semibold transition flex items-center justify-center gap-2 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h.01M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                Masuk dengan MetaMask
            </button>

            <div class="flex items-center gap-3 my-2 mb-3">
                <div class="h-px bg-slate-200 flex-1"></div>
                <span class="text-xs text-slate-400">atau</span>
                <div class="h-px bg-slate-200 flex-1"></div>
            </div>

            <div class="flex gap-2 mb-3 text-sm">
                <button type="button" id="ltabPass" onclick="loginMode('pass')" class="flex-1 py-2 rounded-lg border border-blue-500 bg-blue-50 text-blue-700 font-medium">Password</button>
                <button type="button" id="ltabPin" onclick="loginMode('pin')" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600">PIN</button>
            </div>

            <form method="POST" action="/login" id="loginPass" class="space-y-3">
                @csrf
                <input name="email" type="email" value="{{ !$startRegister ? old('email') : '' }}" required placeholder="Email" class="in-field">
                <input name="password" type="password" required placeholder="Password" class="in-field">
                <button class="w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm mt-1">Masuk</button>
            </form>

            <form method="POST" action="/login-pin" id="loginPin" class="space-y-3 hidden">
                @csrf
                <input name="email" type="email" value="{{ old('email') }}" placeholder="Email" class="in-field">
                <input name="pin" type="password" inputmode="numeric" maxlength="6" placeholder="PIN 6 angka" class="in-field">
                <button class="w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm mt-1">Masuk dengan PIN</button>
            </form>

            <p class="mobile-switch text-sm text-slate-500 text-center mt-5">
                Belum punya akun? <button type="button" onclick="toRegister()" class="text-blue-600 font-medium">Daftar</button>
            </p>
        </div>

        {{-- ===== DAFTAR ===== --}}
        <div class="form-col col-signup">
            <h1 class="text-2xl font-bold text-slate-900">Buat Akun</h1>
            <p class="text-sm text-slate-500 mt-1 mb-4"><b>PIN 6 angka</b> wajib untuk semua akun (login &amp; bayar). Pilih wallet: dibuatkan otomatis, atau hubungkan MetaMask.</p>

            @if($errors->any() && $startRegister)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-3 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Pilih metode wallet (PIN tetap wajib di kedua mode) --}}
            <div class="flex gap-2 mb-3 text-sm">
                <button type="button" id="tabPin" onclick="regMode('pin')" class="flex-1 py-2 rounded-lg border border-blue-500 bg-blue-50 text-blue-700 font-medium">Wallet otomatis</button>
                <button type="button" id="tabMm" onclick="regMode('mm')" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600">Pakai MetaMask</button>
            </div>

            <div id="mmConnect" class="hidden mb-3">
                <button type="button" onclick="connectWallet()" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <span id="cwLabel">Connect Wallet</span>
                </button>
            </div>

            <form method="POST" action="/register" class="space-y-3">
                @csrf
                <input name="name" value="{{ $startRegister ? old('name') : '' }}" required placeholder="Nama lengkap" class="in-field @error('name') !border-red-400 @enderror">
                <input name="email" type="email" value="{{ $startRegister ? old('email') : '' }}" required placeholder="Email" class="in-field @error('email') !border-red-400 @enderror">
                <input name="phone" type="text" value="{{ $startRegister ? old('phone') : '' }}" maxlength="15" required placeholder="No HP (08xxxx)" class="in-field @error('phone') !border-red-400 @enderror">
                <input name="password" type="password" required placeholder="Password (min 8 karakter)" class="in-field @error('password') !border-red-400 @enderror">
                <input name="password_confirmation" type="password" required placeholder="Ulangi password" class="in-field">

                {{-- Blok PIN (default) --}}
                <div id="pinBlock" class="space-y-3">
                    <input name="pin" id="pin" type="password" inputmode="numeric" maxlength="6" required placeholder="PIN 6 angka (untuk bayar & login)" class="in-field @error('pin') !border-red-400 @enderror">
                    <input name="pin_confirmation" id="pin_confirmation" type="password" inputmode="numeric" maxlength="6" required placeholder="Ulangi PIN" class="in-field">
                </div>

                {{-- Blok MetaMask --}}
                <div id="mmBlock" class="hidden">
                    <input name="wallet_address" id="wallet_address" value="{{ old('wallet_address') }}" readonly disabled placeholder="Wallet — klik Connect Wallet" class="in-field font-mono text-slate-600 !bg-slate-100 cursor-not-allowed @error('wallet_address') !border-red-400 @enderror">
                    <input type="hidden" name="signature" id="signature" value="{{ old('signature') }}" disabled>
                    <input type="hidden" name="sig_timestamp" id="sig_timestamp" value="{{ old('sig_timestamp') }}" disabled>
                </div>

                <button class="w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm mt-1">Buat Akun</button>
            </form>

            <p class="mobile-switch text-sm text-slate-500 text-center mt-5">
                Sudah punya akun? <button type="button" onclick="toLogin()" class="text-blue-600 font-medium">Masuk</button>
            </p>
        </div>

        {{-- ===== OVERLAY (geser) ===== --}}
        <div class="overlay-wrap">
            <div class="overlay">
                {{-- Terlihat saat panel DAFTAR aktif --}}
                <div class="overlay-panel overlay-left">
                    <h2 class="text-3xl font-extrabold">Selamat datang kembali!</h2>
                    <p class="text-sm text-blue-100 mt-3 max-w-xs">Sudah punya akun? Masuk untuk lanjut belanja dengan TLKM.</p>
                    <button type="button" onclick="toLogin()" class="ghost-btn mt-6">Masuk</button>
                </div>
                {{-- Terlihat saat panel MASUK aktif --}}
                <div class="overlay-panel overlay-right">
                    <h2 class="text-3xl font-extrabold">Halo, teman!</h2>
                    <p class="text-sm text-blue-100 mt-3 max-w-xs">Belum punya akun? Daftar & mulai belanja on-chain yang transparan.</p>
                    <button type="button" onclick="toRegister()" class="ghost-btn mt-6">Daftar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const authCard = document.getElementById('authCard');
    function toRegister() { authCard.classList.add('show-signup'); }
    function toLogin()    { authCard.classList.remove('show-signup'); }

    // Metode wallet: 'pin' = wallet otomatis (embedded), 'mm' = MetaMask.
    // PIN SELALU wajib di kedua mode; hanya field wallet yang di-enable/disable.
    function regMode(m) {
        const embedded = m === 'pin';
        document.getElementById('mmBlock').classList.toggle('hidden', embedded);
        document.getElementById('mmConnect').classList.toggle('hidden', embedded);
        document.getElementById('wallet_address').disabled = embedded;
        document.getElementById('signature').disabled = embedded;
        document.getElementById('sig_timestamp').disabled = embedded;
        setTab('tabPin', embedded); setTab('tabMm', !embedded);
    }
    // Metode masuk: Password vs PIN.
    function loginMode(m) {
        const pass = m === 'pass';
        document.getElementById('loginPass').classList.toggle('hidden', !pass);
        document.getElementById('loginPin').classList.toggle('hidden', pass);
        setTab('ltabPass', pass); setTab('ltabPin', !pass);
    }
    function setTab(id, active) {
        const el = document.getElementById(id);
        el.className = 'flex-1 py-2 rounded-lg border ' + (active ? 'border-blue-500 bg-blue-50 text-blue-700 font-medium' : 'border-slate-200 text-slate-600');
    }

    // ===== REGISTER: connect wallet + tanda tangan kepemilikan =====
    async function connectWallet() {
        if (!window.ethereum) { alert("Install MetaMask dulu!"); return; }
        try {
            const accounts = await ethereum.request({ method: "eth_requestAccounts" });
            const wallet = accounts[0];
            const ts = Math.floor(Date.now() / 1000);
            const message = "E-Trace register\nWallet: " + wallet.toLowerCase() + "\nWaktu: " + ts;
            const provider = new ethers.BrowserProvider(window.ethereum);
            const signer = await provider.getSigner();
            const signature = await signer.signMessage(message);
            document.getElementById("wallet_address").value = wallet;
            document.getElementById("sig_timestamp").value = ts;
            document.getElementById("signature").value = signature;
            document.getElementById("cwLabel").textContent = "Wallet terhubung ✓";
        } catch (e) {
            alert(e.code === 4001 || e.code === 'ACTION_REJECTED'
                ? "Kamu membatalkan tanda tangan di MetaMask."
                : ("Gagal menghubungkan wallet: " + (e.message || e)));
        }
    }

    // ===== LOGIN dengan MetaMask (nonce + tanda tangan) =====
    async function loginWithWallet() {
        const btn = document.getElementById('mmBtn');
        const orig = btn.innerHTML;
        try {
            if (!window.ethereum) return alert("Install MetaMask dulu ya!");
            btn.disabled = true; btn.textContent = "Menghubungkan…";
            const accounts = await ethereum.request({ method: "eth_requestAccounts" });
            const wallet = accounts[0];
            const nonceRes = await fetch("/api/get-nonce?wallet=" + wallet);
            const nonceJson = await nonceRes.json();
            if (!nonceJson.nonce) { alert("Wallet ini belum terdaftar. Silakan Daftar dulu."); return; }
            btn.textContent = "Menunggu tanda tangan…";
            const provider = new ethers.BrowserProvider(window.ethereum);
            const signer = await provider.getSigner();
            const signature = await signer.signMessage("Login with wallet\nNonce: " + nonceJson.nonce);
            btn.textContent = "Memverifikasi…";
            const loginReq = await fetch("/login-wallet", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: JSON.stringify({ wallet_address: wallet, signature: signature })
            });
            let loginRes;
            try { loginRes = await loginReq.json(); }
            catch (_) { throw new Error("Server error (" + loginReq.status + "). Cek log Laravel."); }
            if (loginReq.ok && loginRes.success) { window.location.href = loginRes.redirect || "/products"; }
            else { alert(loginRes.error || "Login gagal."); }
        } catch (e) {
            console.error(e);
            alert((e.code === 4001 || e.code === 'ACTION_REJECTED') ? "Kamu membatalkan tanda tangan di MetaMask." : (e.message || "Login gagal."));
        } finally {
            btn.disabled = false; btn.innerHTML = orig;
        }
    }
</script>
</body>
</html>

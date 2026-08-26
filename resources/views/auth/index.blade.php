<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
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

{{-- Ikon ganti bahasa (pojok kanan atas) --}}
<div class="fixed top-4 right-4 z-50">@include('partials.lang-switcher')</div>

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
            <h1 class="text-2xl font-bold text-slate-900">{{ __('auth.login_title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 mb-5">{{ __('auth.login_sub') }}</p>

            @if($errors->any() && !$startRegister)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-4 text-sm">{{ $errors->first() }}</div>
            @endif

            <button id="mmBtn" type="button" onclick="loginWithWallet()"
                class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-60 text-white text-sm font-semibold transition flex items-center justify-center gap-2 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h.01M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                {{ __('auth.sign_in_mm') }}
            </button>

            <div class="flex items-center gap-3 my-2 mb-3">
                <div class="h-px bg-slate-200 flex-1"></div>
                <span class="text-xs text-slate-400">atau</span>
                <div class="h-px bg-slate-200 flex-1"></div>
            </div>

            <form method="POST" action="/login" id="loginPass" class="space-y-3">
                @csrf
                <input name="email" type="email" value="{{ !$startRegister ? old('email') : '' }}" required placeholder="{{ __('auth.email') }}" class="in-field">
                <input name="password" type="password" required placeholder="{{ __('auth.password') }}" class="in-field">
                <button class="w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm mt-1">{{ __('auth.sign_in') }}</button>
            </form>
            <p class="text-[11px] text-slate-400 text-center mt-2">{{ __('auth.pin_after_pw') }}</p>

            <p class="mobile-switch text-sm text-slate-500 text-center mt-5">
                {{ __('auth.no_account') }} <button type="button" onclick="toRegister()" class="text-blue-600 font-medium">{{ __('nav.register') }}</button>
            </p>
        </div>

        {{-- ===== DAFTAR ===== --}}
        <div class="form-col col-signup">
            <h1 class="text-2xl font-bold text-slate-900">{{ __('auth.register_title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 mb-4">{{ __('auth.register_sub') }}</p>

            @if($errors->any() && $startRegister)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-3 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Pilih metode wallet (PIN tetap wajib di kedua mode) --}}
            <div class="flex gap-2 mb-3 text-sm">
                <button type="button" id="tabPin" onclick="regMode('pin')" class="flex-1 py-2 rounded-lg border border-blue-500 bg-blue-50 text-blue-700 font-medium">{{ __('auth.wallet_auto') }}</button>
                <button type="button" id="tabMm" onclick="regMode('mm')" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600">{{ __('auth.wallet_mm') }}</button>
            </div>

            <div id="mmConnect" class="hidden mb-3">
                <button type="button" onclick="connectWallet()" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <span id="cwLabel">Connect Wallet</span>
                </button>
            </div>

            <form method="POST" action="/register" id="regForm" class="space-y-3">
                @csrf
                <input name="name" value="{{ $startRegister ? old('name') : '' }}" required placeholder="{{ __('auth.full_name') }}" class="in-field @error('name') !border-red-400 @enderror">
                <input name="email" type="email" value="{{ $startRegister ? old('email') : '' }}" required placeholder="{{ __('auth.email') }}" class="in-field @error('email') !border-red-400 @enderror">

                {{-- No HP dengan PEMILIH KODE NEGARA (target internasional) --}}
                <div class="flex gap-2">
                    <select id="phoneCc" class="in-field !w-[132px] shrink-0 !px-2" aria-label="Kode negara">
                        @php
                            $dials = [
                                ['+62','ID','🇮🇩'], ['+60','MY','🇲🇾'], ['+65','SG','🇸🇬'], ['+66','TH','🇹🇭'],
                                ['+63','PH','🇵🇭'], ['+84','VN','🇻🇳'], ['+1','US','🇺🇸'], ['+44','GB','🇬🇧'],
                                ['+61','AU','🇦🇺'], ['+91','IN','🇮🇳'], ['+86','CN','🇨🇳'], ['+81','JP','🇯🇵'],
                                ['+82','KR','🇰🇷'], ['+49','DE','🇩🇪'], ['+33','FR','🇫🇷'], ['+971','AE','🇦🇪'],
                                ['+966','SA','🇸🇦'], ['+31','NL','🇳🇱'], ['+55','BR','🇧🇷'], ['+7','RU','🇷🇺'],
                            ];
                        @endphp
                        @foreach($dials as $d)
                            <option value="{{ $d[0] }}" @selected($d[0]==='+62')>{{ $d[2] }} {{ $d[1] }} {{ $d[0] }}</option>
                        @endforeach
                    </select>
                    <input id="phoneNum" type="tel" inputmode="numeric" required placeholder="{{ __('auth.phone') }}" class="in-field @error('phone') !border-red-400 @enderror" value="{{ $startRegister ? old('phone_local') : '' }}">
                </div>
                {{-- Nomor lengkap (kode negara + nomor) diisi oleh JS saat submit --}}
                <input type="hidden" name="phone" id="phoneHidden" value="{{ $startRegister ? old('phone') : '' }}">

                <input name="password" type="password" required placeholder="{{ __('auth.password_min') }}" class="in-field @error('password') !border-red-400 @enderror">
                <input name="password_confirmation" type="password" required placeholder="{{ __('auth.password_again') }}" class="in-field">

                {{-- PIN dikumpulkan lewat MODAL setelah klik Daftar (form tetap pendek). --}}
                <input type="hidden" name="pin" id="pinHidden">
                <input type="hidden" name="pin_confirmation" id="pinConfHidden">

                {{-- Blok MetaMask --}}
                <div id="mmBlock" class="hidden">
                    <input name="wallet_address" id="wallet_address" value="{{ old('wallet_address') }}" readonly disabled placeholder="Wallet — klik Connect Wallet" class="in-field font-mono text-slate-600 !bg-slate-100 cursor-not-allowed @error('wallet_address') !border-red-400 @enderror">
                    <input type="hidden" name="signature" id="signature" value="{{ old('signature') }}" disabled>
                    <input type="hidden" name="sig_timestamp" id="sig_timestamp" value="{{ old('sig_timestamp') }}" disabled>
                </div>

                <button type="button" onclick="openPinModal()" class="w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm mt-1">{{ __('auth.create_account') }}</button>
                <p class="text-[11px] text-slate-400 text-center">{{ __('auth.pin_after') }}</p>
            </form>

            {{-- ===== MODAL SET PIN (muncul setelah klik "Buat Akun") ===== --}}
            <div id="pinModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6" onclick="event.stopPropagation()">
                    <h3 class="text-lg font-bold text-slate-900 mb-1">{{ __('auth.pin_make_title') }}</h3>
                    <p class="text-sm text-slate-500 mb-4">{{ __('auth.pin_make_sub') }}</p>
                    <div id="pinModalErr" class="hidden bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm mb-3"></div>
                    <input id="mPin" type="password" inputmode="numeric" maxlength="6" autocomplete="off" placeholder="••••••"
                        class="w-full text-center tracking-[0.5em] text-xl font-bold px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 mb-2">
                    <input id="mPin2" type="password" inputmode="numeric" maxlength="6" autocomplete="off" placeholder="{{ __('auth.pin_repeat') }}"
                        class="w-full text-center tracking-[0.5em] text-xl font-bold px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none focus:bg-white focus:border-blue-500 mb-4">
                    <div class="flex gap-3">
                        <button type="button" onclick="closePinModal()" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">{{ __('common.back') }}</button>
                        <button type="button" onclick="confirmPinAndRegister()" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">{{ __('auth.pin_confirm') }}</button>
                    </div>
                </div>
            </div>

            <p class="mobile-switch text-sm text-slate-500 text-center mt-5">
                {{ __('auth.have_account') }} <button type="button" onclick="toLogin()" class="text-blue-600 font-medium">{{ __('nav.login') }}</button>
            </p>
        </div>

        {{-- ===== OVERLAY (geser) ===== --}}
        <div class="overlay-wrap">
            <div class="overlay">
                {{-- Terlihat saat panel DAFTAR aktif --}}
                <div class="overlay-panel overlay-left">
                    <h2 class="text-3xl font-extrabold">{{ __('auth.login_title') }}!</h2>
                    <p class="text-sm text-blue-100 mt-3 max-w-xs">{{ __('auth.have_account') }} {{ __('auth.login_sub') }}</p>
                    <button type="button" onclick="toLogin()" class="ghost-btn mt-6">{{ __('nav.login') }}</button>
                </div>
                {{-- Terlihat saat panel MASUK aktif --}}
                <div class="overlay-panel overlay-right">
                    <h2 class="text-3xl font-extrabold">{{ __('auth.register_title') }}</h2>
                    <p class="text-sm text-blue-100 mt-3 max-w-xs">{{ __('auth.no_account') }} {{ __('auth.pin_after') }}</p>
                    <button type="button" onclick="toRegister()" class="ghost-btn mt-6">{{ __('nav.register') }}</button>
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
    function setTab(id, active) {
        const el = document.getElementById(id);
        el.className = 'flex-1 py-2 rounded-lg border ' + (active ? 'border-blue-500 bg-blue-50 text-blue-700 font-medium' : 'border-slate-200 text-slate-600');
    }

    // ===== MODAL SET PIN (langkah terakhir daftar) =====
    const regForm = document.getElementById('regForm');
    function isMetamaskMode() { return !document.getElementById('mmBlock').classList.contains('hidden'); }

    // Gabungkan kode negara + nomor lokal jadi nomor lengkap (mis. +62 + 0812 -> +62812).
    function buildFullPhone() {
        const cc = document.getElementById('phoneCc').value;
        let num = (document.getElementById('phoneNum').value || '').replace(/[^0-9]/g, '');
        num = num.replace(/^0+/, ''); // buang angka 0 di depan (prefix lokal)
        document.getElementById('phoneHidden').value = num ? (cc + num) : '';
        return num.length >= 6;
    }
    function openPinModal() {
        // Validasi field wajib dulu (nama/email/HP/password) sebelum minta PIN.
        if (!regForm.reportValidity()) return;
        // Bangun nomor telepon lengkap (kode negara + nomor).
        if (!buildFullPhone()) { alert('Masukkan nomor HP yang valid.'); return; }
        // Mode MetaMask: pastikan wallet sudah terhubung.
        if (isMetamaskMode() && !document.getElementById('wallet_address').value) {
            alert('Hubungkan MetaMask dulu (klik "Connect Wallet").');
            return;
        }
        document.getElementById('pinModalErr').classList.add('hidden');
        document.getElementById('mPin').value = '';
        document.getElementById('mPin2').value = '';
        document.getElementById('pinModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('mPin').focus(), 50);
    }
    function closePinModal() { document.getElementById('pinModal').classList.add('hidden'); }

    function confirmPinAndRegister() {
        const pin = (document.getElementById('mPin').value || '').trim();
        const pin2 = (document.getElementById('mPin2').value || '').trim();
        const err = (m) => { const e = document.getElementById('pinModalErr'); e.textContent = m; e.classList.remove('hidden'); };
        if (!/^\d{6}$/.test(pin)) return err('PIN harus 6 angka.');
        if (pin !== pin2) return err('Konfirmasi PIN tidak cocok.');
        document.getElementById('pinHidden').value = pin;
        document.getElementById('pinConfHidden').value = pin2;
        regForm.submit();
    }
    // Klik area gelap menutup modal; Enter di input kedua = konfirmasi.
    document.getElementById('pinModal').addEventListener('click', closePinModal);
    document.getElementById('mPin2').addEventListener('keydown', (e) => { if (e.key === 'Enter') confirmPinAndRegister(); });

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

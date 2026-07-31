<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MyCryptoShop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/ethers@6.7.1/dist/ethers.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',system-ui,sans-serif}</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 text-slate-900">

    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="flex items-center justify-center gap-2 mb-6">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-extrabold text-white">M</div>
            <span class="text-xl font-bold tracking-tight">MyCrypto<span class="text-blue-600">Shop</span></span>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            <h1 class="text-2xl font-bold mb-1">Masuk</h1>
            <p class="text-sm text-slate-500 mb-6">Belanja aman dengan pembayaran crypto.</p>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- LOGIN NORMAL --}}
            <form method="POST" action="/login" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required placeholder="you@email.com"
                        class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input name="password" type="password" required placeholder="••••••••"
                        class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
                </div>
                <button class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Login</button>
            </form>

            <div class="flex items-center gap-3 my-5">
                <div class="h-px bg-slate-200 flex-1"></div>
                <span class="text-xs text-slate-400">atau</span>
                <div class="h-px bg-slate-200 flex-1"></div>
            </div>

            {{-- LOGIN METAMASK --}}
            <button id="mmBtn" onclick="loginWithWallet()"
                class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-60 text-white text-sm font-semibold transition flex items-center justify-center gap-2">
                🔐 Login dengan MetaMask
            </button>

            <p class="text-sm text-slate-500 text-center mt-6">
                Belum punya akun? <a href="/register" class="text-blue-600 font-medium hover:underline">Daftar</a>
            </p>
        </div>
    </div>

    <script>
    async function loginWithWallet() {
        const btn = document.getElementById('mmBtn');
        try {
            if (!window.ethereum) return alert("Install MetaMask dulu ya!");

            btn.disabled = true;
            btn.textContent = "Menghubungkan…";

            const accounts = await ethereum.request({ method: "eth_requestAccounts" });
            const wallet = accounts[0];

            const nonceRes = await fetch("/api/get-nonce?wallet=" + wallet);
            const nonceJson = await nonceRes.json();
            if (!nonceJson.nonce) {
                alert("Wallet ini belum terdaftar. Silakan Daftar dulu.");
                return;
            }

            btn.textContent = "Menunggu tanda tangan…";
            const provider = new ethers.BrowserProvider(window.ethereum);
            const signer = await provider.getSigner();
            const signature = await signer.signMessage("Login with wallet\nNonce: " + nonceJson.nonce);

            btn.textContent = "Memverifikasi…";
            const loginReq = await fetch("/login-wallet", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ wallet_address: wallet, signature: signature })
            });

            // Tangani respons non-JSON / error server supaya tidak "stuck" diam-diam.
            let loginRes;
            try {
                loginRes = await loginReq.json();
            } catch (_) {
                throw new Error("Server error (" + loginReq.status + "). Cek log Laravel.");
            }

            if (loginReq.ok && loginRes.success) {
                window.location.href = loginRes.redirect || "/products";
            } else {
                alert(loginRes.error || "Login gagal.");
            }
        } catch (e) {
            console.error(e);
            const msg = (e.code === 4001 || e.code === 'ACTION_REJECTED')
                ? "Kamu membatalkan tanda tangan di MetaMask."
                : (e.message || "Login gagal.");
            alert(msg);
        } finally {
            btn.disabled = false;
            btn.innerHTML = "🔐 Login dengan MetaMask";
        }
    }
    </script>
</body>
</html>

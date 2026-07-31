<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — MyCryptoShop</title>
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
            <h1 class="text-2xl font-bold mb-1">Buat Akun</h1>
            <p class="text-sm text-slate-500 mb-6">Hubungkan wallet lalu lengkapi data kamu.</p>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{-- CONNECT WALLET --}}
            <button type="button" onclick="connectWallet()"
                class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition flex items-center justify-center gap-2 mb-5">
                🔗 Connect Wallet
            </button>

            <form method="POST" action="/register" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama</label>
                    <input name="name" value="{{ old('name') }}" required placeholder="Nama lengkap"
                        class="w-full px-4 py-2.5 rounded-xl bg-white border @error('name') border-red-400 @else border-slate-300 @enderror focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required placeholder="you@email.com"
                        class="w-full px-4 py-2.5 rounded-xl bg-white border @error('email') border-red-400 @else border-slate-300 @enderror focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">No HP</label>
                    <input name="phone" type="text" value="{{ old('phone') }}" maxlength="15" required placeholder="Contoh: 081234567890"
                        class="w-full px-4 py-2.5 rounded-xl bg-white border @error('phone') border-red-400 @else border-slate-300 @enderror focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
                    @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Wallet Address</label>
                    <input name="wallet_address" id="wallet_address" value="{{ old('wallet_address') }}" readonly required placeholder="Klik Connect Wallet di atas"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border @error('wallet_address') border-red-400 @else border-slate-300 @enderror outline-none text-sm font-mono text-slate-600 cursor-not-allowed">
                    @error('wallet_address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    {{-- Bukti kepemilikan wallet (diisi saat Connect Wallet) --}}
                    <input type="hidden" name="signature" id="signature" value="{{ old('signature') }}">
                    <input type="hidden" name="sig_timestamp" id="sig_timestamp" value="{{ old('sig_timestamp') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input name="password" type="password" required placeholder="Minimal 8 karakter"
                        class="w-full px-4 py-2.5 rounded-xl bg-white border @error('password') border-red-400 @else border-slate-300 @enderror focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Password</label>
                    <input name="password_confirmation" type="password" required placeholder="Ulangi password"
                        class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
                </div>

                <button class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Buat Akun</button>
            </form>

            <p class="text-sm text-slate-500 text-center mt-6">
                Sudah punya akun? <a href="/login" class="text-blue-600 font-medium hover:underline">Login</a>
            </p>
        </div>
    </div>

    <script>
    async function connectWallet() {
        if (!window.ethereum) {
            alert("Install MetaMask dulu!");
            return;
        }
        try {
            const accounts = await ethereum.request({ method: "eth_requestAccounts" });
            const wallet = accounts[0];

            // Bukti kepemilikan: tanda tangani pesan dengan wallet ini.
            const ts = Math.floor(Date.now() / 1000);
            const message = "MyCryptoShop register\nWallet: " + wallet.toLowerCase() + "\nWaktu: " + ts;
            const provider = new ethers.BrowserProvider(window.ethereum);
            const signer = await provider.getSigner();
            const signature = await signer.signMessage(message);

            document.getElementById("wallet_address").value = wallet;
            document.getElementById("sig_timestamp").value = ts;
            document.getElementById("signature").value = signature;
            alert("Wallet terhubung & terverifikasi:\n" + wallet);
        } catch (e) {
            alert(e.code === 4001 || e.code === 'ACTION_REJECTED'
                ? "Kamu membatalkan tanda tangan di MetaMask."
                : ("Gagal menghubungkan wallet: " + (e.message || e)));
        }
    }
    </script>
</body>
</html>

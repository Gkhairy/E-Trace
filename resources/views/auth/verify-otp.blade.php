<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Verifikasi Email — E-Trace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Hanken Grotesk',system-ui,sans-serif}</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 text-slate-900">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-center gap-2 mb-6">
            <img src="{{ asset('favicon.svg') }}" alt="" class="w-9 h-9 shrink-0">
            <span class="text-xl font-bold tracking-tight">E-<span class="text-blue-600">Trace</span></span>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            <h1 class="text-2xl font-bold mb-1">Verifikasi Email</h1>
            <p class="text-sm text-slate-500 mb-6">Kami mengirim kode 6 digit ke <b class="text-slate-700">{{ $email }}</b>. Masukkan di bawah.</p>

            @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2.5 rounded-xl mb-4 text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-4 text-sm">{{ $errors->first() }}</div>@endif

            <form method="POST" action="/verify-otp" class="space-y-4">
                @csrf
                <input name="code" inputmode="numeric" maxlength="6" autofocus required placeholder="______"
                    class="w-full text-center tracking-[0.5em] text-2xl font-bold px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                <button class="w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Verifikasi</button>
            </form>

            <form method="POST" action="/resend-otp" class="mt-4 text-center">
                @csrf
                <button id="resendBtn" type="submit"
                    class="text-sm text-blue-600 hover:underline disabled:text-slate-400 disabled:no-underline disabled:cursor-not-allowed">
                    Tidak menerima kode? Kirim ulang
                </button>
                <p id="resendTimer" class="text-xs text-slate-400 mt-1 hidden">Kirim ulang dalam <span id="resendSecs">0</span> detik</p>
            </form>
        </div>
        <p class="text-center text-xs text-slate-400 mt-4">Kode berlaku 10 menit. <a href="/login" class="text-blue-600 hover:underline">Kembali ke login</a></p>
    </div>

    <script>
        // Cooldown "kirim ulang" yang TERLIHAT: hitung mundur dari sisa detik server.
        (function () {
            let remaining = {{ (int) ($cooldown ?? 0) }};
            const btn = document.getElementById('resendBtn');
            const timer = document.getElementById('resendTimer');
            const secs = document.getElementById('resendSecs');
            if (!btn) return;

            function tick() {
                if (remaining <= 0) {
                    btn.disabled = false;
                    timer.classList.add('hidden');
                    return;
                }
                btn.disabled = true;
                timer.classList.remove('hidden');
                secs.textContent = remaining;
                remaining -= 1;
                setTimeout(tick, 1000);
            }
            tick();
        })();
    </script>
</body>
</html>

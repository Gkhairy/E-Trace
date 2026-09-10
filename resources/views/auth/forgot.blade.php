<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Lupa Password — E-Trace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Hanken Grotesk',system-ui,sans-serif}</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 text-slate-900">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-center gap-2 mb-6">
            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center font-extrabold text-white">E</div>
            <span class="text-xl font-bold tracking-tight">E-<span class="text-blue-600">Trace</span></span>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            <h1 class="text-2xl font-bold mb-1">Lupa Password</h1>
            <p class="text-sm text-slate-500 mb-6">Masukkan email akunmu. Kami akan mengirim <b>kode 6 digit</b> untuk mengatur ulang password.</p>

            @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2.5 rounded-xl mb-4 text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-4 text-sm">{{ $errors->first() }}</div>@endif

            <form method="POST" action="/forgot-password" class="space-y-4">
                @csrf
                <input name="email" type="email" value="{{ old('email') }}" autofocus required placeholder="Email"
                    class="w-full px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                <button class="w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Kirim Kode Reset</button>
            </form>
        </div>
        <p class="text-center text-xs text-slate-400 mt-4">Ingat passwordmu? <a href="/login" class="text-blue-600 hover:underline">Kembali ke login</a></p>
    </div>
</body>
</html>

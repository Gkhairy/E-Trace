@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto">
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
        <a href="/profile" class="hover:text-blue-600 transition">Profil</a>
        <span class="text-slate-300">/</span><span class="text-slate-700">Aktifkan 2FA</span>
    </nav>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h1 class="text-xl font-bold text-slate-900 mb-1">Aktifkan Autentikasi 2 Langkah</h1>
        <p class="text-sm text-slate-500 mb-5">Scan QR ini dengan Google Authenticator / Authy, lalu masukkan kode 6 digit untuk konfirmasi.</p>

        @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl mb-4 text-sm">{{ $errors->first() }}</div>@endif

        <div class="flex justify-center mb-4">
            <div class="p-3 bg-white border border-slate-200 rounded-xl">{!! $qrSvg !!}</div>
        </div>

        <p class="text-xs text-slate-500 mb-1">Atau masukkan kode manual:</p>
        <p class="font-mono text-sm bg-slate-100 border border-slate-200 rounded-lg px-3 py-2 mb-5 break-all select-all">{{ $secret }}</p>

        <form method="POST" action="/two-factor/confirm" class="space-y-3">
            @csrf
            <input name="code" inputmode="numeric" maxlength="6" required placeholder="Kode 6 digit"
                class="w-full text-center tracking-widest text-lg font-bold px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
            <button class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Aktifkan 2FA</button>
        </form>
        <a href="/profile" class="block text-center text-sm text-slate-500 hover:text-slate-700 mt-4">Batal</a>
    </div>
</div>
@endsection

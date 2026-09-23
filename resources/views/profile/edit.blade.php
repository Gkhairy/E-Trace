@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/products" class="hover:text-blue-600 transition">Beranda</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Profil Publik</span>
</nav>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Profil Publik</h1>
    <p class="text-sm text-slate-500 mb-2">Atur bagaimana identitasmu tampil di <a href="/explorer" class="text-blue-600 hover:underline">Explorer</a> transparansi. Data pribadi (email, telepon, alamat) <b>tidak akan</b> ditampilkan.</p>
    <p class="inline-flex items-center gap-1.5 text-xs text-slate-500 bg-slate-100 border border-slate-200 rounded-full px-3 py-1 mb-6">
        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
        Bergabung sejak {{ $user->created_at ? $user->created_at->translatedFormat('F Y') : '—' }}
    </p>

    @if($user->wallet_address)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6 flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs text-slate-500">Saldo TLKM kamu</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-0.5">{{ $balance !== null ? rtrim(rtrim(number_format((float)$balance, 2), '0'), '.') : '—' }} <span class="text-sm text-blue-600">TLKM</span></p>
                <p class="text-[11px] text-slate-400 mt-0.5 font-mono truncate">{{ substr($user->wallet_address, 0, 12) }}…{{ substr($user->wallet_address, -6) }}</p>
            </div>
            <a href="{{ config('chain.explorer_url') }}/token/{{ config('chain.tlkm') }}?a={{ $user->wallet_address }}" target="_blank" rel="noopener" class="shrink-0 text-xs text-blue-600 hover:underline">BscScan ↗</a>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ===== 2FA (Autentikasi 2 Langkah) ===== --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-slate-900">Autentikasi 2 Langkah (2FA)</h2>
                    @if($user->hasTwoFactor())
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">Aktif</span>
                    @else
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Nonaktif</span>
                    @endif
                </div>
                <p class="text-sm text-slate-500 mt-1 max-w-md">Tambahan keamanan pakai aplikasi Authenticator (Google Authenticator/Authy). Diminta saat login.</p>
            </div>
        </div>

        @if($user->hasTwoFactor())
            <form action="/two-factor/disable" method="POST" class="mt-4 flex flex-wrap items-end gap-3" onsubmit="return confirmSubmit(event, {title: 'Matikan 2FA?', message: 'Login tidak lagi meminta kode dari aplikasi autentikator.', confirmText: 'Matikan 2FA', danger: true})">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Konfirmasi password untuk mematikan</label>
                    <input type="password" name="password" required placeholder="Password" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <button class="py-2.5 px-4 rounded-xl bg-white border border-red-300 text-red-700 hover:bg-red-50 text-sm font-semibold transition">Matikan 2FA</button>
            </form>
        @else
            <a href="/two-factor/setup" class="inline-block mt-4 py-2.5 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Aktifkan 2FA</a>
        @endif
    </div>

    <form action="/profile" method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama tampilan (nama samaran)</label>
            <input type="text" name="public_name" value="{{ old('public_name', $user->public_name) }}" maxlength="40" placeholder="mis. KolektorNFT21"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
            <p class="text-xs text-slate-400 mt-1.5">Nama samaran yang tampil di Explorer menggantikan alamat wallet kamu. <b>Belum diverifikasi</b> (centang resmi hanya diberikan pengawas).</p>
        </div>

        <label class="flex items-start gap-3 cursor-pointer select-none">
            <input type="checkbox" name="explorer_public" value="1" @checked(old('explorer_public', $user->explorer_public))
                class="mt-0.5 w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-slate-700">
                Tampilkan nama publik di Explorer
                <span class="block text-xs text-slate-400 mt-0.5">Kalau dimatikan, kamu tetap tampil sebagai alamat wallet pendek (mis. <span class="font-mono">0x04f5…da323</span>).</span>
            </span>
        </label>

        <div class="border-t border-slate-100 pt-4">
            <p class="text-xs font-medium text-slate-500 mb-2">Yang publik vs privat</p>
            <ul class="text-xs text-slate-500 space-y-1.5">
                <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Nama publik (jika diaktifkan), wallet, saldo &amp; transaksi TLKM — <b class="text-slate-600">publik</b></li>
                <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Nama asli, email, telepon, alamat pengiriman — <b class="text-slate-600">selalu privat</b></li>
            </ul>
        </div>

        <div class="flex gap-3 pt-1">
            <a href="/products" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Simpan</button>
        </div>
    </form>

    @if($user->wallet_address)
        <p class="text-xs text-slate-400 mt-4">Wallet kamu:
            <a href="/explorer/{{ $user->wallet_address }}" class="font-mono text-blue-600 hover:underline">{{ $user->wallet_address }}</a>
            — lihat bagaimana profilmu tampil di Explorer.
        </p>
    @endif
</div>

@endsection

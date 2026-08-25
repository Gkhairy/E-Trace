@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <div class="flex items-center gap-2 mb-1">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h1 class="text-xl font-bold text-slate-900">2FA Aktif</h1>
        </div>
        <p class="text-sm text-slate-500 mb-5">Simpan <b>recovery codes</b> ini di tempat aman. Tiap kode sekali pakai — dipakai kalau kamu kehilangan akses ke aplikasi Authenticator.</p>

        <div class="grid grid-cols-2 gap-2 bg-slate-50 border border-slate-200 rounded-xl p-4 font-mono text-sm mb-5">
            @foreach($codes as $c)
                <span class="text-slate-700 select-all">{{ $c }}</span>
            @endforeach
        </div>

        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-5">Kode ini <b>tidak akan ditampilkan lagi</b>. Salin & simpan sekarang.</p>

        <a href="/profile" class="block text-center w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Selesai, saya sudah menyimpan</a>
    </div>
</div>
@endsection

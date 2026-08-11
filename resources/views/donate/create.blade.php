@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/donate" class="hover:text-blue-600 transition">Donasi</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Buat Campaign</span>
</nav>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Buat Campaign Donasi</h1>
    <p class="text-sm text-slate-500 mb-6">Donatur akan menyumbang TLKM ke campaign ini; dana disalurkan ke wallet penerima yang kamu tentukan.</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="/donate/campaigns" method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Judul</label>
            <input type="text" name="title" value="{{ old('title') }}" required maxlength="120" placeholder="mis. Bantuan untuk Palestina"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi</label>
            <textarea name="description" rows="4" placeholder="Ceritakan tujuan donasi ini…"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm resize-none">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Foto campaign</label>
            <input type="file" name="image" accept="image/*"
                class="text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs hover:file:bg-blue-700 file:cursor-pointer">
            <p class="text-[11px] text-slate-400 mt-1.5">JPG/PNG/WEBP, maks 3 MB. Rasio 16:9 paling pas.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Wallet penerima</label>
            <input type="text" name="recipient_wallet" value="{{ old('recipient_wallet') }}" required placeholder="0x…"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm font-mono">
            <p class="text-[11px] text-slate-400 mt-1.5">Alamat wallet yang menerima seluruh donasi saat disalurkan.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Target (opsional)</label>
            <div class="relative">
                <input type="number" name="goal_amount" value="{{ old('goal_amount') }}" min="0" step="any" placeholder="mis. 10000"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm pr-16">
                <span class="absolute right-4 top-2.5 text-sm text-slate-400">TLKM</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">Kosongkan bila tanpa target (progress bar tidak tampil).</p>
        </div>

        <div class="flex gap-3 pt-1">
            <a href="/donate" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Buat Campaign</button>
        </div>
    </form>
</div>

@endsection

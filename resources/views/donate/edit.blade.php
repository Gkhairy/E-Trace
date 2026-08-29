@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/donate" class="hover:text-blue-600 transition">Donasi</a>
    <span class="text-slate-300">/</span>
    <a href="/donate/{{ $campaign->slug }}" class="hover:text-blue-600 transition truncate max-w-[200px]">{{ $campaign->title }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Edit</span>
</nav>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Edit Campaign Donasi</h1>
    <p class="text-sm text-slate-500 mb-6">Perbarui detail campaign. Slug &amp; alamat on-chain campaign tidak berubah.</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="/donate/{{ $campaign->slug }}/update" method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Judul</label>
            <input type="text" name="title" value="{{ old('title', $campaign->title) }}" required maxlength="120"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi</label>
            <textarea name="description" rows="4"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm resize-none">{{ old('description', $campaign->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Foto campaign</label>
            @if($campaign->image)
                <img src="/campaign_images/{{ $campaign->image }}" class="w-40 rounded-xl border border-slate-200 mb-2 object-cover aspect-[16/9]">
            @endif
            <input type="file" name="image" accept="image/*"
                class="text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs hover:file:bg-blue-700 file:cursor-pointer">
            <p class="text-[11px] text-slate-400 mt-1.5">Kosongkan untuk mempertahankan gambar saat ini.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Wallet penerima</label>
            <input type="text" name="recipient_wallet" value="{{ old('recipient_wallet', $campaign->recipient_wallet) }}" required
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm font-mono">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Target (opsional)</label>
                <div class="relative">
                    <input type="number" name="goal_amount" value="{{ old('goal_amount', $campaign->goal_amount) }}" min="0" step="any"
                        class="w-full px-4 py-2.5 pr-16 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                    <span class="absolute right-4 top-2.5 text-sm text-slate-400">TLKM</span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
                    <option value="active" @selected(old('status', $campaign->status) === 'active')>Dibuka (menerima donasi)</option>
                    <option value="closed" @selected(old('status', $campaign->status) === 'closed')>Ditutup</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Batas waktu donasi (opsional)</label>
            <input type="date" name="closes_at" value="{{ old('closes_at', optional($campaign->closes_at)->format('Y-m-d')) }}"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
        </div>

        <div class="flex gap-3 pt-1">
            <a href="/donate/{{ $campaign->slug }}" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm">Simpan Perubahan</button>
        </div>
    </form>

    <form action="/donate/{{ $campaign->slug }}/delete" method="POST" class="mt-4" onsubmit="return confirm('Hapus campaign ini? Catatan donasi tetap tersimpan tapi tak lagi tertaut ke campaign.')">
        @csrf
        <button class="text-sm text-red-600 hover:underline">Hapus campaign ini</button>
    </form>
</div>

@endsection

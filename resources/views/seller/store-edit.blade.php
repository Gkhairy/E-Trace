@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/seller" class="hover:text-blue-600 transition">Dashboard Toko</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Edit Toko</span>
</nav>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Edit Toko</h1>
    <p class="text-sm text-slate-500 mb-6">Profil toko tampil ke pembeli. Wallet payout tidak bisa diubah di sini (terikat pembayaran on-chain).</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="/seller/store" method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Toko</label>
            <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi</label>
            <textarea name="description" rows="3" placeholder="Ceritakan tokomu…"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition resize-none">{{ old('description', $store->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Asal Pengiriman <span class="text-red-500">*</span></label>
            <textarea name="origin_address" rows="2" required placeholder="mis. Jl. Merdeka No.1, Bandung"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition resize-none">{{ old('origin_address', $store->origin_address) }}</textarea>
            <p class="text-[11px] text-slate-400 mt-1.5"><b>Wajib</b> — sertakan <b>nama kota</b> (mis. Bandung, Jakarta). Dipakai menghitung ongkir ke pembeli.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Notifikasi Order</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $store->contact_email) }}" placeholder="toko@contoh.com"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm transition">
            <p class="text-xs text-slate-400 mt-1.5">Ke sinilah pemberitahuan "ada pesanan baru" dikirim. Kosongkan untuk memakai email akunmu. <b>Privat</b> — tidak tampil ke pembeli/Explorer.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Logo</label>
                <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-200 overflow-hidden mb-2 flex items-center justify-center">
                    <img src="{{ $store->logo ? '/store_images/'.$store->logo : 'https://placehold.co/80x80/eff6ff/2563eb?text=Logo' }}" onerror="this.src='https://placehold.co/80x80/eff6ff/2563eb?text=Logo'" class="w-full h-full object-cover">
                </div>
                <input type="file" name="logo" accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs hover:file:bg-blue-700 file:cursor-pointer">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Banner</label>
                <div class="w-full h-16 rounded-xl bg-slate-50 border border-slate-200 overflow-hidden mb-2">
                    <img src="{{ $store->banner ? '/store_images/'.$store->banner : 'https://placehold.co/400x80/e0e7ff/6366f1?text=Banner' }}" onerror="this.src='https://placehold.co/400x80/e0e7ff/6366f1?text=Banner'" class="w-full h-full object-cover">
                </div>
                <input type="file" name="banner" accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs hover:file:bg-blue-700 file:cursor-pointer">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <a href="/seller" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Simpan</button>
        </div>
    </form>
</div>

@endsection

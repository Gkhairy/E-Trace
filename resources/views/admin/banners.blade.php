@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="/products" class="hover:text-blue-600 transition">Beranda</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Kelola Iklan</span>
</nav>

<div class="max-w-4xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Kelola Iklan (Banner Carousel)</h1>
    <p class="text-sm text-slate-500 mb-6">Banner aktif tampil di carousel halaman produk. Urutan diatur lewat kolom <b>Urutan</b> (kecil tampil dulu).</p>

    @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Tambah banner --}}
    <form action="/admin/banners" method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-8 space-y-5">
        @csrf
        <h2 class="font-bold text-slate-900">Tambah Iklan</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Judul (opsional)</label>
                <input name="title" value="{{ old('title') }}" placeholder="mis. Promo Spesial" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Urutan</label>
                <input name="sort" type="number" min="0" value="{{ old('sort', 0) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Tautan saat diklik (opsional)</label>
            <input name="link" type="url" value="{{ old('link') }}" placeholder="https://…" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Upload gambar</label>
                <input name="image" type="file" accept="image/*" class="text-sm text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs hover:file:bg-blue-700 file:cursor-pointer">
                <p class="text-[11px] text-slate-400 mt-1.5">Rasio lebar ~16:6 paling pas. Maks 4 MB.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">…atau URL gambar</label>
                <input name="image_url" type="url" value="{{ old('image_url') }}" placeholder="https://…/banner.jpg" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
            </div>
        </div>

        <button class="py-2.5 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm">Tambah Iklan</button>
    </form>

    {{-- Daftar banner --}}
    <h2 class="font-bold text-slate-900 mb-3">Iklan Saat Ini ({{ $banners->count() }})</h2>
    @if($banners->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center text-slate-500">Belum ada iklan. Tambahkan di atas.</div>
    @else
        <div class="space-y-3">
            @foreach($banners as $b)
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex items-center gap-4 p-3">
                    <img src="{{ $b->imageUrl() }}" onerror="this.src='https://placehold.co/240x90/f1f5f9/94a3b8?text=Banner'" class="w-40 h-16 object-cover rounded-lg bg-slate-100 shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $b->title ?: '(tanpa judul)' }}</p>
                        @if($b->link)<a href="{{ $b->link }}" target="_blank" class="text-xs text-blue-600 hover:underline truncate block">{{ $b->link }}</a>@endif
                        <p class="text-[11px] text-slate-400">Urutan {{ $b->sort }} ·
                            <span class="{{ $b->is_active ? 'text-green-600' : 'text-slate-400' }}">{{ $b->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <form action="/admin/banners/toggle" method="POST">@csrf<input type="hidden" name="id" value="{{ $b->id }}">
                            <button class="text-xs font-medium px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700">{{ $b->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                        <form action="/admin/banners/delete" method="POST" onsubmit="return confirmSubmit(event, {title: 'Hapus iklan?', message: 'Iklan ini akan hilang dari carousel di beranda.', confirmText: 'Hapus', danger: true})">@csrf<input type="hidden" name="id" value="{{ $b->id }}">
                            <button class="text-xs font-medium px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:border-red-300 hover:text-red-600">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection

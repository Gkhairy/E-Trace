@extends('layouts.app')

@section('content')
@php $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.'); @endphp

@include('seller._nav')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <p class="text-sm text-slate-600"><b class="text-slate-900 tabular-nums">{{ $products->total() }}</b> produk{{ $q !== '' ? ' cocok dengan "' . $q . '"' : ' di tokomu' }}</p>
    <form method="GET" action="/seller/products" class="relative w-full sm:w-72" role="search">
        <label for="pq" class="sr-only">Cari produk</label>
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5-5m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input id="pq" name="q" value="{{ $q }}" placeholder="Cari nama produk" class="w-full h-10 pl-9 pr-3 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
    </form>
</div>

@if($products->isEmpty())
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 px-6 py-16 text-center">
        @if($q !== '')
            <p class="font-semibold text-slate-800">Tidak ada produk bernama "{{ $q }}"</p>
            <a href="/seller/products" class="mt-3 inline-block text-sm font-semibold text-blue-700 hover:underline">Tampilkan semua produk</a>
        @else
            <p class="font-semibold text-slate-800">Tokomu belum punya produk</p>
            <p class="mt-1 text-sm text-slate-500">Tambahkan produk pertama: foto, nama, dan harga sudah cukup untuk mulai berjualan.</p>
            <a href="/products/create" class="mt-4 inline-flex items-center h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors">Tambah produk</a>
        @endif
    </div>
@else
    <ul class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($products as $p)
            @php
                $n = (int) ($sold[$p->id] ?? 0);
                [$stockText, $stockTone] = $p->stock === null ? ['Stok tak terbatas', 'text-slate-500']
                    : ($p->stock == 0 ? ['Stok habis', 'text-red-700 font-semibold']
                    : ($p->stock <= 3 ? ['Sisa ' . $p->stock, 'text-amber-700 font-semibold'] : ['Stok ' . $p->stock, 'text-slate-500']));
            @endphp
            <li class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm overflow-hidden flex flex-col hover:ring-slate-300 transition">
                <a href="/products/{{ $p->id }}" class="block aspect-square bg-slate-50 p-4" title="Lihat seperti pembeli">
                    <img src="{{ $p->thumbnail() ?? 'https://placehold.co/300x300/f1f5f9/94a3b8?text=-' }}" alt="{{ $p->name }}" loading="lazy" onerror="this.src='https://placehold.co/300x300/f1f5f9/94a3b8?text=-'" class="w-full h-full object-contain">
                </a>
                <div class="p-4 flex-1 flex flex-col">
                    @if($p->category)<p class="text-xs text-slate-500 truncate">{{ $p->category->name }}</p>@endif
                    <p class="font-semibold text-slate-900 line-clamp-2 leading-snug">{{ $p->name }}</p>
                    <p class="mt-1 font-bold text-slate-900 tabular-nums">{{ $fmt($p->price_usdc) }} <span class="text-xs font-semibold text-blue-700">TLKM</span></p>
                    <p class="mt-2 text-xs flex flex-wrap gap-x-3 gap-y-0.5">
                        <span class="{{ $stockTone }}">{{ $stockText }}</span>
                        <span class="text-slate-500">{{ $n }} terjual</span>
                    </p>
                    <div class="mt-auto pt-4 flex gap-2">
                        <a href="/products/{{ $p->id }}/edit" class="flex-1 inline-flex items-center justify-center h-9 rounded-lg bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">Ubah</a>
                        <form action="/products/{{ $p->id }}/delete" method="POST"
                              onsubmit="return confirmSubmit(event, {title: 'Hapus produk?', message: @js('"' . $p->name . '" akan dihapus dari toko. Tindakan ini tidak bisa dibatalkan.'), confirmText: 'Hapus produk', danger: true})">
                            @csrf
                            <button aria-label="Hapus {{ $p->name }}" class="h-9 w-9 inline-flex items-center justify-center rounded-lg ring-1 ring-slate-200 text-slate-500 hover:text-red-700 hover:ring-red-200 hover:bg-red-50 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m5 4v6m4-6v6M15 7V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="mt-6">{{ $products->links() }}</div>
@endif
@endsection

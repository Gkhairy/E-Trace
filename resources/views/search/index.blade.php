@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Hasil pencarian</h1>
    @if($q !== '')
        <p class="text-sm text-slate-500 mt-1">untuk “<span class="font-medium text-slate-700">{{ $q }}</span>” —
            {{ $products->count() }} produk, {{ $stores->count() }} toko, {{ $people->count() }} orang</p>
    @endif
</div>

@if($q === '')
    <div class="bg-white border border-dashed border-slate-300 rounded-3xl p-16 text-center text-slate-500">Ketik kata kunci di kotak pencarian untuk mencari produk, toko, atau orang (nama publik / wallet).</div>
@elseif($products->isEmpty() && $stores->isEmpty() && $people->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-3xl p-16 text-center text-slate-500">
        Tidak ada hasil untuk “{{ $q }}”. Coba kata kunci lain.
    </div>
@else
    {{-- ORANG --}}
    @if($people->isNotEmpty())
        <h2 class="text-sm font-bold text-slate-900 mb-3">Orang</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @foreach($people as $p)
                <a href="/explorer/{{ $p['wallet'] }}" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-blue-400 hover:shadow-md transition">
                    <div class="flex items-center gap-1.5">
                        <span class="font-semibold text-slate-900 truncate">{{ $p['name'] }}</span>
                        @if($p['verified'])<svg class="w-4 h-4 text-blue-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>@endif
                    </div>
                    <p class="text-[11px] text-slate-400 font-mono mt-1 truncate">{{ substr($p['wallet'],0,10) }}…{{ substr($p['wallet'],-6) }}</p>
                </a>
            @endforeach
        </div>
    @endif

    {{-- TOKO --}}
    @if($stores->isNotEmpty())
        <h2 class="text-sm font-bold text-slate-900 mb-3">Toko</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @foreach($stores as $s)
                <a href="/store/{{ $s->slug }}" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-blue-400 hover:shadow-md transition">
                    <p class="font-semibold text-slate-900 truncate">🏪 {{ $s->name }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $s->products_count }} produk</p>
                </a>
            @endforeach
        </div>
    @endif

    {{-- PRODUK --}}
    @if($products->isNotEmpty())
        <h2 class="text-sm font-bold text-slate-900 mb-3">Produk</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
            @foreach($products as $product)
                <a href="/products/{{ $product->id }}" class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-blue-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col">
                    <div class="relative aspect-square bg-white flex items-center justify-center p-3 border-b border-slate-100">
                        <img src="{{ $product->imageUrl() ?? 'https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image' }}" onerror="this.src='https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image'" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        <span class="absolute top-2 left-2 bg-blue-600 text-[10px] font-semibold px-2 py-1 rounded-full text-white shadow-sm">TLKM</span>
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        <h3 class="font-semibold text-sm leading-snug line-clamp-2 text-slate-800 group-hover:text-blue-600 transition">{{ $product->name }}</h3>
                        <p class="mt-auto pt-3 text-lg font-extrabold text-slate-900">{{ $fmt($product->price_usdc) }} <span class="text-xs text-blue-600 font-semibold">TLKM</span></p>
                        @if($product->store)<p class="text-[11px] text-slate-500 mt-0.5 truncate">{{ $product->store->name }}</p>@endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endif

@endsection

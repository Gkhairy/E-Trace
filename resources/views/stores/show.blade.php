@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="/products" class="hover:text-blue-600 transition">Produk</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700 truncate">{{ $store->name }}</span>
</nav>

{{-- ===== HEADER TOKO ===== --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
    <div class="h-28 md:h-40 bg-blue-600">
        @if($store->banner)
            <img src="/store_images/{{ $store->banner }}" alt="{{ $store->name }}" class="w-full h-full object-cover">
        @endif
    </div>
    <div class="px-5 md:px-8 pb-5">
        <div class="flex flex-wrap items-end gap-4 -mt-10">
            <div class="w-20 h-20 rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex items-center justify-center shrink-0">
                @if($store->logo)
                    <img src="/store_images/{{ $store->logo }}" alt="{{ $store->name }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-9 h-9 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7l2-4h14l2 4M3 7h18M3 7v13a1 1 0 001 1h16a1 1 0 001-1V7"/></svg>
                @endif
            </div>
            <div class="flex-1 min-w-0 pb-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ $store->name }}</h1>
                    @if($identity['verified'])
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                            Terverifikasi
                        </span>
                    @endif
                    @if($store->status === 'active')
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">Aktif</span>
                    @endif
                </div>
                <a href="/explorer/{{ $store->payout_wallet }}" class="text-xs text-blue-600 hover:underline font-mono">{{ substr($store->payout_wallet, 0, 10) }}…{{ substr($store->payout_wallet, -6) }} ↗</a>
            </div>
            <div class="flex gap-6 text-center pb-1">
                <div><p class="text-lg font-extrabold text-slate-900">{{ $products->count() }}</p><p class="text-[11px] text-slate-400">Produk</p></div>
                <div><p class="text-lg font-extrabold text-slate-900">{{ $sold }}</p><p class="text-[11px] text-slate-400">Terjual</p></div>
                <div>
                    <p class="text-lg font-extrabold {{ $ratingAvg !== null ? 'text-amber-500' : 'text-slate-300' }}">{{ $ratingAvg !== null ? '★ '.$ratingAvg : '—' }}</p>
                    <p class="text-[11px] text-slate-400">{{ $ratingCount }} ulasan</p>
                </div>
            </div>
        </div>

        @if($store->description)
            <p class="text-sm text-slate-600 mt-4 max-w-3xl whitespace-pre-line leading-relaxed">{{ $store->description }}</p>
        @endif
        @if($store->origin_address)
            <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Dikirim dari {{ $store->origin_address }}
            </p>
        @endif
    </div>
</div>

{{-- ===== ULASAN GABUNGAN (dari semua produk toko) — F1 ===== --}}
@if($reviews->isNotEmpty())
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-900">Ulasan Pembeli</h2>
            <span class="text-xs text-slate-400">
                @if($ratingAvg !== null)<span class="text-amber-500 font-semibold">★ {{ $ratingAvg }}</span> · @endif{{ $ratingCount }} ulasan (semua produk)
            </span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($reviews as $rv)
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-slate-800 truncate">{{ $rv['reviewer'] }}</span>
                                <span class="text-amber-500 text-xs whitespace-nowrap">{!! str_repeat('★', $rv['rating']) . str_repeat('☆', 5 - $rv['rating']) !!}</span>
                            </div>
                            @if($rv['product'])
                                <a href="/products/{{ $rv['product']->id }}" class="text-[11px] text-blue-600 hover:underline">{{ $rv['product']->name }}</a>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400 shrink-0">{{ $rv['at']->translatedFormat('d M Y') }}</span>
                    </div>
                    @if($rv['comment'])
                        <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">{{ $rv['comment'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ===== PRODUK TOKO ===== --}}
<h2 class="text-lg font-bold text-slate-900 mb-4">Produk Toko</h2>
@if($products->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-3xl p-16 text-center text-slate-500">Toko ini belum menjual produk.</div>
@else
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        @foreach($products as $product)
            @php $soldOut = $product->stock !== null && $product->stock <= 0; @endphp
            <a href="/products/{{ $product->id }}" class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-blue-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col">
                <div class="relative aspect-square bg-white flex items-center justify-center p-3 border-b border-slate-100">
                    <img src="{{ $product->imageUrl() ?? 'https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image' }}" onerror="this.src='https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image'" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                    <span class="absolute top-2 left-2 bg-blue-600 text-[10px] font-semibold px-2 py-1 rounded-full text-white shadow-sm">TLKM</span>
                    @if($soldOut)<span class="absolute inset-0 bg-white/70 flex items-center justify-center text-sm font-bold text-slate-500">Stok Habis</span>@endif
                </div>
                <div class="p-4 flex flex-col flex-1">
                    <h3 class="font-semibold text-sm leading-snug line-clamp-2 text-slate-800 group-hover:text-blue-600 transition">{{ $product->name }}</h3>
                    <p class="mt-auto pt-3 text-lg font-extrabold text-slate-900">{{ rtrim(rtrim(number_format($product->price_usdc, 2), '0'), '.') }} <span class="text-xs text-blue-600 font-semibold">TLKM</span></p>
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection

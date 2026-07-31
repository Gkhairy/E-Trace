@extends('layouts.app')

@section('content')

{{-- ===== HERO BANNER (banner berwarna, teks putih — disengaja) ===== --}}
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 via-indigo-600 to-indigo-700 mb-8 shadow-sm">
    <div class="absolute -right-16 -top-16 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute -left-10 -bottom-16 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
    <div class="relative px-6 md:px-12 py-10 md:py-14 max-w-2xl">
        <span class="inline-flex items-center gap-2 text-xs font-medium bg-white/15 border border-white/20 rounded-full px-3 py-1 text-white mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-white"></span> Pembayaran on-chain • Transparan
        </span>
        <h1 class="text-3xl md:text-5xl font-extrabold leading-tight tracking-tight text-white">
            Belanja dengan <span class="text-yellow-300">TLKM</span>,
            <br class="hidden md:block">setiap transaksi tercatat blockchain.
        </h1>
        <p class="text-blue-100 mt-4 text-sm md:text-base max-w-lg">
            Bayar aman lewat smart contract escrow. Dana baru lepas ke penjual setelah kamu konfirmasi barang diterima.
        </p>
        <div class="flex flex-wrap gap-3 mt-6">
            <a href="#katalog" class="bg-white text-blue-700 hover:bg-blue-50 px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">Lihat Produk</a>
            <a href="/orders" class="bg-white/15 hover:bg-white/25 border border-white/25 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition">Riwayat Order</a>
        </div>
    </div>
</section>

{{-- ===== TRUST STRIP ===== --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-10">
    @php
        $badges = [
            ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'Escrow Aman', 'Dana ditahan kontrak'],
            ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'Terverifikasi', 'Cek di block explorer'],
            ['M13 10V3L4 14h7v7l9-11h-7z', 'Instan', 'Bayar langsung on-chain'],
            ['M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'Token TLKM', 'ERC-20 di Sepolia'],
        ];
    @endphp
    @foreach($badges as $b)
        <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $b[0] }}"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ $b[1] }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $b[2] }}</p>
            </div>
        </div>
    @endforeach
</div>

{{-- ===== HEADER KATALOG ===== --}}
<div id="katalog" class="mb-6 scroll-mt-28">
    <h2 class="text-2xl font-bold text-slate-900">Katalog Produk</h2>
    <p class="text-sm text-slate-500">{{ $products->count() }} produk tersedia</p>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
@endif

{{-- ===== GRID PRODUK ===== --}}
@if($products->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center bg-white border border-dashed border-slate-300 rounded-3xl">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <p class="text-slate-600 font-medium">Belum ada produk</p>
        <p class="text-sm text-slate-400 mt-1">Produk yang ditambahkan admin akan muncul di sini.</p>
    </div>
@else
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        @foreach($products as $product)
            <div class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-blue-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col">
                <a href="/products/{{ $product->id }}" class="flex flex-col flex-1">
                    {{-- Foto di latar PUTIH, object-contain agar warna akurat & tidak terpotong --}}
                    <div class="relative aspect-square bg-white flex items-center justify-center p-3 border-b border-slate-100">
                        <img src="{{ $product->image ? '/product_images/'.$product->image : 'https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image' }}"
                             onerror="this.src='https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image'"
                             class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        <span class="absolute top-2 left-2 bg-blue-600 text-[10px] font-semibold px-2 py-1 rounded-full text-white shadow-sm">TLKM</span>
                        @if($product->stock !== null && $product->stock <= 0)
                            <span class="absolute inset-0 bg-white/70 flex items-center justify-center text-sm font-bold text-slate-500">Stok Habis</span>
                        @endif
                    </div>
                    <div class="p-4 pb-2 flex flex-col flex-1">
                        <h3 class="font-semibold text-sm md:text-base leading-snug line-clamp-2 text-slate-800 group-hover:text-blue-600 transition">{{ $product->name }}</h3>
                        <div class="flex items-center gap-1 mt-1 text-amber-400 text-xs">
                            {!! str_repeat('<svg class="w-3.5 h-3.5 inline" fill="currentColor" viewBox="0 0 20 20"><path d="M9.05 2.9c.3-.9 1.6-.9 1.9 0l1.3 4a1 1 0 00.95.7h4.2c.97 0 1.37 1.24.6 1.8l-3.4 2.5a1 1 0 00-.36 1.1l1.3 4c.3.9-.74 1.65-1.5 1.1l-3.4-2.5a1 1 0 00-1.18 0l-3.4 2.5c-.77.55-1.8-.2-1.5-1.1l1.3-4a1 1 0 00-.36-1.1L2.1 9.4c-.77-.56-.37-1.8.6-1.8h4.2a1 1 0 00.95-.7l1.3-4z"/></svg>', 5) !!}
                            <span class="text-slate-400 ml-1">(5.0)</span>
                        </div>
                        <div class="mt-auto pt-3">
                            <p class="text-lg font-extrabold text-slate-900">{{ rtrim(rtrim(number_format($product->price_usdc, 2), '0'), '.') }} <span class="text-xs text-blue-600 font-semibold">TLKM</span></p>
                            @if($product->store)
                            <p class="text-[11px] text-slate-500 mt-0.5 truncate flex items-center gap-1">
                                <svg class="w-3 h-3 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l2-4h14l2 4M3 7h18M3 7v13a1 1 0 001 1h16a1 1 0 001-1V7"/></svg>
                                {{ $product->store->name }}
                            </p>
                        @else
                            <p class="text-[11px] text-slate-400 font-mono mt-0.5 truncate">Seller {{ substr($product->seller_wallet, 0, 6) }}…{{ substr($product->seller_wallet, -4) }}</p>
                        @endif
                        </div>
                    </div>
                </a>
                <div class="px-4 pb-4">
                    @php $soldOut = $product->stock !== null && $product->stock <= 0; @endphp
                    @if($soldOut)
                        <button disabled class="w-full py-2 rounded-xl text-sm font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed">Stok Habis</button>
                    @elseif(auth()->check())
                        <button onclick="addToCart({{ $product->id }}, this)"
                            class="w-full inline-flex items-center justify-center gap-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 py-2 rounded-xl text-sm font-semibold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Keranjang
                        </button>
                    @else
                        <a href="/login?next={{ urlencode(url('/products/'.$product->id)) }}"
                            class="w-full inline-flex items-center justify-center gap-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 py-2 rounded-xl text-sm font-semibold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Keranjang
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection

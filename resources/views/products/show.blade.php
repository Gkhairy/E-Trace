@extends('layouts.app')

@section('content')

@php
    $priceFmt = rtrim(rtrim(number_format($product->price_usdc, 2), '0'), '.');
    $gallery = $product->images();
    if (empty($gallery)) { $gallery = ['https://placehold.co/600x600/f1f5f9/94a3b8?text=No+Image']; }
    $img = $gallery[0];
@endphp

{{-- ===== BREADCRUMB ===== --}}
<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/products" class="hover:text-blue-600 transition">Produk</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700 truncate max-w-[200px]">{{ $product->name }}</span>
</nav>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

    {{-- ===== GALERI (kiri) — carousel yang bisa di-slide ===== --}}
    <div class="lg:col-span-5">
        <div class="relative group bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden aspect-square flex items-center justify-center p-6">
            <img id="mainImg" src="{{ $img }}"
                 onerror="this.src='https://placehold.co/600x600/f1f5f9/94a3b8?text=No+Image'"
                 class="max-w-full max-h-full object-contain">

            @if(count($gallery) > 1)
                {{-- Panah prev/next --}}
                <button type="button" onclick="galPrev()" aria-label="Sebelumnya"
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 border border-slate-200 shadow-sm flex items-center justify-center text-slate-600 hover:bg-white opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" onclick="galNext()" aria-label="Berikutnya"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 border border-slate-200 shadow-sm flex items-center justify-center text-slate-600 hover:bg-white opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                {{-- Indikator jumlah --}}
                <span id="galCounter" class="absolute bottom-3 right-3 text-[11px] font-medium bg-slate-900/60 text-white rounded-full px-2 py-0.5">1 / {{ count($gallery) }}</span>
            @endif
        </div>

        @if(count($gallery) > 1)
            <div class="flex gap-3 mt-3 overflow-x-auto pb-1">
                @foreach($gallery as $i => $u)
                    <button type="button" onclick="galGo({{ $i }})" data-thumb="{{ $i }}"
                        class="shrink-0 w-16 h-16 rounded-xl overflow-hidden bg-white flex items-center justify-center p-1 border-2 {{ $i === 0 ? 'border-blue-500' : 'border-slate-200' }} transition">
                        <img src="{{ $u }}" onerror="this.src='https://placehold.co/100x100/f1f5f9/94a3b8?text=—'" class="max-w-full max-h-full object-contain">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ===== INFO (tengah) ===== --}}
    <div class="lg:col-span-4">
        <h1 class="text-2xl md:text-3xl font-bold leading-tight text-slate-900">{{ $product->name }}</h1>

        <div class="flex items-center gap-2 mt-2">
            @php $revs = $product->reviews; $avg = $revs->count() ? round($revs->avg('rating'), 1) : 0; $cnt = $revs->count(); @endphp
            <div class="flex items-center gap-0.5">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= round($avg) ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.05 2.9c.3-.9 1.6-.9 1.9 0l1.3 4a1 1 0 00.95.7h4.2c.97 0 1.37 1.24.6 1.8l-3.4 2.5a1 1 0 00-.36 1.1l1.3 4c.3.9-.74 1.65-1.5 1.1l-3.4-2.5a1 1 0 00-1.18 0l-3.4 2.5c-.77.55-1.8-.2-1.5-1.1l1.3-4a1 1 0 00-.36-1.1L2.1 9.4c-.77-.56-.37-1.8.6-1.8h4.2a1 1 0 00.95-.7l1.3-4z"/></svg>
                @endfor
            </div>
            <span class="text-sm text-slate-500">{{ $cnt ? $avg.' ('.$cnt.' ulasan)' : 'Belum ada ulasan' }}</span>
        </div>

        <div class="h-px bg-slate-200 my-5"></div>

        <h3 class="text-sm font-semibold text-slate-700 mb-2">Deskripsi</h3>
        <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $product->description ?: 'Tidak ada deskripsi untuk produk ini.' }}</p>

        <div class="h-px bg-slate-200 my-5"></div>

        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Toko</span>
                @if($product->store)
                    <a href="/store/{{ $product->store->slug }}" class="font-medium text-blue-600 hover:underline flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l2-4h14l2 4M3 7h18M3 7v13a1 1 0 001 1h16a1 1 0 001-1V7"/></svg>
                        {{ $product->store->name }}
                        <span class="text-slate-400">›</span>
                    </a>
                @else
                    <span class="font-medium text-slate-800">Toko</span>
                @endif
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Wallet penjual</span>
                <a href="https://sepolia.etherscan.io/address/{{ $product->seller_wallet }}" target="_blank" rel="noopener"
                   class="font-mono text-blue-600 hover:underline text-xs">{{ substr($product->seller_wallet, 0, 8) }}…{{ substr($product->seller_wallet, -6) }} ↗</a>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Metode bayar</span>
                <span class="text-slate-700 font-medium">Token TLKM (ERC-20)</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Jaringan</span>
                <span class="text-slate-700 font-medium">Ethereum Sepolia</span>
            </div>
        </div>
    </div>

    {{-- ===== BUY BOX (kanan, sticky) ===== --}}
    <div class="lg:col-span-3">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 lg:sticky lg:top-28">
            <p class="text-xs text-slate-500 mb-1">Harga</p>
            <div class="flex items-end gap-1.5">
                <span class="text-3xl font-extrabold text-slate-900">{{ $priceFmt }}</span>
                <span class="text-sm font-semibold text-blue-600 mb-1">TLKM</span>
            </div>

            <div class="flex items-center gap-2 text-xs text-green-700 mt-3 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Dilindungi escrow smart contract
            </div>

            @php $soldOut = $product->stock !== null && $product->stock <= 0; @endphp
            @if($product->stock !== null)
                <p class="mt-3 text-xs {{ $soldOut ? 'text-red-600 font-medium' : 'text-slate-500' }}">{{ $soldOut ? 'Stok habis' : 'Stok: '.$product->stock.' tersedia' }}</p>
            @endif

            @if($soldOut)
                <button disabled class="mt-4 w-full bg-slate-100 text-slate-400 border border-slate-200 py-3 rounded-xl text-sm font-bold cursor-not-allowed">Stok Habis</button>
            @else
            @auth
                {{-- Jumlah --}}
                <div class="mt-4 flex items-center gap-3">
                    <span class="text-sm text-slate-500">Jumlah</span>
                    <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden">
                        <button type="button" onclick="qtyStep(-1)" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100">−</button>
                        <input id="qty" type="number" min="1" value="1" class="w-12 text-center border-x border-slate-200 outline-none text-sm py-1.5">
                        <button type="button" onclick="qtyStep(1)" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100">+</button>
                    </div>
                </div>

                {{-- Beli Langsung: masuk keranjang lalu ke checkout --}}
                <button onclick="beliLangsung()"
                    class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Beli Langsung
                </button>
                {{-- + Keranjang --}}
                <button onclick="tambahKeranjang(this)"
                    class="mt-2 w-full bg-white hover:bg-blue-50 border border-blue-200 text-blue-700 py-2.5 rounded-xl text-sm font-semibold transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3M17 13l2.3 2.3M9 20a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    + Keranjang
                </button>

                {{-- Chat penjual + Nawar harga --}}
                @if($sellerId && $sellerId !== auth()->id())
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <button onclick="startChat({{ $sellerId }}, @js($product->store->name ?? 'Penjual'))"
                            class="w-full bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 py-2.5 rounded-xl text-sm font-semibold transition flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5M21 12a8 8 0 01-11.6 7.1L4 20l1-4.3A8 8 0 1121 12z"/></svg>
                            Chat Penjual
                        </button>
                        <button onclick="nawarProduct({{ $product->id }}, @js($product->name))"
                            class="w-full bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 py-2.5 rounded-xl text-sm font-semibold transition flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5a2 2 0 011.41.59l7 7a2 2 0 010 2.82l-5.18 5.18a2 2 0 01-2.82 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                            Nawar Harga
                        </button>
                    </div>
                @endif
            @else
                {{-- Guest: minta login dulu, simpan tujuan kembali ke produk ini (intended) --}}
                <a href="/login?next={{ urlencode(url()->current()) }}"
                    class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Login untuk membeli
                </a>
                <p class="mt-2 text-xs text-slate-500 text-center">Kamu perlu masuk & menghubungkan wallet untuk belanja.</p>
            @endauth
            @endif

            <div class="mt-4 space-y-2 text-xs text-slate-500">
                <div class="flex items-start gap-2"><span class="text-slate-400 font-semibold">1.</span> Masuk keranjang / beli langsung.</div>
                <div class="flex items-start gap-2"><span class="text-slate-400 font-semibold">2.</span> Checkout & bayar sekali → dana masuk escrow.</div>
                <div class="flex items-start gap-2"><span class="text-slate-400 font-semibold">3.</span> Konfirmasi terima → dana lepas ke penjual.</div>
            </div>
        </div>
    </div>
</div>

{{-- ===== ULASAN PEMBELI ===== --}}
<div class="mt-10 max-w-3xl">
    <h2 class="text-lg font-bold text-slate-900 mb-4">Ulasan Pembeli @if($cnt)<span class="text-slate-400 font-normal text-sm">· {{ $avg }}/5 dari {{ $cnt }} ulasan</span>@endif</h2>
    @if($revs->isEmpty())
        <p class="text-sm text-slate-400">Belum ada ulasan untuk produk ini.</p>
    @else
        <div class="space-y-3">
            @foreach($revs->sortByDesc('created_at') as $r)
                <div class="bg-white border border-slate-200 rounded-2xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700">{{ $r->user->name ?? 'Pembeli' }}</span>
                        <div class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-3.5 h-3.5 {{ $i <= $r->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.05 2.9c.3-.9 1.6-.9 1.9 0l1.3 4a1 1 0 00.95.7h4.2c.97 0 1.37 1.24.6 1.8l-3.4 2.5a1 1 0 00-.36 1.1l1.3 4c.3.9-.74 1.65-1.5 1.1l-3.4-2.5a1 1 0 00-1.18 0l-3.4 2.5c-.77.55-1.8-.2-1.5-1.1l1.3-4a1 1 0 00-.36-1.1L2.1 9.4c-.77-.56-.37-1.8.6-1.8h4.2a1 1 0 00.95-.7l1.3-4z"/></svg>
                            @endfor
                        </div>
                    </div>
                    @if($r->comment)<p class="text-sm text-slate-600 mt-2">{{ $r->comment }}</p>@endif
                    <p class="text-[11px] text-slate-400 mt-2">{{ $r->created_at->format('d M Y') }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
const PRODUCT_DB_ID = @json($product->id);

// ===== Galeri produk (carousel) =====
const GALLERY = @json($gallery);
let galIdx = 0;
function galRender() {
    const main = document.getElementById('mainImg');
    if (main) main.src = GALLERY[galIdx];
    const c = document.getElementById('galCounter');
    if (c) c.textContent = `${galIdx + 1} / ${GALLERY.length}`;
    document.querySelectorAll('[data-thumb]').forEach(el => {
        const on = Number(el.dataset.thumb) === galIdx;
        el.classList.toggle('border-blue-500', on);
        el.classList.toggle('border-slate-200', !on);
    });
}
function galGo(i) { galIdx = (i + GALLERY.length) % GALLERY.length; galRender(); }
function galPrev() { galGo(galIdx - 1); }
function galNext() { galGo(galIdx + 1); }

function qtyStep(d) {
    const el = document.getElementById('qty');
    el.value = Math.max(1, (parseInt(el.value) || 1) + d);
}
function getQty() {
    return Math.max(1, parseInt(document.getElementById('qty').value) || 1);
}

// + Keranjang (tetap di halaman, tampilkan toast)
function tambahKeranjang(btn) {
    addToCart(PRODUCT_DB_ID, btn, { quantity: getQty() });
}

// Beli Langsung: masuk keranjang lalu ke halaman checkout
function beliLangsung() {
    addToCart(PRODUCT_DB_ID, null, { quantity: getQty(), redirect: '/checkout' });
}
</script>
@endsection

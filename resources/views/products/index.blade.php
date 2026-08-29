@extends('layouts.app')

@section('content')

{{-- ===== HERO CAROUSEL (slide 1 = hero; slide 2+ = iklan admin) ===== --}}
@php $slideCount = 1 + $banners->count(); @endphp
<div id="heroCarousel" class="relative overflow-hidden rounded-2xl mb-8 group">
    <div id="heroTrack" class="flex transition-transform duration-500 ease-out" style="transform: translateX(0%)">
        {{-- Slide bawaan --}}
        <section class="w-full shrink-0 bg-blue-600 min-h-[280px] md:min-h-[340px] flex items-center">
            <div class="px-6 md:px-12 py-10 md:py-14 max-w-2xl">
                <span class="inline-flex items-center gap-2 text-xs font-medium bg-white/15 border border-white/20 rounded-full px-3 py-1 text-white mb-4">
                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span> {{ __('catalog.hero_badge') }}
                </span>
                <h1 class="text-3xl md:text-5xl font-extrabold leading-tight tracking-tight text-white">
                    {{ __('catalog.hero_title_1') }} <span class="text-yellow-300">TLKM</span>,
                    <br class="hidden md:block">{{ __('catalog.hero_title_2') }}
                </h1>
                <p class="text-blue-100 mt-4 text-sm md:text-base max-w-lg">{{ __('catalog.hero_sub') }}</p>
                <div class="flex flex-wrap gap-3 mt-6">
                    <a href="#katalog" class="bg-white text-blue-700 hover:bg-blue-50 px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">{{ __('catalog.see_products') }}</a>
                    <a href="/orders" class="bg-white/15 hover:bg-white/25 border border-white/25 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition">{{ __('catalog.order_history') }}</a>
                </div>
            </div>
        </section>
        {{-- Slide iklan (dikelola admin) --}}
        @foreach($banners as $b)
            <a href="{{ $b->link ?: '#' }}" @if($b->link) target="_blank" rel="noopener" @endif class="w-full shrink-0 min-h-[280px] md:min-h-[340px] bg-slate-100 block">
                <img src="{{ $b->imageUrl() }}" alt="{{ $b->title }}" class="w-full h-full min-h-[280px] md:min-h-[340px] object-cover">
            </a>
        @endforeach
    </div>

    @if($slideCount > 1)
        {{-- Panah --}}
        <button type="button" onclick="heroStep(-1)" aria-label="Sebelumnya" class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/80 hover:bg-white text-slate-700 shadow flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" onclick="heroStep(1)" aria-label="Berikutnya" class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/80 hover:bg-white text-slate-700 shadow flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        {{-- Titik --}}
        <div id="heroDots" class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
            @for($i = 0; $i < $slideCount; $i++)
                <button type="button" onclick="heroGo({{ $i }})" data-dot class="w-2 h-2 rounded-full bg-white/50 hover:bg-white transition"></button>
            @endfor
        </div>
    @endif

    @auth
        @if(auth()->user()->isSupervisor())
            <a href="/admin/banners" class="absolute top-3 right-3 inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg bg-white/90 hover:bg-white text-slate-700 shadow transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/></svg>
                Kelola Iklan
            </a>
        @endif
    @endauth
</div>

@if($slideCount > 1)
<script>
(function () {
    const track = document.getElementById('heroTrack');
    const dots = document.querySelectorAll('#heroDots [data-dot]');
    const count = {{ $slideCount }};
    let idx = 0, timer = null;
    window.heroGo = function (i) {
        idx = (i + count) % count;
        track.style.transform = 'translateX(-' + (idx * 100) + '%)';
        dots.forEach((d, k) => d.classList.toggle('!bg-white', k === idx));
        dots.forEach((d, k) => d.classList.toggle('w-4', k === idx));
        restart();
    };
    window.heroStep = (d) => heroGo(idx + d);
    function restart() { if (timer) clearInterval(timer); timer = setInterval(() => heroGo(idx + 1), 5000); }
    heroGo(0);
})();
</script>
@endif

{{-- ===== PINTASAN IKON (ala Shopee) ===== --}}
@php
    $u = auth()->user();
    $sc = [
        ['/explorer', __('catalog.sc_explorer'), 'bg-indigo-50 text-indigo-600', 'M11 3a8 8 0 105.29 14.29l4.7 4.71 1.42-1.42-4.71-4.7A8 8 0 0011 3zm0 2a6 6 0 110 12 6 6 0 010-12z'],
        ['/donate', __('catalog.sc_donate'), 'bg-rose-50 text-rose-600', 'M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 000-7.78z'],
    ];
    if ($u) {
        $sc[] = ['/wallet', __('catalog.sc_wallet'), 'bg-blue-50 text-blue-600', 'M3 10h18M7 15h.01M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z'];
        $sc[] = ['/orders', __('catalog.sc_orders'), 'bg-amber-50 text-amber-600', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'];
        $sc[] = ['/addresses', __('catalog.sc_address'), 'bg-emerald-50 text-emerald-600', 'M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10zM12 11a2 2 0 100-4 2 2 0 000 4z'];
        $sc[] = ['/community', __('catalog.sc_community'), 'bg-violet-50 text-violet-600', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-6a3 3 0 11-3 3'];
        if ($u->isSeller())     $sc[] = ['/seller', __('catalog.sc_store'), 'bg-orange-50 text-orange-600', 'M3 7l2-4h14l2 4M3 7h18M3 7v13a1 1 0 001 1h16a1 1 0 001-1V7'];
        if ($u->isSupervisor()) $sc[] = ['/supervisor/disputes', __('catalog.sc_supervisor'), 'bg-violet-50 text-violet-600', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'];
        $sc[] = ['/profile', __('catalog.sc_profile'), 'bg-slate-100 text-slate-600', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'];
    } else {
        $sc[] = ['/login', __('catalog.sc_login'), 'bg-blue-50 text-blue-600', 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'];
        $sc[] = ['/register', __('catalog.sc_register'), 'bg-emerald-50 text-emerald-600', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h9m5-3h-4m2-2v4'];
    }
@endphp
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-8">
    <div class="flex flex-wrap justify-center gap-x-6 sm:gap-x-10 gap-y-5">
        @foreach($sc as $s)
            <a href="{{ $s[0] }}" class="group flex flex-col items-center gap-2 text-center w-16">
                <span class="w-12 h-12 rounded-2xl {{ $s[2] }} flex items-center justify-center group-hover:scale-105 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $s[3] }}"/></svg>
                </span>
                <span class="text-[11px] text-slate-600 leading-tight">{{ $s[1] }}</span>
            </a>
        @endforeach
    </div>
</div>

{{-- ===== TRUST STRIP ===== --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-10">
    @php
        $badges = [
            ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', __('catalog.trust_escrow_t'), __('catalog.trust_escrow_s')],
            ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', __('catalog.trust_verified_t'), __('catalog.trust_verified_s')],
            ['M13 10V3L4 14h7v7l9-11h-7z', __('catalog.trust_instant_t'), __('catalog.trust_instant_s')],
            ['M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', __('catalog.trust_token_t'), __('catalog.trust_token_s')],
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

{{-- ===== KATEGORI (baris ikon, ala Tokopedia/Shopee) — tampil 6 + lihat lebih banyak ===== --}}
@if($categories->isNotEmpty())
@php $visible = 6; @endphp
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-bold text-slate-900">{{ __('catalog.categories') }}</h2>
        @if($activeCategory)
            <a href="/products" class="text-xs text-blue-600 hover:underline">{{ __('catalog.show_all') }}</a>
        @endif
    </div>
    <div id="catGrid" class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-7 gap-3">
        <a href="/products" class="group flex flex-col items-center gap-1.5 text-center">
            <span class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl transition {{ !$activeCategory ? 'bg-blue-600 text-white' : 'bg-slate-100 group-hover:bg-blue-50' }}">🛍️</span>
            <span class="text-[10px] leading-tight {{ !$activeCategory ? 'text-blue-600 font-semibold' : 'text-slate-600' }}">{{ __('catalog.all') }}</span>
        </a>
        @foreach($categories as $i => $cat)
            @php $on = $activeCategory && $activeCategory->id === $cat->id; @endphp
            {{-- Kategori ke-7 dst disembunyikan sampai "lihat lebih banyak" (kecuali yang sedang aktif) --}}
            <a href="/products?category={{ $cat->slug }}"
               class="cat-item group flex flex-col items-center gap-1.5 text-center {{ ($i >= $visible && !$on) ? 'cat-extra hidden' : '' }}">
                <span class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl transition {{ $on ? 'bg-blue-600' : 'bg-slate-100 group-hover:bg-blue-50' }}">{{ $cat->icon }}</span>
                <span class="text-[10px] leading-tight {{ $on ? 'text-blue-600 font-semibold' : 'text-slate-600' }}">{{ $cat->name }}</span>
            </a>
        @endforeach
    </div>
    @if($categories->count() > $visible)
        <div class="text-center mt-4">
            <button type="button" id="catToggle" onclick="toggleCats()"
                class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 transition">
                <span id="catToggleLabel">{{ __('catalog.more') }}</span>
                <svg id="catToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
    @endif
</div>
@endif

{{-- ===== HEADER KATALOG ===== --}}
<div id="katalog" class="mb-6 scroll-mt-28">
    <h2 class="text-2xl font-bold text-slate-900">{{ $activeCategory ? $activeCategory->icon.' '.$activeCategory->name : __('catalog.catalog') }}</h2>
    <p class="text-sm text-slate-500">{{ number_format($products->total(), 0, ',', '.') }} {{ $activeCategory ? __('catalog.in_category') : __('catalog.available') }}</p>
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
        <p class="text-slate-600 font-medium">{{ __('catalog.empty_title') }}</p>
        <p class="text-sm text-slate-400 mt-1">{{ __('catalog.empty_sub') }}</p>
    </div>
@else
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        @foreach($products as $product)
            <div class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-blue-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col">
                <a href="/products/{{ $product->id }}" class="flex flex-col flex-1">
                    {{-- Foto di latar PUTIH, object-contain agar warna akurat & tidak terpotong --}}
                    <div class="relative aspect-square bg-white flex items-center justify-center p-3 border-b border-slate-100">
                        <img src="{{ $product->imageUrl() ?? 'https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image' }}"
                             onerror="this.src='https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image'"
                             class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        <span class="absolute top-2 left-2 bg-blue-600 text-[10px] font-semibold px-2 py-1 rounded-full text-white shadow-sm">TLKM</span>
                        @if($product->stock !== null && $product->stock <= 0)
                            <span class="absolute inset-0 bg-white/70 flex items-center justify-center text-sm font-bold text-slate-500">{{ __('catalog.sold_out') }}</span>
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
                        <button disabled class="w-full py-2 rounded-xl text-sm font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed">{{ __('catalog.sold_out') }}</button>
                    @elseif(auth()->check())
                        <button onclick="addToCart({{ $product->id }}, this)"
                            class="w-full inline-flex items-center justify-center gap-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 py-2 rounded-xl text-sm font-semibold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('catalog.add_cart') }}
                        </button>
                    @else
                        <a href="/login?next={{ urlencode(url('/products/'.$product->id)) }}"
                            class="w-full inline-flex items-center justify-center gap-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 py-2 rounded-xl text-sm font-semibold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('catalog.add_cart') }}
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">{{ $products->links() }}</div>
@endif

@endsection

@section('scripts')
<script>
    // Kategori: tampil 6 dulu, sisanya muncul saat "Lihat lebih banyak".
    function toggleCats() {
        const grid = document.getElementById('catGrid');
        const label = document.getElementById('catToggleLabel');
        const icon = document.getElementById('catToggleIcon');
        const expanded = grid.classList.toggle('cats-expanded');
        document.querySelectorAll('.cat-extra').forEach(el => el.classList.toggle('hidden', !expanded));
        label.textContent = expanded ? @json(__('catalog.less')) : @json(__('catalog.more'));
        icon.classList.toggle('rotate-180', expanded);
    }
</script>
@endsection

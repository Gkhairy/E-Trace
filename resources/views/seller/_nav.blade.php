{{-- Kepala + tab bersama semua halaman penjual (pola yang sama dengan panel pengawas). --}}
@php
    $navStore = $store ?? auth()->user()->store;
    $navToShip = \App\Models\OrderItem::where('seller_wallet', $navStore->payout_wallet)
        ->where('status', 'paid')->whereIn('fulfillment_status', ['pending', 'processing'])->count();
    $hour = (int) now()->format('G');
    $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name ?? '')->explode(' ')->first() ?: $navStore->name;

    $path = '/' . trim(request()->path(), '/');
    $current = match (true) {
        $path === '/seller'                                   => 'overview',
        str_starts_with($path, '/seller/orders')              => 'orders',
        str_starts_with($path, '/seller/products'),
        str_starts_with($path, '/products')                   => 'products',
        str_starts_with($path, '/seller/store')               => 'settings',
        default                                               => '',
    };
    $tabs = [
        'overview' => ['/seller',          'Ringkasan',        null,       'M4 5h6v6H4zM14 5h6v4h-6zM14 13h6v6h-6zM4 15h6v4H4z'],
        'orders'   => ['/seller/orders',   'Pesanan',          $navToShip, 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 13h6M9 17h4'],
        'products' => ['/seller/products', 'Produk',           null,       'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        'settings' => ['/seller/store',    'Pengaturan toko',  null,       'M10.3 4.3c.4-1.8 3-1.8 3.4 0a1.7 1.7 0 002.6 1.1c1.5-1 3.4.8 2.4 2.4a1.7 1.7 0 001 2.5c1.9.5 1.9 3.1 0 3.5a1.7 1.7 0 00-1 2.6c1 1.5-.9 3.3-2.4 2.3a1.7 1.7 0 00-2.6 1.1c-.4 1.8-3 1.8-3.4 0a1.7 1.7 0 00-2.6-1.1c-1.5 1-3.4-.8-2.4-2.3a1.7 1.7 0 00-1-2.6c-1.9-.4-1.9-3 0-3.5a1.7 1.7 0 001-2.5c-1-1.6.9-3.4 2.4-2.4a1.7 1.7 0 002.6-1.1zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
    $statusNote = [
        'pending'   => ['Toko sedang ditinjau pengawas. Produkmu belum tampil ke pembeli sampai toko disetujui.', 'bg-amber-50 text-amber-800 ring-amber-200'],
        'suspended' => ['Toko sedang dinonaktifkan pengawas. Hubungi pengawas platform untuk informasi lebih lanjut.', 'bg-red-50 text-red-800 ring-red-200'],
    ][$navStore->status] ?? null;
@endphp

<div class="mb-6">
    <div class="flex flex-wrap items-center gap-4">
        <div class="relative w-12 h-12 rounded-xl bg-blue-50 ring-1 ring-slate-200 overflow-hidden flex items-center justify-center shrink-0">
            <span class="text-lg font-bold text-blue-600" aria-hidden="true">{{ mb_strtoupper(mb_substr($navStore->name, 0, 1)) }}</span>
            @if($navStore->logo)
                <img src="/store_images/{{ $navStore->logo }}" alt="Logo {{ $navStore->name }}" class="absolute inset-0 w-full h-full object-cover" onerror="this.remove()">
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-slate-900 leading-tight">{{ $greet }}, {{ $firstName }}</h1>
            <p class="text-sm text-slate-500 truncate">Ini kabar toko <b class="font-semibold text-slate-700">{{ $navStore->name }}</b> hari ini.</p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <span class="hidden md:inline-flex items-center gap-2 h-10 px-3 rounded-xl bg-white ring-1 ring-slate-200 text-sm text-slate-600">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ now()->translatedFormat('d M Y') }}
            </span>
            @if($navStore->slug)
                <a href="/store/{{ $navStore->slug }}" class="inline-flex items-center justify-center gap-1.5 h-10 px-3.5 rounded-xl bg-white ring-1 ring-slate-200 hover:ring-slate-300 hover:bg-slate-50 text-sm font-medium text-slate-700 transition-colors flex-1 sm:flex-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    Lihat toko
                </a>
            @endif
            <a href="/products/create" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white text-sm font-semibold shadow-sm transition flex-1 sm:flex-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                Tambah produk
            </a>
        </div>
    </div>

    @if($statusNote)
        <p class="mt-4 px-4 py-3 rounded-xl ring-1 text-sm {{ $statusNote[1] }}">{{ $statusNote[0] }}</p>
    @endif

    <nav class="mt-5 border-b border-slate-200 -mx-4 px-4 md:mx-0 md:px-0 overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Menu toko">
        <ul class="flex gap-1 min-w-max">
            @foreach($tabs as $key => [$href, $label, $badge, $icon])
                @php $on = $current === $key; @endphp
                <li>
                    <a href="{{ $href }}" @if($on) aria-current="page" @endif
                       class="relative flex items-center gap-2 px-3.5 py-2.5 text-sm font-medium rounded-t-lg transition-colors
                              {{ $on ? 'text-slate-900' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/70' }}
                              focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <svg class="w-4 h-4 {{ $on ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                        {{ $label }}
                        @if($badge)
                            <span class="min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center tabular-nums
                                         {{ $on ? 'bg-slate-900 text-white' : 'bg-red-100 text-red-700' }}"
                                  title="{{ $badge }} pesanan perlu dikirim">{{ $badge > 99 ? '99+' : $badge }}</span>
                        @endif
                        @if($on)<span class="absolute left-2 right-2 -bottom-px h-0.5 rounded-full bg-blue-600"></span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>

@if(session('success'))
    <p role="status" class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 ring-1 ring-emerald-200 text-sm text-emerald-800">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </p>
@endif
@if(session('error'))
    <p role="alert" class="mb-5 px-4 py-3 rounded-xl bg-red-50 ring-1 ring-red-200 text-sm text-red-800">{{ session('error') }}</p>
@endif

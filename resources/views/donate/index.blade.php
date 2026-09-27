@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Donasi</h1>
        <p class="text-sm text-slate-500 mt-1 max-w-2xl">Pilih campaign, donasi TLKM dengan nominal bebas. Dana ditampung on-chain lalu disalurkan pengawas ke wallet penerima — semua bisa diaudit publik.</p>
    </div>
    @auth
        @if(auth()->user()->isSupervisor())
            <a href="/donate/create" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">+ Buat Campaign</a>
        @endif
    @endauth
</div>

@if(!$configured)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 text-sm mb-6">
        <b>Kotak donasi belum aktif.</b> Deploy <code class="font-mono">DonationPool.sol</code> lalu isi <code class="font-mono">DONATION_POOL_ADDRESS</code> di <code class="font-mono">.env</code>. Campaign tetap bisa dibuat & ditampilkan.
    </div>
@endif

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
@endif

{{-- ===== RADAR BENCANA AI (publik) ===== --}}
<section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12m-1 0a1 1 0 102 0 1 1 0 10-2 0M16.2 7.8a6 6 0 010 8.5M7.8 16.2a6 6 0 010-8.5M19.1 4.9a10 10 0 010 14.2M4.9 19.1a10 10 0 010-14.2"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="font-bold text-slate-900 flex items-center gap-2">Radar Bencana AI
                    <span class="relative flex w-2 h-2"><span class="absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75 animate-ping"></span><span class="relative inline-flex rounded-full w-2 h-2 bg-green-500"></span></span>
                </h2>
                <p class="text-xs text-slate-500">AI memantau BMKG, GDACS, dan berita Indonesia. Saat ada bencana, donasi dibuka otomatis dan dananya masuk ke Lembaga Donasi E-Trace.</p>
            </div>
        </div>
        @if($radarChecked)
            <span class="text-[11px] text-slate-400 shrink-0">Diperbarui {{ \Carbon\Carbon::parse($radarChecked)->diffForHumans() }}</span>
        @endif
    </div>

    @if($radar->isEmpty())
        <p class="text-sm text-slate-500 mt-4 bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">Belum ada bencana yang perlu penggalangan dana saat ini.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
            @foreach($radar as $e)
                @php $sev = (int) $e->ai_severity; @endphp
                <div class="flex items-start gap-3 rounded-xl border border-slate-200 p-3.5">
                    <span class="mt-0.5 w-2.5 h-2.5 rounded-full shrink-0 {{ $sev >= 70 ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900 leading-snug">{{ $e->ai_title ?: $e->title }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $e->typeLabel() }}{{ $e->location ? ' · ' . $e->location : '' }} · {{ optional($e->occurred_at)->diffForHumans() }} · {{ $e->sourceName() }}</p>
                    </div>
                    @if($e->status === 'opened' && $e->campaign)
                        <a href="/donate/{{ $e->campaign->slug }}" class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">Donasi</a>
                    @else
                        <span class="shrink-0 text-[11px] font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">Ditinjau</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>

@if($campaigns->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-3xl p-16 text-center text-slate-500">Belum ada campaign donasi.</div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($campaigns as $c)
            @php
                $m = $c['model'];
                $goal = (float) $m->goal_amount;
                $pct  = $goal > 0 ? min(100, round($c['raised'] / $goal * 100)) : null;
            @endphp
            <a href="/donate/{{ $m->slug }}" class="group bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition flex flex-col">
                <div class="relative aspect-[16/9] bg-slate-100 flex items-center justify-center">
                    @if($m->image)
                        {{-- Foto sumber dimuat dari situs aslinya; gagal dimuat → sampul ilustrasi. --}}
                        <img src="{{ $m->imageUrl() }}" alt="{{ $m->title }}" class="w-full h-full object-cover" loading="lazy" referrerpolicy="no-referrer"
                             onerror="this.onerror=null; this.src='{{ $m->coverUrl() }}'">
                        @if($credit = $m->imageCredit())
                            <span class="absolute left-2 bottom-2 text-[10px] font-medium text-white bg-black/55 backdrop-blur-sm rounded-md px-2 py-0.5">{{ $credit['text'] }}</span>
                        @endif
                    @else
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 000-7.78z"/></svg>
                    @endif
                </div>
                <div class="p-5 flex flex-col flex-1">
                    <div class="flex items-center gap-2 mb-1.5">
                        @if($m->isClosed())
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">Ditutup</span>
                        @else
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">Dibuka</span>
                        @endif
                        @if($m->isAi())
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">Radar AI</span>
                        @endif
                        @if($m->closes_at)
                            <span class="text-[10px] text-slate-400">{{ $m->isClosed() ? 'ditutup' : 'batas' }} {{ $m->closes_at->translatedFormat('d M Y') }}</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-slate-900 leading-snug line-clamp-2">{{ $m->title }}</h3>
                    @if($m->description)
                        <p class="text-sm text-slate-500 mt-1.5 line-clamp-2 flex-1">{{ $m->isAi() ? \Illuminate\Support\Str::before($m->description, "\n\n") : $m->description }}</p>
                    @else
                        <div class="flex-1"></div>
                    @endif

                    <div class="flex items-center gap-1.5 mt-3 text-xs text-slate-500">
                        <span class="truncate">Penerima: <span class="text-slate-700 font-medium">{{ $c['recipient']['name'] }}</span></span>
                        @if($c['recipient']['verified'])
                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                        @endif
                    </div>

                    @if($pct !== null)
                        <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    @endif

                    <div class="mt-2.5 flex items-end justify-between">
                        <div>
                            <p class="text-[11px] text-slate-400">Saldo saat ini</p>
                            <p class="font-extrabold text-green-600">{{ $fmt($c['balance']) }} <span class="text-xs font-semibold">TLKM</span></p>
                        </div>
                        @if($goal > 0)
                            <p class="text-xs text-slate-400">target {{ $fmt($goal) }}</p>
                        @else
                            <span class="text-slate-300 text-xl leading-none">∞</span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Masuk {{ $fmt($c['raised']) }} · Disalurkan {{ $fmt($c['disbursed']) }} TLKM</p>
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection

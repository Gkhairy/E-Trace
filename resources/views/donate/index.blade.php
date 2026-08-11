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
                <div class="aspect-[16/9] bg-slate-100 flex items-center justify-center">
                    @if($m->image)
                        <img src="/campaign_images/{{ $m->image }}" alt="{{ $m->title }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 000-7.78z"/></svg>
                    @endif
                </div>
                <div class="p-5 flex flex-col flex-1">
                    <h3 class="font-bold text-slate-900 leading-snug line-clamp-2">{{ $m->title }}</h3>
                    @if($m->description)
                        <p class="text-sm text-slate-500 mt-1.5 line-clamp-2 flex-1">{{ $m->description }}</p>
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
                            <p class="text-[11px] text-slate-400">Terkumpul</p>
                            <p class="font-extrabold text-green-600">{{ $fmt($c['raised']) }} <span class="text-xs font-semibold">TLKM</span></p>
                        </div>
                        @if($goal > 0)
                            <p class="text-xs text-slate-400">dari {{ $fmt($goal) }}</p>
                        @else
                            <span class="text-slate-300 text-xl leading-none">∞</span>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection

@extends('layouts.app')

@section('content')

@include('supervisor._nav')

@php
    $statusMeta = [
        'opened'         => [__('Donasi dibuka'), 'bg-green-50 text-green-700 border-green-200'],
        'pending_review' => [__('Menunggu tinjauan'), 'bg-amber-50 text-amber-700 border-amber-200'],
        'rejected'       => [__('Ditolak AI'), 'bg-slate-100 text-slate-600 border-slate-200'],
        'dismissed'      => [__('Diabaikan'), 'bg-slate-100 text-slate-500 border-slate-200'],
        'duplicate'      => [__('Duplikat'), 'bg-violet-50 text-violet-700 border-violet-200'],
        'new'            => [__('Baru'), 'bg-blue-50 text-blue-700 border-blue-200'],
    ];
    $sevColor = fn ($s) => $s === null ? 'bg-slate-200' : ($s >= 70 ? 'bg-red-500' : ($s >= 45 ? 'bg-amber-500' : 'bg-slate-400'));
    $srcClass = ['bmkg' => 'bg-sky-50 text-sky-700 border-sky-200', 'gdacs' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'news' => 'bg-slate-100 text-slate-600 border-slate-200', 'manual' => 'bg-violet-50 text-violet-700 border-violet-200'];
@endphp

{{-- Kepala + scan --}}
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div class="max-w-3xl">
        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            {{ __('Radar Bencana AI') }}
            <span class="relative flex w-2.5 h-2.5"><span class="absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75 animate-ping"></span><span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-green-500"></span></span>
        </h2>
        <p class="text-sm text-slate-500 mt-1">
            {{ __('Memantau') }} <b class="text-slate-700">BMKG</b>, <b class="text-slate-700">GDACS</b>{{ __(', dan berita Indonesia setiap hari pukul 07.00 WIB (atau kapan saja lewat') }} <b class="text-slate-700">{{ __('Scan sekarang') }}</b>{{ __('). AI menilai tiap kejadian.') }}
            {{ __('Skor AI') }} <b class="text-slate-700">{{ __('70 ke atas') }}</b> {{ __('langsung membuka donasi (maks. :n per hari), sedangkan skor 45–69 menunggu persetujuanmu.', ['n' => config('disaster.max_auto_per_day')]) }}
        </p>
        @if($lastScan)
            <p class="text-xs text-slate-400 mt-1.5">{{ __('Scan manual terakhir :ago: :fetched diambil, :new baru.', ['ago' => \Carbon\Carbon::parse($lastScan['at'])->diffForHumans(), 'fetched' => $lastScan['fetched'], 'new' => $lastScan['new']]) }}</p>
        @endif
    </div>
    <form method="POST" action="/supervisor/disasters/scan" onsubmit="this.querySelector('button').disabled=true; this.querySelector('[data-label]').textContent=@js(__('Memindai…'))">
        @csrf
        <button class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 disabled:opacity-60 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.6m14.8 2A8 8 0 004.6 9m0 0H9m11 11v-5h-.6m0 0a8 8 0 01-15.4-2m15.4 2H15"/></svg>
            <span data-label>{{ __('Scan sekarang') }}</span>
        </button>
    </form>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ $errors->first() }}</div>
@endif

@if(!$recipient)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl mb-5 text-sm">
        <b>{{ __('Wallet Lembaga Donasi E-Trace belum diisi.') }}</b> {{ __('Isi') }} <code class="font-mono bg-white/70 px-1 rounded">DISASTER_RECIPIENT_WALLET</code> {{ __('di Railway agar donasi bisa dibuka otomatis.') }}
        {{ __('Sampai saat itu semua kejadian masuk antrean, dan kamu bisa mengisi wallet penerima saat menyetujui.') }}
    </div>
@else
    <p class="text-xs text-slate-500 mb-5">{{ __('Penerima dana:') }} <span class="font-mono text-slate-700 bg-slate-100 border border-slate-200 rounded px-1.5 py-0.5">{{ substr($recipient, 0, 10) }}…{{ substr($recipient, -6) }}</span> {{ __('(Lembaga Donasi E-Trace)') }}</p>
@endif

{{-- Ringkasan --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        [__('Dinilai AI · 7 hari'), $stats['assessed'], 'text-slate-900'],
        [__('Menunggu tinjauan'), $stats['pending'], 'text-amber-600'],
        [__('Dibuka otomatis · 7 hari'), $stats['auto'], 'text-green-600'],
        [__('Ditolak AI · 7 hari'), $stats['rejected'], 'text-slate-500'],
    ] as [$label, $value, $color])
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-extrabold mt-1 tabular-nums {{ $color }}">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
    {{-- Uji dari berita --}}
    <div class="lg:col-span-2 space-y-4 min-w-0">
        @if($assessed)
            @include('supervisor._disaster_card', ['e' => $assessed, 'highlight' => true])
        @endif
        <form method="POST" action="/supervisor/disasters/assess" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5"
              onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent=@js(__('AI sedang menilai…'))">
            @csrf
            <h3 class="font-bold text-slate-900">{{ __('Uji dari berita') }}</h3>
            <p class="text-xs text-slate-500 mt-1 mb-4">{{ __('Tempel isi berita atau laporan warga. AI menilai apakah ini bencana dan seberapa parah. Skor 70 ke atas langsung membuka donasi.') }}</p>
            <textarea name="text" rows="6" required minlength="30" maxlength="3000" placeholder="{{ __('mis. Kebakaran melanda permukiman padat di Kelurahan …, 40 rumah hangus dan 120 warga mengungsi …') }}"
                      class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">{{ old('text') }}</textarea>
            <input name="url" type="url" value="{{ old('url') }}" placeholder="{{ __('Link sumber (opsional)') }}"
                   class="w-full mt-3 px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
            <button class="w-full mt-4 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-semibold transition">{{ __('Nilai dengan AI') }}</button>
        </form>
    </div>

    {{-- Antrean --}}
    <div class="lg:col-span-3 min-w-0">
        <h3 class="font-bold text-slate-900 mb-3">{{ __('Antrean tinjauan') }} <span class="text-slate-400 font-medium">({{ $pending->count() }})</span></h3>
        @forelse($pending as $e)
            <div class="mb-3">@include('supervisor._disaster_card', ['e' => $e, 'highlight' => false])</div>
        @empty
            <div class="flex flex-col items-center justify-center py-16 text-center bg-white border border-dashed border-slate-300 rounded-2xl">
                <svg class="w-8 h-8 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm text-slate-500">{{ __('Tidak ada usulan yang menunggu.') }}</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Kejadian terbaru --}}
<h3 class="font-bold text-slate-900 mb-3">{{ __('Kejadian terbaru') }}</h3>
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Waktu') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Sumber') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Kejadian') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Skor AI.col') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent as $e)
                    @php $sm = $statusMeta[$e->status] ?? [$e->status, 'bg-slate-100 text-slate-600 border-slate-200']; @endphp
                    <tr class="border-t border-slate-100 align-top">
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ optional($e->occurred_at)->timezone('Asia/Jakarta')->format('d M H:i') ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium border {{ $srcClass[$e->source] ?? '' }}">{{ __($e->sourceLabel()) }}</span></td>
                        <td class="px-4 py-3 min-w-[260px]">
                            <p class="text-slate-800 font-medium">{{ $e->ai_title ?: $e->title }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $e->type ? __($e->typeLabel()) : '' }}{{ $e->location ? ' · ' . $e->location : '' }}</p>
                            @if($e->ai_reason)<p class="text-xs text-slate-400 mt-1">{{ $e->ai_reason }}</p>@endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($e->ai_severity !== null)
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full {{ $sevColor($e->ai_severity) }}" style="width: {{ $e->ai_severity }}%"></div></div>
                                    <span class="text-xs tabular-nums text-slate-600">{{ $e->ai_severity }}</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-medium border {{ $sm[1] }}">{{ $sm[0] }}</span>
                            @if($e->campaign)
                                <a href="/donate/{{ $e->campaign->slug }}" class="block text-xs text-blue-600 hover:underline mt-1">{{ __('Lihat campaign') }} ↗</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400">{{ __('Belum ada kejadian. Tekan') }} <b>{{ __('Scan sekarang') }}</b> {{ __('untuk memindai.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

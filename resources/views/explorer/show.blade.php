@extends('layouts.app')

@section('content')
@php
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
    $typeLabel = ['entity'=>__('Entitas Terverifikasi'),'store'=>__('Toko'),'buyer'=>__('Pengguna'),'wallet'=>__('Wallet')][$identity['type']] ?? __('Wallet');
    $stBadge = ['paid'=>[__('Escrow (ditahan)'),'bg-amber-50 text-amber-700 border-amber-200'],'completed'=>[__('Selesai'),'bg-green-50 text-green-700 border-green-200'],'refunded'=>[__('Refund'),'bg-slate-100 text-slate-600 border-slate-200'],'disputed'=>[__('Sengketa'),'bg-red-50 text-red-700 border-red-200'],'pending_confirmation'=>[__('Pending'),'bg-slate-100 text-slate-500 border-slate-200']];
@endphp

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/explorer" class="hover:text-blue-600 transition">Explorer</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700 font-mono">{{ substr($addr,0,10) }}…{{ substr($addr,-6) }}</span>
</nav>

{{-- ===== HEADER ENTITAS ===== --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl font-bold text-slate-900">{{ $identity['name'] }}</h1>
                @if($identity['verified'])
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                        {{ __('Terverifikasi') }}
                    </span>
                @endif
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">{{ $typeLabel }}</span>
            </div>
            @if($isPseudonym)
                <p class="text-xs text-slate-400 mt-1">{{ __('Nama samaran (belum diverifikasi) — pengganti alamat wallet.') }}</p>
            @endif
            <p class="text-sm text-slate-400 font-mono mt-1 break-all">{{ $addr }}</p>
            <div class="flex flex-wrap items-center gap-3 mt-1">
                <a href="{{ config('chain.explorer_url') }}/address/{{ $addr }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline">{{ __('Lihat di BscScan') }} ↗</a>
                @if($joined)
                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                        {{ __('Bergabung sejak') }} {{ $joined->translatedFormat('F Y') }}
                    </span>
                @endif
            </div>
        </div>
        <div class="text-right">
            <p class="text-xs text-slate-500">{{ __('Saldo TLKM') }}</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $balance !== null ? $fmt($balance) : '—' }} <span class="text-sm text-blue-600">TLKM</span></p>
        </div>
    </div>
</div>

{{-- ===== STATISTIK ===== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach([
        [__('Total Beli'), $fmt($stats['bought_total']).' TLKM', __(':n order', ['n' => $stats['bought_count']]), 'text-slate-900'],
        [__('Total Jual (net)'), $fmt($stats['sold_net']).' TLKM', __(':n item', ['n' => $stats['sold_count']]), 'text-red-600'],
        [__('Escrow diterima'), $fmt($stats['escrow_in']).' TLKM', __('masuk (sbg penjual)'), 'text-green-600'],
        [__('Escrow dibayar'), $fmt($stats['escrow_out']).' TLKM', __('keluar (sbg pembeli)'), 'text-blue-600'],
    ] as $c)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <p class="text-xs text-slate-500">{{ $c[0] }}</p>
            <p class="text-xl font-extrabold mt-1 {{ $c[3] }}">{{ $c[1] }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $c[2] }}</p>
        </div>
    @endforeach
</div>

{{-- ===== PEMBELIAN ===== --}}
@if($buyerOrders->isNotEmpty())
    <h2 class="text-lg font-bold text-slate-900 mb-3">{{ __('Pembelian') }}</h2>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide"><tr>
                    <th class="px-4 py-3 font-medium">{{ __('Produk') }}</th><th class="px-4 py-3 font-medium">{{ __('Nominal') }}</th><th class="px-4 py-3 font-medium">{{ __('Status') }}</th><th class="px-4 py-3 font-medium">{{ __('Tanggal') }}</th><th class="px-4 py-3 font-medium">Tx</th>
                </tr></thead>
                <tbody>
                    @foreach($buyerOrders as $o)
                        @foreach($o->items as $it)
                            @php $sb = $stBadge[$it->status] ?? ['—','bg-slate-100 text-slate-500 border-slate-200']; @endphp
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 text-slate-800">
                                @if($it->product)
                                    <a href="/products/{{ $it->product->id }}" class="hover:text-blue-600 hover:underline">{{ $it->product->name }}</a>
                                @else — @endif
                            </td>
                                <td class="px-4 py-3 font-semibold text-blue-600 whitespace-nowrap">{{ $fmt($it->amount) }} TLKM</td>
                                <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border {{ $sb[1] }}">{{ $sb[0] }}</span></td>
                                <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">{{ $o->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3"><a href="{{ config('chain.explorer_url') }}/tx/{{ $o->tx_hash }}" target="_blank" class="text-green-600 hover:underline font-mono text-xs">{{ substr($o->tx_hash,0,8) }}… ↗</a></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- ===== PENJUALAN ===== --}}
@if($sellerItems->isNotEmpty())
    <h2 class="text-lg font-bold text-slate-900 mb-3">{{ __('Penjualan') }}</h2>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide"><tr>
                    <th class="px-4 py-3 font-medium">{{ __('Produk') }}</th><th class="px-4 py-3 font-medium">{{ __('Nominal') }}</th><th class="px-4 py-3 font-medium">{{ __('Status') }}</th><th class="px-4 py-3 font-medium">{{ __('Tanggal') }}</th><th class="px-4 py-3 font-medium">Tx</th>
                </tr></thead>
                <tbody>
                    @foreach($sellerItems as $it)
                        @php $sb = $stBadge[$it->status] ?? ['—','bg-slate-100 text-slate-500 border-slate-200']; @endphp
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 text-slate-800">
                                @if($it->product)
                                    <a href="/products/{{ $it->product->id }}" class="hover:text-blue-600 hover:underline">{{ $it->product->name }}</a>
                                @else — @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-green-600 whitespace-nowrap">{{ $fmt($it->amount) }} TLKM</td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border {{ $sb[1] }}">{{ $sb[0] }}</span></td>
                            <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">{{ $it->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3"><a href="{{ config('chain.explorer_url') }}/tx/{{ $it->order->tx_hash ?? '' }}" target="_blank" class="text-green-600 hover:underline font-mono text-xs">{{ substr($it->order->tx_hash ?? '', 0, 8) }}… ↗</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($buyerOrders->isEmpty() && $sellerItems->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center text-slate-500">{{ __('Belum ada aktivitas marketplace untuk wallet ini.') }}</div>
@endif


{{-- ===== RIWAYAT TRANSFER TLKM (indeks on-chain) ===== --}}
<div class="flex items-center gap-2 mt-8 mb-3">
    <h2 class="text-lg font-bold text-slate-900">{{ __('Riwayat Transfer TLKM') }}</h2>
    <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200" title="{{ __('Dari indeks event Transfer on-chain: masuk dari siapa, keluar ke mana.') }}">on-chain</span>
</div>

@if($flow['count'] > 0)
    <div class="grid grid-cols-3 gap-3 mb-3">
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <p class="text-xs text-slate-500">{{ __('Masuk.flow') }}</p>
            <p class="text-lg font-extrabold text-green-600">+{{ $fmt($flow['in']) }} <span class="text-xs font-semibold text-slate-400">TLKM</span></p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <p class="text-xs text-slate-500">{{ __('Keluar') }}</p>
            <p class="text-lg font-extrabold text-red-600">-{{ $fmt($flow['out']) }} <span class="text-xs font-semibold text-slate-400">TLKM</span></p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <p class="text-xs text-slate-500">{{ __('Jumlah transfer') }}</p>
            <p class="text-lg font-extrabold text-slate-900">{{ $flow['count'] }}</p>
        </div>
    </div>
@endif

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Arah') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Pihak lawan') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Jumlah') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Waktu') }}</th>
                    <th class="px-4 py-3 font-medium">Tx</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $t)
                    @php $isIn = $t['direction'] === 'in'; $c = $t['counter']; @endphp
                    <tr class="border-t border-slate-100 hover:bg-slate-50 transition">
                        <td class="px-4 py-3">
                            @if($t['direction'] === 'self')
                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">{{ __('SENDIRI') }}</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $isIn ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">{{ $isIn ? __('MASUK') : __('KELUAR') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[11px] text-slate-400">{{ $isIn ? __('dari') : __('ke') }}</span>
                            @if($c['system'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">{{ $c['name'] }}</span>
                            @else
                                <a href="/explorer/{{ $c['addr'] }}" class="text-blue-600 hover:underline font-medium">{{ $c['name'] }}</a>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold whitespace-nowrap {{ $t['direction'] === 'self' ? 'text-slate-600' : ($isIn ? 'text-green-600' : 'text-red-600') }}">
                            {{ $t['direction'] === 'self' ? '' : ($isIn ? '+' : '-') }}{{ $fmt($t['tlkm']) }} TLKM
                        </td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $t['time'] ? $t['time']->diffForHumans() : '—' }}</td>
                        <td class="px-4 py-3"><a href="{{ config('chain.explorer_url') }}/tx/{{ $t['tx'] }}" target="_blank" rel="noopener" class="text-green-600 hover:underline font-mono text-xs">{{ substr($t['tx'],0,8) }}… ↗</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">{{ __('Belum ada transfer TLKM terindeks untuk alamat ini.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="text-[11px] text-slate-400 mt-2">{{ __('Dari indeks event') }} <code class="bg-slate-100 px-1 rounded">Transfer</code> {{ __('TLKM on-chain (diperbarui tiap menit). Node publik hanya menyimpan riwayat singkat, jadi indeks tumbuh maju sejak diaktifkan — isi') }} <code class="bg-slate-100 px-1 rounded">ARCHIVE_RPC_URL</code> {{ __('untuk menarik riwayat sejak token lahir.') }}</p>

@endsection

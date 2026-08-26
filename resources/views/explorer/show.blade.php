@extends('layouts.app')

@section('content')
@php
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
    $typeLabel = ['entity'=>'Entitas Terverifikasi','store'=>'Toko','buyer'=>'Pengguna','wallet'=>'Wallet'][$identity['type']] ?? 'Wallet';
    $stBadge = ['paid'=>['Escrow (ditahan)','bg-amber-50 text-amber-700 border-amber-200'],'completed'=>['Selesai','bg-green-50 text-green-700 border-green-200'],'refunded'=>['Refund','bg-slate-100 text-slate-600 border-slate-200'],'disputed'=>['Sengketa','bg-red-50 text-red-700 border-red-200'],'pending_confirmation'=>['Pending','bg-slate-100 text-slate-500 border-slate-200']];
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
                        Terverifikasi
                    </span>
                @endif
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">{{ $typeLabel }}</span>
            </div>
            @if($isPseudonym)
                <p class="text-xs text-slate-400 mt-1">Nama samaran (belum diverifikasi) — pengganti alamat wallet.</p>
            @endif
            <p class="text-sm text-slate-400 font-mono mt-1 break-all">{{ $addr }}</p>
            <div class="flex flex-wrap items-center gap-3 mt-1">
                <a href="https://sepolia.etherscan.io/address/{{ $addr }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline">Lihat di Etherscan ↗</a>
                @if($joined)
                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                        Bergabung sejak {{ $joined->translatedFormat('F Y') }}
                    </span>
                @endif
            </div>
        </div>
        <div class="text-right">
            <p class="text-xs text-slate-500">Saldo TLKM</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $balance !== null ? $fmt($balance) : '—' }} <span class="text-sm text-blue-600">TLKM</span></p>
        </div>
    </div>
</div>

{{-- ===== STATISTIK ===== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach([
        ['Total Beli', $fmt($stats['bought_total']).' TLKM', $stats['bought_count'].' order', 'text-slate-900'],
        ['Total Jual (net)', $fmt($stats['sold_net']).' TLKM', $stats['sold_count'].' item', 'text-green-600'],
        ['Escrow diterima', $fmt($stats['escrow_in']).' TLKM', 'masuk (sbg penjual)', 'text-green-600'],
        ['Escrow dibayar', $fmt($stats['escrow_out']).' TLKM', 'keluar (sbg pembeli)', 'text-blue-600'],
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
    <h2 class="text-lg font-bold text-slate-900 mb-3">Pembelian</h2>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide"><tr>
                    <th class="px-4 py-3 font-medium">Produk</th><th class="px-4 py-3 font-medium">Nominal</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Tanggal</th><th class="px-4 py-3 font-medium">Tx</th>
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
                                <td class="px-4 py-3"><a href="https://sepolia.etherscan.io/tx/{{ $o->tx_hash }}" target="_blank" class="text-green-600 hover:underline font-mono text-xs">{{ substr($o->tx_hash,0,8) }}… ↗</a></td>
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
    <h2 class="text-lg font-bold text-slate-900 mb-3">Penjualan</h2>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide"><tr>
                    <th class="px-4 py-3 font-medium">Produk</th><th class="px-4 py-3 font-medium">Nominal</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Tanggal</th><th class="px-4 py-3 font-medium">Tx</th>
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
                            <td class="px-4 py-3"><a href="https://sepolia.etherscan.io/tx/{{ $it->order->tx_hash ?? '' }}" target="_blank" class="text-green-600 hover:underline font-mono text-xs">{{ substr($it->order->tx_hash ?? '', 0, 8) }}… ↗</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($buyerOrders->isEmpty() && $sellerItems->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center text-slate-500">Belum ada aktivitas marketplace untuk wallet ini.</div>
@endif

@endsection

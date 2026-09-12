@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Explorer Transparansi</h1>
    </div>
    <p class="text-sm text-slate-500 max-w-3xl">Semua aktivitas marketplace terbuka: siapa membeli/menjual apa, nominal, dan status escrow — terverifikasi on-chain. Data pribadi (nama asli, email, telepon, alamat) tetap privat.</p>
</div>

{{-- ===== TOTAL ===== --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    @foreach([
        ['Volume', $fmt($totals['volume']).' TLKM', 'text-slate-900'],
        ['Transaksi', $totals['tx'], 'text-slate-900'],
        ['Ditahan Escrow', $fmt($totals['escrow_held']).' TLKM', 'text-amber-600'],
        ['Toko', $totals['stores'], 'text-slate-900'],
        ['Entitas Terverifikasi', $totals['labels'], 'text-blue-600'],
    ] as $c)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <p class="text-xs text-slate-500">{{ $c[0] }}</p>
            <p class="text-xl font-extrabold mt-1 {{ $c[2] }}">{{ $c[1] }}</p>
        </div>
    @endforeach
</div>

{{-- ===== ENTITAS TERVERIFIKASI ===== --}}
@if($labels->isNotEmpty())
    <h2 class="text-lg font-bold text-slate-900 mb-3">Entitas Terverifikasi</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach($labels as $l)
            <a href="/explorer/{{ $l['address'] }}" class="bg-white border border-blue-200 rounded-2xl p-4 hover:border-blue-500 hover:shadow-md transition">
                <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-slate-900 truncate">{{ $l['name'] }}</span>
                    <svg class="w-4 h-4 text-blue-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                </div>
                @if($l['category'])<p class="text-[11px] text-slate-400 mt-0.5 capitalize">{{ $l['category'] }}</p>@endif
                <p class="text-[11px] text-slate-400 font-mono mt-1 truncate">{{ substr($l['address'],0,10) }}…{{ substr($l['address'],-6) }}</p>
            </a>
        @endforeach
    </div>
@endif

{{-- ===== TOKO (Top 4, bisa diurutkan) ===== --}}
@if($stores->isNotEmpty())
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-2">
            <h2 class="text-lg font-bold text-slate-900">Toko Teratas</h2>
            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200" title="Menampilkan 4 toko teratas. Statistik total toko ada di kartu ringkasan di atas.">Top 4</span>
        </div>
        {{-- D4: pilih urutan --}}
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs">
            <a href="?store_sort=sold{{ $range!=='all' ? '&range='.$range : '' }}" title="Toko dengan total penjualan (TLKM) terbesar"
               class="px-3 py-1.5 rounded-md font-medium transition {{ $storeSort==='sold' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:text-blue-600' }}">Paling Laris</a>
            <a href="?store_sort=rating{{ $range!=='all' ? '&range='.$range : '' }}" title="Toko dengan rata-rata rating ulasan tertinggi"
               class="px-3 py-1.5 rounded-md font-medium transition {{ $storeSort==='rating' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:text-blue-600' }}">Rating Tertinggi</a>
        </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach($stores as $s)
            <a href="/explorer/{{ $s['address'] }}" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-blue-400 hover:shadow-md transition">
                <p class="font-semibold text-slate-900 truncate">🏪 {{ $s['name'] }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $s['products'] }} produk</p>
                <div class="flex items-center justify-between mt-1">
                    <p class="text-xs text-green-600 font-medium">Terjual {{ $fmt($s['sold']) }} TLKM</p>
                    @if($s['rating'] !== null)
                        <span class="text-xs text-amber-500 font-medium whitespace-nowrap" title="{{ $s['reviews'] }} ulasan">★ {{ $s['rating'] }}</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

{{-- ===== TRANSAKSI TERBARU (dengan filter waktu D1) ===== --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-3">
    <h2 class="text-lg font-bold text-slate-900">Transaksi Terbaru</h2>
    <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs" role="group" aria-label="Filter waktu transaksi">
        @php $ranges = ['all'=>['Semua','Semua transaksi'],'day'=>['Hari ini','24 jam terakhir'],'week'=>['Minggu','7 hari terakhir'],'month'=>['Bulan','30 hari terakhir']]; @endphp
        @foreach($ranges as $key => $r)
            <a href="?range={{ $key }}{{ $storeSort!=='sold' ? '&store_sort='.$storeSort : '' }}" title="{{ $r[1] }}"
               class="px-3 py-1.5 rounded-md font-medium transition {{ $range===$key ? 'bg-blue-600 text-white' : 'text-slate-600 hover:text-blue-600' }}">{{ $r[0] }}</a>
        @endforeach
    </div>
</div>
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 font-medium">Pembeli</th>
                    <th class="px-4 py-3 font-medium">Penjual</th>
                    <th class="px-4 py-3 font-medium">Produk</th>
                    <th class="px-4 py-3 font-medium">Nominal</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Tx</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $stBadge = ['paid'=>['Escrow','text-amber-600'],'completed'=>['Selesai','text-green-600'],'refunded'=>['Refund','text-slate-500'],'disputed'=>['Sengketa','text-red-600'],'pending_confirmation'=>['Pending','text-slate-400']];
                    $idLink = function($id, $addr) { if(!$addr) return '<span class="text-slate-400">—</span>'; $chk = $id['verified'] ? ' <svg class="w-3.5 h-3.5 inline text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>' : ''; return '<a href="/explorer/'.$addr.'" class="text-blue-600 hover:underline font-medium">'.e($id['name']).'</a>'.$chk; };
                @endphp
                @forelse($recent as $r)
                    @php $sb = $stBadge[$r['status']] ?? ['—','text-slate-400']; @endphp
                    <tr class="border-t border-slate-100 hover:bg-slate-50 transition">
                        <td class="px-4 py-3">{!! $idLink($r['buyerId'], $r['buyer']) !!}</td>
                        <td class="px-4 py-3">{!! $idLink($r['sellerId'], $r['seller']) !!}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $r['product'] }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ $fmt($r['amount']) }} TLKM</td>
                        <td class="px-4 py-3"><span class="text-xs font-medium {{ $sb[1] }}">{{ $sb[0] }}</span></td>
                        <td class="px-4 py-3"><a href="{{ config('chain.explorer_url') }}/tx/{{ $r['tx'] }}" target="_blank" rel="noopener" class="text-green-600 hover:underline font-mono text-xs">{{ substr($r['tx'],0,8) }}… ↗</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $recent->links() }}</div>
</div>

@endsection

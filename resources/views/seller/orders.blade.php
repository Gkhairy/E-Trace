@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $filters = [
        'kirim'   => 'Perlu dikirim',
        'jalan'   => 'Dalam pengiriman',
        'selesai' => 'Selesai',
        'masalah' => 'Sengketa & refund',
        'semua'   => 'Semua',
    ];
    $empty = [
        'kirim'   => ['Tidak ada yang perlu dikirim', 'Pesanan baru yang sudah dibayar akan muncul di sini.'],
        'jalan'   => ['Tidak ada paket di jalan', 'Pesanan yang sudah kamu beri nomor resi akan muncul di sini.'],
        'selesai' => ['Belum ada pesanan selesai', 'Pesanan selesai setelah pembeli mengonfirmasi barang diterima.'],
        'masalah' => ['Tidak ada masalah', 'Sengketa atau pengembalian dana akan muncul di sini.'],
        'semua'   => ['Belum ada pesanan', 'Pesanan muncul di sini begitu pembeli membayar.'],
    ][$tab];
    // Tahap untuk garis kemajuan: bayar, kemas, kirim, selesai.
    $stage = fn ($it) => match (true) {
        $it->status === 'completed'                                        => 4,
        in_array($it->fulfillment_status, ['shipped', 'delivered'], true)  => 3,
        $it->fulfillment_status === 'processing'                           => 2,
        default                                                            => 1,
    };
    $couriers = ['JNE', 'J&T Express', 'SiCepat', 'AnterAja', 'Pos Indonesia', 'Ninja Xpress', 'ID Express', 'GoSend', 'GrabExpress'];
@endphp

@include('seller._nav')

<div class="flex flex-wrap gap-2 mb-5" role="tablist" aria-label="Saring pesanan">
    @foreach($filters as $key => $label)
        @php $on = $tab === $key; @endphp
        <a href="?tab={{ $key }}" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}"
           class="inline-flex items-center gap-2 h-9 px-3.5 rounded-full text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500
                  {{ $on ? 'bg-slate-900 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600 hover:text-slate-900 hover:ring-slate-300' }}">
            {{ $label }}
            <span class="tabular-nums text-xs {{ $on ? 'text-slate-300' : ($key === 'kirim' && $counts[$key] ? 'text-red-700 font-bold' : 'text-slate-400') }}">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

<datalist id="courierList">@foreach($couriers as $c)<option value="{{ $c }}">@endforeach</datalist>

@if($items->isEmpty())
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 px-6 py-16 text-center">
        <p class="font-semibold text-slate-800">{{ $empty[0] }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $empty[1] }}</p>
    </div>
@else
    <ul class="space-y-4">
        @foreach($items as $it)
            @php
                $st = \App\Support\SellerStatus::for($it);
                $addr = $it->order?->shippingAddress;
                $n = $stage($it);
                $canAct = $it->status === 'paid' && in_array($it->fulfillment_status, ['pending', 'processing'], true);
                $problem = in_array($it->status, ['disputed', 'refunded'], true);
            @endphp
            <li class="bg-white rounded-2xl ring-1 {{ $canAct ? 'ring-amber-200' : 'ring-slate-200' }} shadow-sm overflow-hidden">
                <div class="p-5 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)]">
                    {{-- Produk & nominal --}}
                    <div class="flex gap-4 min-w-0">
                        <img src="{{ $it->product?->thumbnail() ?? 'https://placehold.co/160x160/f1f5f9/94a3b8?text=-' }}" alt="" loading="lazy" onerror="this.src='https://placehold.co/160x160/f1f5f9/94a3b8?text=-'" class="w-16 h-16 rounded-xl object-contain bg-slate-50 ring-1 ring-slate-100 shrink-0">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate">{{ $it->product->name ?? 'Produk dihapus' }}</p>
                            <p class="text-sm text-slate-600 tabular-nums">
                                {{ ($it->quantity ?? 1) }} barang, <b class="text-slate-900">{{ $fmt($it->amount) }} TLKM</b>
                            </p>
                            <p class="text-xs text-slate-500">Kamu terima ≈ <span class="tabular-nums">{{ $fmt((float) $it->amount * (1 - $feePct / 100)) }}</span> TLKM setelah fee {{ $fmt($feePct) }}%</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $it->created_at->translatedFormat('d M Y, H:i') }} · <span class="font-mono">{{ $it->order->order_id ?? '' }}</span></p>
                        </div>
                    </div>

                    {{-- Kirim ke --}}
                    <div class="min-w-0 text-sm">
                        <p class="text-xs font-medium text-slate-500">Kirim ke</p>
                        @if($addr)
                            <p class="font-medium text-slate-900">{{ $addr->recipient_name }} <span class="font-normal text-slate-500">{{ $addr->phone }}</span></p>
                            <p class="text-slate-600 leading-snug">{{ $addr->address }}, {{ $addr->city }} {{ $addr->postal_code }}</p>
                            @if($addr->notes)<p class="mt-1 text-xs text-slate-500">Catatan pembeli: {{ $addr->notes }}</p>@endif
                        @else
                            <p class="text-slate-500">Alamat tidak tersedia.</p>
                        @endif
                    </div>

                    {{-- Status & kemajuan --}}
                    <div class="min-w-0">
                        <span class="inline-flex px-2 py-1 rounded-md ring-1 text-xs font-semibold {{ $st['tone'] }}">{{ $st['label'] }}</span>
                        <p class="mt-1.5 text-sm text-slate-600 leading-snug">{{ $st['hint'] }}</p>
                        @unless($problem)
                            <ol class="mt-3 grid grid-cols-4 gap-1" aria-label="Kemajuan pesanan: tahap {{ $n }} dari 4">
                                @foreach(['Dibayar', 'Dikemas', 'Dikirim', 'Selesai'] as $i => $step)
                                    <li>
                                        <span class="block h-1.5 rounded-full {{ $i < $n ? 'bg-blue-600' : 'bg-slate-200' }}"></span>
                                        <span class="mt-1 block text-[11px] {{ $i < $n ? 'text-slate-700 font-medium' : 'text-slate-400' }}">{{ $step }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endunless
                        @if($it->tracking_number)
                            <p class="mt-2 text-xs text-slate-600">Resi <b class="font-mono text-slate-900">{{ $it->tracking_number }}</b>@if($it->courier), {{ $it->courier }}@endif</p>
                        @endif
                    </div>
                </div>

                @if($canAct)
                    <div class="px-5 py-4 bg-amber-50/60 border-t border-amber-100 flex flex-wrap items-end gap-3">
                        @if($it->fulfillment_status === 'pending')
                            <form method="POST" action="/seller/fulfill" class="shrink-0">@csrf
                                <input type="hidden" name="item_id" value="{{ $it->id }}">
                                <input type="hidden" name="action" value="process">
                                <button class="h-10 px-4 rounded-xl bg-white ring-1 ring-slate-300 hover:ring-slate-400 text-sm font-semibold text-slate-800 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">Tandai sedang dikemas</button>
                            </form>
                            <span class="hidden sm:block self-center text-sm text-slate-400">atau langsung</span>
                        @endif
                        <form method="POST" action="/seller/fulfill" class="flex flex-wrap items-end gap-3 flex-1 min-w-0">@csrf
                            <input type="hidden" name="item_id" value="{{ $it->id }}">
                            <input type="hidden" name="action" value="ship">
                            <div class="w-full sm:w-44">
                                <label for="resi{{ $it->id }}" class="block text-xs font-medium text-slate-700 mb-1">Nomor resi</label>
                                <input id="resi{{ $it->id }}" name="tracking_number" required maxlength="100" autocomplete="off" class="w-full h-10 px-3 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono">
                            </div>
                            <div class="w-full sm:w-44">
                                <label for="kurir{{ $it->id }}" class="block text-xs font-medium text-slate-700 mb-1">Kurir <span class="font-normal text-slate-500">(opsional)</span></label>
                                <input id="kurir{{ $it->id }}" name="courier" list="courierList" maxlength="60" class="w-full h-10 px-3 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            </div>
                            <button class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white text-sm font-semibold shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">Kirim pesanan</button>
                        </form>
                        <p class="w-full text-xs text-slate-600">Pembeli otomatis diberi tahu nomor resinya lewat email dan notifikasi.</p>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    <div class="mt-6">{{ $items->links() }}</div>
@endif
@endsection

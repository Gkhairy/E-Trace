@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $filters = [
        'kirim'   => __('Perlu dikirim'),
        'jalan'   => __('Dalam pengiriman'),
        'selesai' => __('Selesai'),
        'masalah' => __('Sengketa & refund'),
        'semua'   => __('Semua'),
    ];
    $empty = [
        'kirim'   => [__('Tidak ada yang perlu dikirim'), __('Pesanan baru yang sudah dibayar akan muncul di sini.')],
        'jalan'   => [__('Tidak ada paket di jalan'), __('Pesanan yang sudah kamu beri nomor resi akan muncul di sini.')],
        'selesai' => [__('Belum ada pesanan selesai'), __('Pesanan selesai setelah pembeli mengonfirmasi barang diterima.')],
        'masalah' => [__('Tidak ada masalah'), __('Sengketa atau pengembalian dana akan muncul di sini.')],
        'semua'   => [__('Belum ada pesanan'), __('Pesanan muncul di sini begitu pembeli membayar.')],
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

<div class="flex flex-wrap gap-2 mb-5" role="tablist" aria-label="{{ __('Saring pesanan') }}">
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
            <li class="bg-white rounded-2xl ring-1 {{ $canAct ? 'ring-amber-200' : 'ring-slate-200' }} shadow-sm">
                <div class="p-5 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)]">
                    {{-- Produk & nominal --}}
                    <div class="flex gap-4 min-w-0">
                        <img src="{{ $it->product?->thumbnail() ?? 'https://placehold.co/160x160/f1f5f9/94a3b8?text=-' }}" alt="" loading="lazy" onerror="this.src='https://placehold.co/160x160/f1f5f9/94a3b8?text=-'" class="w-16 h-16 rounded-xl object-contain bg-slate-50 ring-1 ring-slate-100 shrink-0">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate">{{ $it->product->name ?? __('Produk dihapus') }}</p>
                            <p class="text-sm text-slate-600 tabular-nums">
                                {{ __(':n barang,', ['n' => ($it->quantity ?? 1)]) }} <b class="text-slate-900">{{ $fmt($it->amount) }} TLKM</b>
                            </p>
                            <p class="text-xs text-slate-500">{{ __('Kamu terima ≈ :net TLKM setelah fee :fee%', ['net' => $fmt((float) $it->amount * (1 - $feePct / 100)), 'fee' => $fmt($feePct)]) }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $it->created_at->translatedFormat('d M Y, H:i') }} · <span class="font-mono">{{ $it->order->order_id ?? '' }}</span></p>
                        </div>
                    </div>

                    {{-- Kirim ke --}}
                    <div class="min-w-0 text-sm">
                        <p class="text-xs font-medium text-slate-500">{{ __('Kirim ke') }}</p>
                        @if($addr)
                            <p class="font-medium text-slate-900">{{ $addr->recipient_name }} <span class="font-normal text-slate-500">{{ $addr->phone }}</span></p>
                            <p class="text-slate-600 leading-snug">{{ $addr->address }}, {{ $addr->city }} {{ $addr->postal_code }}</p>
                            @if($addr->notes)<p class="mt-1 text-xs text-slate-500">{{ __('Catatan pembeli:') }} {{ $addr->notes }}</p>@endif
                        @else
                            <p class="text-slate-500">{{ __('Alamat tidak tersedia.') }}</p>
                        @endif
                    </div>

                    {{-- Status & kemajuan --}}
                    <div class="min-w-0">
                        <span class="inline-flex px-2 py-1 rounded-md ring-1 text-xs font-semibold {{ $st['tone'] }}">{{ $st['label'] }}</span>
                        <p class="mt-1.5 text-sm text-slate-600 leading-snug">{{ $st['hint'] }}</p>
                        @unless($problem)
                            <ol class="mt-3 grid grid-cols-4 gap-1" aria-label="{{ __('Kemajuan pesanan: tahap :n dari 4', ['n' => $n]) }}">
                                @foreach([__('Dibayar'), __('Dikemas'), __('Dikirim'), __('Selesai')] as $i => $step)
                                    <li>
                                        <span class="block h-1.5 rounded-full {{ $i < $n ? 'bg-blue-600' : 'bg-slate-200' }}"></span>
                                        <span class="mt-1 block text-[11px] {{ $i < $n ? 'text-slate-700 font-medium' : 'text-slate-400' }}">{{ $step }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endunless
                        @if($it->tracking_number)
                            <p class="mt-2 text-xs text-slate-600">{{ __('Resi') }} <b class="font-mono text-slate-900">{{ $it->tracking_number }}</b>@if($it->courier), {{ $it->courier }}@endif</p>
                        @endif
                    </div>
                </div>

                @if($canAct)
                    <div class="px-5 py-4 bg-amber-50/60 border-t border-amber-100 rounded-b-2xl flex flex-wrap items-end gap-3">
                        @if($it->fulfillment_status === 'pending')
                            <form method="POST" action="/seller/fulfill" class="shrink-0">@csrf
                                <input type="hidden" name="item_id" value="{{ $it->id }}">
                                <input type="hidden" name="action" value="process">
                                <button class="h-10 px-4 rounded-xl bg-white ring-1 ring-slate-300 hover:ring-slate-400 text-sm font-semibold text-slate-800 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">{{ __('Tandai sedang dikemas') }}</button>
                            </form>
                            <span class="hidden sm:block self-center text-sm text-slate-400">{{ __('atau langsung') }}</span>
                        @endif
                        <form method="POST" action="/seller/fulfill" class="flex flex-wrap items-end gap-3 flex-1 min-w-0">@csrf
                            <input type="hidden" name="item_id" value="{{ $it->id }}">
                            <input type="hidden" name="action" value="ship">
                            <div class="w-full sm:w-44">
                                <label for="resi{{ $it->id }}" class="block text-xs font-medium text-slate-700 mb-1">{{ __('Nomor resi') }}</label>
                                <input id="resi{{ $it->id }}" name="tracking_number" required maxlength="100" autocomplete="off" class="w-full h-10 px-3 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono">
                            </div>
                            <div class="w-full sm:w-44">
                                <label for="kurir{{ $it->id }}" class="block text-xs font-medium text-slate-700 mb-1">{{ __('Kurir') }} <span class="font-normal text-slate-500">{{ __('(opsional)') }}</span></label>
                                <div class="relative" data-combo>
                                    <input id="kurir{{ $it->id }}" name="courier" maxlength="60" autocomplete="off" placeholder="{{ __('Pilih atau ketik') }}"
                                        role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="kurirList{{ $it->id }}"
                                        class="w-full h-10 pl-3 pr-9 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm placeholder:text-slate-400">
                                    <button type="button" tabindex="-1" data-combo-toggle aria-label="{{ __('Tampilkan kurir') }}" class="absolute inset-y-0 right-0 w-9 grid place-items-center text-slate-400 hover:text-slate-600">
                                        <svg class="size-4 transition-transform" data-chevron viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                    </button>
                                    <ul id="kurirList{{ $it->id }}" role="listbox" hidden class="absolute z-30 left-0 mt-1.5 w-full min-w-[13rem] max-h-64 overflow-auto rounded-xl bg-white ring-1 ring-slate-200 shadow-lg shadow-slate-900/10 p-1 text-sm">
                                        @foreach($couriers as $c)
                                            <li id="kurir{{ $it->id }}-{{ $loop->index }}" role="option" aria-selected="false" data-value="{{ $c }}"
                                                class="group flex [&[hidden]]:hidden items-center gap-2.5 px-2 py-1.5 rounded-lg cursor-pointer text-slate-700 hover:bg-slate-100 data-[active]:bg-slate-100 aria-selected:text-blue-700">
                                                <span class="size-7 shrink-0 rounded-md bg-slate-100 group-aria-selected:bg-blue-100 grid place-items-center text-[10px] font-bold tracking-wide text-slate-600 group-aria-selected:text-blue-700">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $c), 0, 2)) }}</span>
                                                <span class="truncate">{{ $c }}</span>
                                                <svg class="size-4 ml-auto hidden group-aria-selected:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                            </li>
                                        @endforeach
                                        <li data-empty hidden class="px-2.5 py-2 text-xs text-slate-500">{{ __('Tidak ada di daftar. Nama yang kamu ketik tetap dipakai.') }}</li>
                                    </ul>
                                </div>
                            </div>
                            <button class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white text-sm font-semibold shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">{{ __('Kirim pesanan') }}</button>
                        </form>
                        <p class="w-full text-xs text-slate-600">{{ __('Pembeli otomatis diberi tahu nomor resinya lewat email dan notifikasi.') }}</p>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    <div class="mt-6">{{ $items->links() }}</div>
@endif

<script>
// Pilih kurir: combobox kustom (datalist bawaan browser tampil gelap & tak bisa diberi gaya).
// Tetap input teks bebas; daftar hanya membantu memilih.
document.querySelectorAll('[data-combo]').forEach(box => {
    const input = box.querySelector('input');
    const list  = box.querySelector('[role=listbox]');
    const opts  = [...list.querySelectorAll('[role=option]')];
    const empty = list.querySelector('[data-empty]');
    const chev  = box.querySelector('[data-chevron]');
    let active = -1;

    const shown = () => opts.filter(o => !o.hidden);
    const mark = () => opts.forEach(o => o.setAttribute('aria-selected', o.dataset.value === input.value.trim() ? 'true' : 'false'));
    const setActive = i => {
        opts.forEach(o => o.removeAttribute('data-active'));
        const v = shown(); active = i;
        if (v[i]) { v[i].setAttribute('data-active', ''); v[i].scrollIntoView({ block: 'nearest' }); input.setAttribute('aria-activedescendant', v[i].id); }
        else input.removeAttribute('aria-activedescendant');
    };
    const filter = q => {
        q = (q || '').trim().toLowerCase();
        opts.forEach(o => o.hidden = q !== '' && !o.dataset.value.toLowerCase().includes(q));
        empty.hidden = shown().length > 0;
        mark();
    };
    const open  = (q = '') => { filter(q); list.hidden = false; input.setAttribute('aria-expanded', 'true'); chev.classList.add('rotate-180'); };
    const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); chev.classList.remove('rotate-180'); setActive(-1); };
    const pick  = o => { input.value = o.dataset.value; mark(); close(); };

    input.addEventListener('focus', () => open());
    input.addEventListener('click', () => list.hidden && open());
    input.addEventListener('input', () => { open(input.value); setActive(-1); });
    input.addEventListener('keydown', e => {
        const n = shown().length;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (list.hidden) open();
            if (n) setActive(e.key === 'ArrowDown' ? (active + 1) % n : (active - 1 + n) % n);
        } else if (e.key === 'Enter' && !list.hidden && active >= 0) {
            e.preventDefault(); pick(shown()[active]);
        } else if (e.key === 'Escape' && !list.hidden) {
            e.preventDefault(); close();
        }
    });
    box.querySelector('[data-combo-toggle]').addEventListener('click', () => {
        if (list.hidden) { input.focus(); open(); } else close();
    });
    opts.forEach(o => {
        o.addEventListener('mousedown', e => e.preventDefault()); // jangan hilangkan fokus input
        o.addEventListener('click', () => pick(o));
    });
    box.addEventListener('focusout', e => { if (!box.contains(e.relatedTarget)) close(); });
});
</script>
@endsection

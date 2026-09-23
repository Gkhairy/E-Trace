@extends('layouts.app')

@section('content')

@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

@include('supervisor._nav')

<h2 class="text-lg font-bold text-slate-900 mb-1">Sengketa terbuka</h2>
<p class="text-sm text-slate-500 mb-6 max-w-3xl">
    Item yang disengketakan pembeli. Sebagai arbiter (wallet pengawas) kamu bisa <b>melepas dana ke penjual</b> atau <b>refund ke pembeli</b> —
    keduanya tereksekusi on-chain & tercatat publik. Pastikan MetaMask memakai wallet arbiter.
</p>

@if($items->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center bg-white border border-dashed border-slate-300 rounded-3xl">
        <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-slate-600 font-medium">Tidak ada sengketa aktif</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($items as $it)
            <div class="bg-white border border-red-200 rounded-2xl shadow-sm p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex gap-3 min-w-0">
                        <div class="w-14 h-14 rounded-lg bg-white border border-slate-100 flex items-center justify-center p-1 shrink-0">
                            <img src="{{ $it->product?->thumbnail() ?? 'https://placehold.co/80x80/f1f5f9/94a3b8?text=—' }}" onerror="this.src='https://placehold.co/80x80/f1f5f9/94a3b8?text=—'" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $it->product->name ?? '—' }}</p>
                            <p class="text-sm text-blue-600 font-semibold">{{ $fmt($it->amount) }} TLKM</p>
                            <p class="text-xs text-slate-500 mt-1">
                                Toko: <b>{{ $it->product->store->name ?? '—' }}</b> ·
                                Pembeli: {{ $it->order->user->name ?? ('user#'.$it->order->user_id) }}
                            </p>
                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">Order {{ $it->order->order_id }} · item #{{ $it->item_index }}</p>
                            @if($it->order->shippingAddress)
                                <p class="text-[11px] text-slate-400 mt-0.5">Kirim ke: {{ $it->order->shippingAddress->recipient_name }}, {{ $it->order->shippingAddress->city }}</p>
                            @endif
                            @if($it->tracking_number)
                                <p class="text-[11px] text-slate-400 mt-0.5">Resi: {{ $it->tracking_number }}@if($it->courier) · {{ $it->courier }}@endif</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 shrink-0">
                        <a href="{{ config('chain.explorer_url') }}/tx/{{ $it->order->tx_hash }}" target="_blank" rel="noopener" class="text-xs text-slate-400 hover:text-blue-600 font-mono text-right">tx ↗</a>
                        <button onclick="arbiterResolve('{{ $it->order->order_id }}', {{ $it->item_index }}, {{ $it->id }}, 'release', this)"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition whitespace-nowrap">Lepas ke Penjual</button>
                        <button onclick="arbiterResolve('{{ $it->order->order_id }}', {{ $it->item_index }}, {{ $it->id }}, 'refund', this)"
                            class="bg-white hover:bg-red-50 border border-red-300 text-red-700 px-4 py-2 rounded-lg text-xs font-semibold transition whitespace-nowrap">Refund ke Pembeli</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection

@section('scripts')
<script>
async function arbiterResolve(orderId, index, itemId, action, btn) {
    const label = action === 'release' ? 'melepas dana ke penjual' : 'refund ke pembeli';
    const ok = await uiConfirm({
        title: 'Putusan Pengawas',
        message: `Yakin ${label} untuk item ini? Aksi ini tereksekusi on-chain & tidak bisa dibatalkan.`,
        confirmText: action === 'release' ? 'Lepas ke penjual' : 'Refund ke pembeli', danger: action === 'refund'
    });
    if (!ok) return;
    btn.disabled = true;
    txProgress.open('Eksekusi Putusan', ['Memeriksa jaringan', 'Eksekusi arbiter di blockchain']);
    try {
        txProgress.active(0); await checkNetwork(); txProgress.done(0);
        txProgress.active(1, 'Konfirmasi di MetaMask (wallet arbiter)…');
        const hash = action === 'release' ? await arbiterRelease(orderId, index) : await arbiterRefund(orderId, index);
        await fetch('/supervisor/resolve', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ order_item_id: itemId, status: action === 'release' ? 'completed' : 'refunded' })
        });
        txProgress.done(1);
        setTimeout(() => { txProgress.close(); uiAlert({ title:'Putusan Dieksekusi', message:`<a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type:'success' }).then(()=>location.reload()); }, 400);
    } catch (e) {
        txProgress.close();
        uiAlert({ title:'Gagal', message: niceError(e), type:'error' });
        btn.disabled = false;
    }
}
</script>
@endsection

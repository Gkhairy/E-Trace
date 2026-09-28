@extends('layouts.app')

@section('content')

@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

@include('supervisor._nav')

<h2 class="text-lg font-bold text-slate-900 mb-1">{{ __('Ditahan AI & klaim garansi') }}</h2>
<p class="text-sm text-slate-500 mb-6 max-w-3xl">
    {{ __('Order yang') }} <b class="text-slate-700">{{ __('ditahan') }}</b> {{ __('keeper AI (keyakinan rendah / di atas batas otomatis) atau') }}
    <b class="text-slate-700">{{ __('klaim garansi aktif') }}</b> {{ __('yang perlu ditinjau. Putusanmu dieksekusi on-chain sebagai arbiter.') }}
</p>

@if($orders->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center bg-white border border-dashed border-slate-300 rounded-3xl">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="text-slate-600 font-medium">{{ __('Tidak ada order yang perlu ditinjau.') }}</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($orders as $order)
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-xs text-slate-400">{{ $order->order_id }}</p>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ $order->created_at->format('d M Y H:i') }} ·
                            {{ __('Total') }} <b class="text-slate-800">{{ $fmt($order->total ?? $order->amount) }} TLKM</b> ·
                            {{ __('Pembeli') }} <span class="font-mono">{{ substr((string) $order->user?->wallet_address, 0, 8) }}…</span>
                        </p>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium border bg-slate-100 text-slate-600 border-slate-300">{{ __('insurance.settlement.'.$order->settlement_status) }}</span>
                            @if($order->is_insured)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium border bg-amber-50 text-amber-700 border-amber-200">🛡️ {{ __('insurance.status.'.$order->insurance_status) }}</span>
                            @endif
                            @if($order->promised_date)
                                <span class="text-[11px] text-slate-500">{{ __('insurance.promised') }}: <b>{{ $order->promised_date->format('d M Y') }}</b></span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($order->ai_reason)
                    <div class="px-5 py-2.5 bg-indigo-50/40 text-xs text-slate-600 border-b border-slate-100">
                        <b class="text-indigo-600">{{ __('insurance.ai_badge') }}:</b> {{ $order->ai_reason }}
                    </div>
                @endif

                @if($order->trackingEvents->isNotEmpty())
                    <div class="px-5 py-3 text-[11px] text-slate-500 border-b border-slate-100 space-y-1">
                        <p class="font-semibold text-slate-400 uppercase tracking-wide text-[10px]">{{ __('Riwayat tracking') }}</p>
                        @foreach($order->trackingEvents as $ev)
                            <p>· <span class="text-slate-400">[{{ $ev->created_at->format('d/m H:i') }}]</span> {{ $ev->raw_text }} @if($ev->source==='simulated')<span class="text-amber-500">({{ __('insurance.demo_sim') }})</span>@endif</p>
                        @endforeach
                    </div>
                @endif

                <div class="px-5 py-3 flex flex-wrap items-center gap-2">
                    @if($order->settlement_status === 'held')
                        <button onclick="settle('{{ $order->order_id }}','release',this)" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">{{ __('Release ke penjual') }}</button>
                        <button onclick="settle('{{ $order->order_id }}','refund',this)" class="bg-white hover:bg-amber-50 border border-slate-300 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition">{{ __('Refund ke pembeli') }}</button>
                    @endif
                    @if($order->is_insured && $order->insurance_status === 'active')
                        <span class="w-px h-5 bg-slate-200 mx-1"></span>
                        <button onclick="settle('{{ $order->order_id }}','approve_claim',this)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">🛡️ {{ __('Setujui klaim (bayar ongkir)') }}</button>
                        <button onclick="settle('{{ $order->order_id }}','reject_claim',this)" class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition">{{ __('Tolak klaim') }}</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection

@section('scripts')
<script>
async function settle(orderId, action, btn) {
    // Aksi ini memindahkan dana escrow on-chain dan tak bisa dibatalkan, jadi modal
    // menyebut ke mana dananya pergi — bukan sekadar "Yakin?".
    const id = uiEsc(orderId);
    const copy = {
        release:       { title: @json(__('Lepas dana ke penjual?')),  confirmText: @json(__('Ya, lepas ke penjual')), danger: false,
                         message: tr(@json(__('Dana escrow order :id akan dikirim ke penjual.')), { id: `<b>${id}</b>` }) },
        refund:        { title: @json(__('Kembalikan dana ke pembeli?')), confirmText: @json(__('Ya, refund')), danger: true,
                         message: tr(@json(__('Dana escrow order :id akan dikembalikan ke pembeli.')), { id: `<b>${id}</b>` }) },
        approve_claim: { title: @json(__('Setujui klaim garansi?')),  confirmText: @json(__('Ya, bayar klaim')), danger: false,
                         message: tr(@json(__('Ongkir order :id akan dibayar dari pool asuransi ke pembeli.')), { id: `<b>${id}</b>` }) },
        reject_claim:  { title: @json(__('Tolak klaim garansi?')),    confirmText: @json(__('Ya, tolak klaim')), danger: true,
                         message: tr(@json(__('Klaim order :id ditolak; pembeli tidak mendapat penggantian ongkir.')), { id: `<b>${id}</b>` }) },
    }[action];
    const ok = await uiConfirm({
        ...copy,
        message: copy.message + '<br><span class="text-xs text-slate-400">' + @json(__('Transaksi on-chain ini tidak bisa dibatalkan.')) + '</span>',
        cancelText: @json(__('Batal')),
    });
    if (!ok) return;
    if (btn) { btn.disabled = true; const t = btn.textContent; btn.dataset.t = t; btn.textContent = '⏳ ' + @json(__('Memproses…')); }
    try {
        const res = await fetch('/supervisor/settle', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ order_id: orderId, action })
        });
        const d = await res.json();
        if (res.ok && d.success) { showToast(@json(__('Berhasil diproses.')), 'success'); setTimeout(() => location.reload(), 800); }
        else { showToast(d.message || @json(__('Gagal.')), 'warn'); if (btn) { btn.disabled = false; btn.textContent = btn.dataset.t; } }
    } catch (e) { showToast(@json(__('Kendala jaringan.')), 'warn'); if (btn) { btn.disabled = false; btn.textContent = btn.dataset.t; } }
}
</script>
@endsection

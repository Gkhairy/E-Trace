@extends('layouts.app')

@section('content')

<div class="flex items-center gap-3 mb-2">
    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Riwayat Order</h1>
</div>
<p class="text-sm text-slate-500 mb-3 max-w-3xl">
    Setiap item ditahan di <b class="text-slate-700">escrow terpisah per penjual</b> di blockchain {{ config('chain.name', 'BNB Smart Chain Testnet') }}.
    Konfirmasi 1 item hanya melepas dana item itu ke penjualnya — item lain tidak terpengaruh.
</p>
{{-- H6: kebijakan refund yang jelas & adil --}}
<div class="flex items-start gap-2 text-xs text-slate-600 mb-5 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 max-w-3xl">
    <svg class="w-4 h-4 shrink-0 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span><b>Kebijakan refund:</b> refund bisa diajukan bila barang <b>tidak diterima setelah 3 hari</b>. Bila ada masalah (mis. salah kirim), ajukan <b>Sengketa</b> dengan bukti (resi/foto) — pengawas yang memutuskan, bukan refund otomatis sepihak. Ini melindungi pembeli dan penjual.</span>
</div>
<p id="autoRefreshNote" class="text-[11px] text-slate-400 mb-5 hidden">Memuat pembaruan…</p>

@if($orders->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center bg-white border border-dashed border-slate-300 rounded-3xl">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
        </div>
        <p class="text-slate-600 font-medium">Belum ada order</p>
        <a href="/products" class="text-sm text-blue-600 hover:underline mt-2">Mulai belanja →</a>
    </div>
@else
    @php
        $badgeMap = [
            'paid'                 => ['Menunggu Konfirmasi', 'bg-amber-50 text-amber-700 border-amber-200'],
            'pending_confirmation' => ['Menunggu Jaringan',   'bg-slate-100 text-slate-500 border-slate-200'],
            'completed'            => ['Selesai',             'bg-green-50 text-green-700 border-green-200'],
            'refunded'             => ['Dana Dikembalikan',   'bg-slate-100 text-slate-600 border-slate-200'],
            'disputed'             => ['Sengketa',            'bg-red-50 text-red-700 border-red-200'],
        ];
        $paidConf = (int) config('chain.paid_confirmations');
    @endphp
    <div class="space-y-5">
        @foreach($orders as $order)
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                {{-- HEADER ORDER --}}
                <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-mono text-xs text-slate-400">{{ $order->order_id }}</p>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $order->created_at->format('d M Y H:i') }} •
                            Total <b class="text-slate-800">{{ rtrim(rtrim(number_format($order->total ?? $order->amount, 2), '0'), '.') }} TLKM</b>
                        </p>
                        @php
                            if ($order->finalized_at)                     { $c = ['✓ Final', 'bg-green-50 text-green-700 border-green-200']; }
                            elseif ($order->status === 'paid')           { $c = ['Pembayaran terkonfirmasi', 'bg-blue-50 text-blue-700 border-blue-200']; }
                            elseif ($order->status === 'pending_confirmation') { $c = ['Menunggu konfirmasi '.$order->confirmations.'/'.$paidConf, 'bg-amber-50 text-amber-700 border-amber-200']; }
                            else { $c = null; }
                        @endphp
                        @if($c)
                            <span class="inline-flex items-center mt-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border {{ $c[1] }}">{{ $c[0] }}</span>
                        @endif
                    </div>
                    <a href="{{ config('chain.explorer_url') }}/tx/{{ $order->tx_hash }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1 text-green-600 hover:underline font-mono text-xs">
                        {{ Str::limit($order->tx_hash, 14) }}
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>

                {{-- ALAMAT (data pribadi, di DB saja) --}}
                @if($order->shippingAddress)
                    <div class="px-5 py-3 bg-slate-50/60 text-xs text-slate-500 border-b border-slate-100">
                        <span class="font-medium text-slate-600">{{ $order->shippingAddress->recipient_name }}</span>
                        ({{ $order->shippingAddress->phone }}) — {{ $order->shippingAddress->address }},
                        {{ $order->shippingAddress->city }} {{ $order->shippingAddress->postal_code }}
                    </div>
                @endif

                {{-- AI AUTO-SETTLEMENT + GARANSI TEPAT WAKTU --}}
                @php
                    $ss = $order->settlement_status;
                    $settleBadge = match ($ss) {
                        'released' => ['🤖 '.__('insurance.settlement.released'), 'bg-green-50 text-green-700 border-green-200'],
                        'refunded' => ['🤖 '.__('insurance.settlement.refunded'), 'bg-amber-50 text-amber-700 border-amber-200'],
                        'held'     => ['🕵️ '.__('insurance.settlement.held'), 'bg-slate-100 text-slate-600 border-slate-300'],
                        default    => null,
                    };
                    $insBadge = $order->is_insured ? match ($order->insurance_status) {
                        'paid'     => ['🛡️ '.__('insurance.status.paid'), 'bg-green-50 text-green-700 border-green-200'],
                        'rejected' => ['🛡️ '.__('insurance.status.rejected'), 'bg-slate-100 text-slate-500 border-slate-300'],
                        default    => ['🛡️ '.__('insurance.status.active'), 'bg-amber-50 text-amber-700 border-amber-200'],
                    } : null;
                    // Kontrol simulasi HANYA untuk pengawas — user biasa tak melihatnya
                    // (keeper tetap jalan di belakang lewat scheduler).
                    $isDemo = auth()->user()->isSupervisor();
                    // Tampilkan alasan AI hanya bila sudah ada hasil bermakna (bukan "belum ada tracking").
                    $showReason = $order->ai_reason
                        && ($ss !== 'pending' || in_array($order->insurance_status, ['paid', 'rejected'], true));
                @endphp
                @if($settleBadge || $insBadge || $showReason || $isDemo)
                <div class="px-5 py-3 border-b border-slate-100 bg-indigo-50/30">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($settleBadge)<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium border {{ $settleBadge[1] }}">{{ $settleBadge[0] }}</span>@endif
                        @if($insBadge)<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium border {{ $insBadge[1] }}">{{ $insBadge[0] }}</span>@endif
                        @if($order->promised_date)<span class="text-[11px] text-slate-500">{{ __('insurance.promised') }}: <b class="text-slate-600">{{ $order->promised_date->translatedFormat('d M Y') }}</b></span>@endif
                        @if($order->payout_tx)
                            <a href="{{ config('chain.explorer_url') }}/tx/{{ $order->payout_tx }}" target="_blank" rel="noopener" class="text-[11px] text-green-600 hover:underline">{{ __('insurance.payout_label') }} {{ rtrim(rtrim(number_format($order->payout_tlkm ?? 0, 2), '0'), '.') }} TLKM ↗</a>
                        @endif
                    </div>
                    @if($showReason)
                        <p class="text-[11px] text-slate-500 mt-1.5"><b class="text-indigo-600">{{ __('insurance.ai_badge') }}:</b> {{ $order->ai_reason }}</p>
                    @endif
                    @if($isDemo)
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mr-1">{{ __('insurance.demo_sim') }}</span>
                            <button onclick="simTrack('{{ $order->order_id }}','on_time',this)" class="text-[11px] bg-white hover:bg-green-50 border border-slate-200 hover:border-green-300 text-slate-600 px-2 py-1 rounded-md transition">Terkirim tepat waktu</button>
                            <button onclick="simTrack('{{ $order->order_id }}','late_courier',this)" class="text-[11px] bg-white hover:bg-amber-50 border border-slate-200 hover:border-amber-300 text-slate-600 px-2 py-1 rounded-md transition">Telat karena kurir</button>
                            <button onclick="simTrack('{{ $order->order_id }}','failed_address',this)" class="text-[11px] bg-white hover:bg-red-50 border border-slate-200 hover:border-red-300 text-slate-600 px-2 py-1 rounded-md transition">Gagal kirim — alamat salah</button>
                            <button onclick="simTrack('{{ $order->order_id }}','not_shipped_late',this)" class="text-[11px] bg-white hover:bg-amber-50 border border-slate-200 hover:border-amber-300 text-slate-600 px-2 py-1 rounded-md transition" title="Uji auto-refund">Penjual telat kirim</button>
                            <button onclick="simTrack('{{ $order->order_id }}','delivered_unconfirmed',this)" class="text-[11px] bg-white hover:bg-green-50 border border-slate-200 hover:border-green-300 text-slate-600 px-2 py-1 rounded-md transition" title="Uji auto-selesai (dilewati bila tujuan jauh)">Diterima, lupa konfirmasi</button>
                            <button onclick="runKeeper('{{ $order->order_id }}',this)" class="text-[11px] bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1 rounded-md font-semibold transition">▶ Jalankan keeper</button>
                        </div>
                    @endif
                </div>
                @endif

                {{-- ITEM DIKELOMPOKKAN PER PENJUAL --}}
                <div class="divide-y divide-slate-100">
                    @foreach($order->items->groupBy('seller_wallet') as $seller => $group)
                        <div class="px-5 py-4">
                            <p class="text-[11px] font-mono text-slate-400 mb-3">Penjual {{ substr($seller, 0, 8) }}…{{ substr($seller, -6) }}</p>
                            <div class="space-y-3">
                                @foreach($group as $item)
                                    @php $b = $badgeMap[$item->status] ?? $badgeMap['paid']; @endphp
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-white border border-slate-100 flex items-center justify-center p-1 shrink-0">
                                            <img src="{{ $item->product?->thumbnail() ?? 'https://placehold.co/80x80/f1f5f9/94a3b8?text=—' }}" onerror="this.src='https://placehold.co/80x80/f1f5f9/94a3b8?text=—'" class="max-w-full max-h-full object-contain">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-800 line-clamp-1">{{ $item->product->name ?? '—' }}</p>
                                            <p class="text-xs text-blue-600 font-semibold">{{ rtrim(rtrim(number_format($item->amount, 2), '0'), '.') }} TLKM</p>
                                            @php
                                                $fLabel = ['pending'=>'Belum dikirim','processing'=>'Sedang diproses','shipped'=>'Dikirim','delivered'=>'Diterima'][$item->fulfillment_status ?? 'pending'] ?? null;
                                            @endphp
                                            @if($item->fulfillment_status && $item->fulfillment_status !== 'pending')
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    {{ $fLabel }}@if($item->tracking_number) · Resi <b class="text-slate-700">{{ $item->tracking_number }}</b>@if($item->courier) ({{ $item->courier }})@endif @endif
                                                </p>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $b[1] }} whitespace-nowrap shrink-0">{{ $b[0] }}</span>
                                        <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                                            @if($item->status === 'paid')
                                                @php $isComm = $order->community_wallet_id ? 'true' : 'false'; @endphp
                                                <button onclick="doConfirmItem('{{ $order->order_id }}', {{ $item->item_index }}, this, {{ $isComm }})"
                                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition whitespace-nowrap">Konfirmasi Terima</button>
                                                <button onclick="doRefundItem('{{ $order->order_id }}', {{ $item->item_index }}, this, {{ $isComm }})"
                                                    class="bg-white hover:bg-amber-50 border border-slate-300 hover:border-amber-400 text-slate-700 hover:text-amber-700 px-3 py-1.5 rounded-lg text-xs font-medium transition">Refund</button>
                                                <button onclick="doDisputeItem('{{ $order->order_id }}', {{ $item->item_index }}, this, {{ $isComm }})"
                                                    class="bg-white hover:bg-red-50 border border-slate-300 hover:border-red-400 text-slate-700 hover:text-red-700 px-3 py-1.5 rounded-lg text-xs font-medium transition">Sengketa</button>
                                            @elseif($item->status === 'completed')
                                                @if($item->review)
                                                    <span class="text-xs text-amber-500 font-medium whitespace-nowrap">★ {{ $item->review->rating }}/5</span>
                                                @else
                                                    <button onclick="openReview({{ $item->id }}, @js($item->product->name ?? 'Produk'))"
                                                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition whitespace-nowrap">Beri Ulasan</button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    {{-- H1: TAHAPAN STATUS (timeline) --}}
                                    @include('orders.partials.timeline', ['item' => $item])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
@endif

@endsection

@section('scripts')
<script>
// ===== DEMO: simulasi status kirim + jalankan keeper (khusus dev/pengawas) =====
async function simTrack(orderId, preset, btn) {
    if (btn) btn.disabled = true;
    try {
        const res = await fetch('/orders/simulate-tracking', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ order_id: orderId, preset })
        });
        const d = await res.json();
        showToast(d.message || (res.ok ? 'Event tracking simulasi ditambahkan.' : 'Gagal.'), res.ok ? 'success' : 'warn');
    } catch (e) { showToast('Gagal menambah event.', 'warn'); }
    if (btn) btn.disabled = false;
}
async function runKeeper(orderId, btn) {
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Menilai…'; }
    try {
        const res = await fetch('/orders/run-keeper', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ order_id: orderId })
        });
        const d = await res.json();
        showToast(d.message || 'Keeper dijalankan.', res.ok ? 'success' : 'warn');
        setTimeout(() => location.reload(), 900);
    } catch (e) { showToast('Gagal menjalankan keeper.', 'warn'); if (btn) { btn.disabled = false; btn.textContent = '▶ Jalankan keeper'; } }
}

// ===== H3: AUTO-REFRESH RINGAN (polling fetch) =====
// Ambil "signature" state order tiap 30 dtk; kalau berubah & tidak ada transaksi
// berjalan, muat ulang sekali supaya order/item baru muncul otomatis.
(function () {
    let baseSig = null;
    async function poll() {
        try {
            const res = await fetch('/orders/updates', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const d = await res.json();
            if (baseSig === null) { baseSig = d.sig; return; }
            if (d.sig !== baseSig && !window.__txBusy) {
                const note = document.getElementById('autoRefreshNote');
                if (note) note.classList.remove('hidden');
                setTimeout(() => location.reload(), 600);
            }
        } catch (_) { /* diam: jaringan sesekali gagal tidak fatal */ }
    }
    poll();                       // ambil baseline
    setInterval(poll, 30000);     // cek tiap 30 detik
})();

// Simpan status item baru di DB (mengikuti aksi on-chain per item).
async function markItemStatus(orderId, itemIndex, status) {
    try {
        await fetch("/order/item-status", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF_TOKEN },
            body: JSON.stringify({ order_id: orderId, item_index: itemIndex, status })
        });
    } catch (_) { /* tidak fatal: status on-chain tetap sumber kebenaran */ }
}

// Order yang dibayar DANA KOMUNITAS: pembeli on-chain = dompet komunitas, sehingga
// konfirmasi/refund/sengketa ditandatangani backend memakai kunci komunitas (+PIN).
async function communityOrderAction(orderId, index, action, btn, labels) {
    const pin = await askPin(labels.pin);
    if (!pin) return;
    btn.disabled = true;
    txProgress.open(labels.title, ['Menandatangani dengan dompet komunitas']);
    try {
        txProgress.active(0);
        const res = await fetch('/community/order-action', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ order_id: orderId, item_index: index, action, pin }),
        });
        const d = await res.json().catch(() => ({}));
        if (!res.ok || !d.success) throw new Error(d.message || 'Gagal memproses.');
        txProgress.done(0);
        setTimeout(() => {
            txProgress.close();
            uiAlert({ title: labels.ok, message: `Selesai.<br><a href="${EXPLORER_URL}/tx/${d.tx_hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type: 'success' })
                .then(() => location.reload());
        }, 400);
    } catch (e) {
        txProgress.close();
        uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' });
        btn.disabled = false;
    }
}

async function doConfirmItem(orderId, index, btn, isCommunity = false) {
    const ok = await uiConfirm({
        title: 'Konfirmasi Item Ini',
        message: isCommunity
            ? 'Barang ini sudah diterima? Dana <b class="text-slate-900">item ini</b> dilepas ke penjual dari <b>kas komunitas</b>.'
            : 'Barang ini sudah diterima? Dana <b class="text-slate-900">item ini saja</b> dilepas ke penjualnya. Item lain tidak terpengaruh.',
        confirmText: 'Konfirmasi & lepas dana'
    });
    if (!ok) return;
    if (isCommunity) return communityOrderAction(orderId, index, 'confirm', btn,
        { pin: 'Konfirmasi Terima', title: 'Melepas Dana Item', ok: 'Item Dikonfirmasi' });
    btn.disabled = true;
    txProgress.open('Melepas Dana Item', ['Memeriksa jaringan', 'Mengonfirmasi item di blockchain']);
    try {
        txProgress.active(0); await checkNetwork(); txProgress.done(0);
        txProgress.active(1, 'Konfirmasi di MetaMask…');
        const hash = await confirmItem(orderId, index);
        await markItemStatus(orderId, index, 'completed');
        txProgress.done(1);
        setTimeout(() => { txProgress.close(); uiAlert({ title:'Item Dikonfirmasi', message:`Dana item dilepas ke penjual.<br><a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type:'success' }).then(()=>location.reload()); }, 400);
    } catch (e) {
        txProgress.close();
        uiAlert({ title:'Gagal', message: niceError(e), type:'error' });
        btn.disabled = false;
    }
}

async function doRefundItem(orderId, index, btn, isCommunity = false) {
    const ok = await uiConfirm({
        title: 'Refund Item Ini',
        message: isCommunity
            ? 'Refund hanya bisa <b class="text-slate-900">setelah lewat 3 hari</b>. Dana akan kembali ke <b>kas komunitas</b>. Lanjutkan?'
            : 'Refund item ini hanya bisa <b class="text-slate-900">setelah lewat 3 hari</b> dan belum kamu konfirmasi. Lanjutkan?',
        confirmText: 'Ajukan refund', danger: true
    });
    if (!ok) return;
    if (isCommunity) return communityOrderAction(orderId, index, 'refund', btn,
        { pin: 'Ajukan Refund', title: 'Mengembalikan Dana', ok: 'Refund Diajukan' });
    btn.disabled = true;
    txProgress.open('Memproses Refund Item', ['Memeriksa jaringan', 'Mengajukan refund item']);
    try {
        txProgress.active(0); await checkNetwork(); txProgress.done(0);
        txProgress.active(1, 'Konfirmasi di MetaMask…');
        const hash = await refundItem(orderId, index);
        await markItemStatus(orderId, index, 'refunded');
        txProgress.done(1);
        setTimeout(() => { txProgress.close(); uiAlert({ title:'Refund Berhasil', message:`Dana item dikembalikan ke wallet kamu.<br><a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type:'success' }).then(()=>location.reload()); }, 400);
    } catch (e) {
        txProgress.close();
        uiAlert({ title:'Gagal', message: niceError(e), type:'error' });
        btn.disabled = false;
    }
}

// ===== SENGKETA (pembeli) =====
async function doDisputeItem(orderId, index, btn, isCommunity = false) {
    const ok = await uiConfirm({
        title: 'Ajukan Sengketa',
        message: 'Ada masalah dengan item ini? Dana <b class="text-slate-900">tetap ditahan escrow</b> sampai <b>pengawas</b> memutus (lepas ke penjual / refund). Lanjutkan?',
        confirmText: 'Ajukan sengketa', danger: true
    });
    if (!ok) return;
    if (isCommunity) return communityOrderAction(orderId, index, 'dispute', btn,
        { pin: 'Ajukan Sengketa', title: 'Mencatat Sengketa', ok: 'Sengketa Diajukan' });
    btn.disabled = true;
    txProgress.open('Mengajukan Sengketa', ['Memeriksa jaringan', 'Mencatat sengketa di blockchain']);
    try {
        txProgress.active(0); await checkNetwork(); txProgress.done(0);
        txProgress.active(1, 'Konfirmasi di MetaMask…');
        const hash = await disputeItem(orderId, index);
        await markItemStatus(orderId, index, 'disputed');
        txProgress.done(1);
        setTimeout(() => { txProgress.close(); uiAlert({ title:'Sengketa Diajukan', message:`Pengawas akan meninjau. Dana tetap aman di escrow.<br><a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type:'success' }).then(()=>location.reload()); }, 400);
    } catch (e) {
        txProgress.close();
        uiAlert({ title:'Gagal', message: niceError(e), type:'error' });
        btn.disabled = false;
    }
}

// ===== ULASAN (pembeli) =====
let _reviewItemId = null, _reviewRating = 5;
function openReview(itemId, name) {
    _reviewItemId = itemId; _reviewRating = 5;
    openModal(`
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Beri Ulasan</h3>
            <p class="text-sm text-slate-500 mb-4">${name}</p>
            <div id="starRow" class="flex gap-1 mb-4 text-3xl text-amber-400">
                ${[1,2,3,4,5].map(i=>`<span data-star="${i}" style="cursor:pointer">★</span>`).join('')}
            </div>
            <textarea id="reviewComment" rows="3" placeholder="Bagaimana produknya?" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm outline-none focus:border-blue-500 resize-none"></textarea>
            <div class="flex gap-3 mt-4">
                <button onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Batal</button>
                <button onclick="submitReview()" class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Kirim Ulasan</button>
            </div>
        </div>`);
    document.querySelectorAll('#starRow [data-star]').forEach(s => s.onclick = () => { _reviewRating = +s.dataset.star; paintStars(); });
    paintStars();
}
function paintStars() {
    document.querySelectorAll('#starRow [data-star]').forEach(s => s.style.opacity = (+s.dataset.star <= _reviewRating) ? '1' : '0.3');
}
async function submitReview() {
    const comment = document.getElementById('reviewComment').value;
    try {
        const res = await fetch('/review', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN}, body: JSON.stringify({ order_item_id: _reviewItemId, rating: _reviewRating, comment }) });
        const data = await res.json();
        if (res.ok && data.success) { closeModal(); showToast('Ulasan terkirim', 'success'); setTimeout(()=>location.reload(), 700); }
        else { showToast(data.message || 'Gagal mengirim ulasan', 'error'); }
    } catch (e) { showToast('Gagal mengirim ulasan', 'error'); }
}
</script>
@endsection

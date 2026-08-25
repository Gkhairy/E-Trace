@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<div class="max-w-md mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 text-center">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m-6-4h8m0 0l-3-3m3 3l-3 3"/></svg>
        </div>

        <p class="text-sm text-slate-500">Permintaan uang dari</p>
        <div class="flex items-center justify-center gap-1.5 mt-0.5">
            <h1 class="text-lg font-bold text-slate-900">{{ $identity['name'] }}</h1>
            @if($identity['verified'])
                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
            @endif
        </div>

        @if($req->amount)
            <p class="text-3xl font-extrabold text-slate-900 mt-4">{{ $fmt($req->amount) }} <span class="text-base text-blue-600">TLKM</span></p>
        @else
            <p class="text-sm text-slate-400 mt-4">Nominal bebas</p>
        @endif
        @if($req->note)<p class="text-sm text-slate-500 mt-1">"{{ $req->note }}"</p>@endif

        <div class="mt-5 pt-5 border-t border-slate-100 text-left">
            @auth
                @if(!$req->amount)
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nominal (TLKM)</label>
                    <input id="payAmount" type="number" min="0" step="any" placeholder="mis. 50" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-4">
                @endif
                <button id="payBtn" onclick="doPay()" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Bayar sekarang</button>
                <p class="text-[11px] text-slate-400 mt-3 text-center">Dibayar langsung ke wallet penerima via MetaMask.</p>
            @else
                <a href="/login?next=/pay/{{ $req->code }}" class="block w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold text-center transition shadow-sm">Login untuk membayar</a>
            @endauth
        </div>
    </div>
    <p class="text-center text-xs text-slate-400 mt-4">Penerima: <a href="/explorer/{{ $recipient }}" class="font-mono text-blue-600 hover:underline">{{ substr($recipient,0,10) }}…{{ substr($recipient,-6) }}</a></p>
</div>

@endsection

@section('scripts')
<script>
    const RECIPIENT  = @json($recipient);
    const REQUEST_ID = @json($req->id);
    const FIXED_AMT  = @json($req->amount);

    async function doPay() {
        const amt = FIXED_AMT ? parseFloat(FIXED_AMT) : parseFloat(document.getElementById('payAmount').value);
        if (!amt || amt <= 0) { showToast('Masukkan nominal yang valid.', 'warn'); return; }
        const ok = await uiConfirm({ title: 'Bayar Permintaan', message: `Kirim <b class="text-blue-600">${amt} TLKM</b> ke penerima?`, confirmText: 'Ya, bayar' });
        if (!ok) return;
        let pin = null;
        if (IS_EMBEDDED) { pin = await askPin('Bayar Permintaan'); if (!pin) return; }
        const btn = document.getElementById('payBtn'); btn.disabled = true;
        txProgress.open('Membayar', ['Memeriksa jaringan', IS_EMBEDDED ? 'Tanda tangan dengan PIN' : 'Konfirmasi di MetaMask', 'Mencatat']);
        try {
            txProgress.active(0); if (!IS_EMBEDDED) await checkNetwork(); txProgress.done(0);
            txProgress.active(1, IS_EMBEDDED ? 'Menandatangani & menyiarkan…' : 'Konfirmasi transfer di MetaMask…');
            const hash = IS_EMBEDDED ? await pinTx('/pin/transfer', { pin, to: RECIPIENT, amount: amt }) : await sendTLKM(RECIPIENT, amt);
            txProgress.done(1);
            txProgress.active(2, 'Verifikasi on-chain…');
            await fetch('/wallet/send', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ tx_hash: hash, request_id: REQUEST_ID }) });
            txProgress.done(2);
            setTimeout(() => { txProgress.close(); uiAlert({ title: 'Pembayaran Berhasil', message: `${amt} TLKM terkirim ke penerima.<br><a href="https://sepolia.etherscan.io/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type: 'success' }).then(() => window.location.href = '/wallet'); }, 400);
        } catch (e) {
            txProgress.close();
            uiAlert({ title: 'Pembayaran gagal', message: niceError(e), type: 'error' });
            btn.disabled = false;
        }
    }
</script>
@endsection

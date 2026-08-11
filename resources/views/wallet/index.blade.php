@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<h1 class="text-2xl font-bold text-slate-900 mb-1">Dompet TLKM</h1>
<p class="text-sm text-slate-500 mb-6">Kirim TLKM ke siapa saja, atau buat permintaan uang (link &amp; QR) agar orang lain bisa membayarmu.</p>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- KIRIM --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">Kirim TLKM</h2>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Wallet tujuan</label>
        <input id="sendTo" type="text" placeholder="0x…" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm font-mono mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nominal (TLKM)</label>
        <input id="sendAmount" type="number" min="0" step="any" placeholder="mis. 100" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Catatan (opsional)</label>
        <input id="sendNote" type="text" maxlength="120" placeholder="mis. bayar patungan" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-4">
        <button id="sendBtn" onclick="doSend()" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Kirim</button>
    </div>

    {{-- MINTA UANG --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">Minta Uang</h2>
        <form action="/wallet/requests" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nominal (opsional)</label>
                <input name="amount" type="number" min="0" step="any" placeholder="Kosongkan untuk bebas" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Catatan (opsional)</label>
                <input name="note" type="text" maxlength="120" placeholder="mis. iuran kelas" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
            </div>
            <button class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition">Buat link permintaan</button>
        </form>

        @if($requests->isNotEmpty())
            <div class="mt-5 pt-4 border-t border-slate-100 space-y-2">
                <p class="text-xs font-medium text-slate-500 mb-1">Permintaan kamu</p>
                @foreach($requests as $r)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <span class="font-semibold text-slate-800">{{ $r->amount ? $fmt($r->amount).' TLKM' : 'Nominal bebas' }}</span>
                            @if($r->note)<span class="text-slate-400 text-xs">· {{ $r->note }}</span>@endif
                        </div>
                        <button onclick="shareReq(@js($r->code), @js($r->amount ? $fmt($r->amount).' TLKM' : 'bebas'))" class="shrink-0 text-xs text-blue-600 hover:underline font-medium">Bagikan / QR</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- RIWAYAT --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-6">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">Riwayat transfer</h2></div>
    @if($transfers->isEmpty())
        <p class="px-5 py-10 text-center text-sm text-slate-400">Belum ada transfer.</p>
    @else
        <div class="divide-y divide-slate-100">
            @foreach($transfers as $t)
                <div class="px-5 py-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $t['dir']==='out' ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-600' }}">
                            @if($t['dir']==='out')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M17 7H8m9 0v9"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7 17M7 17h9m-9 0V8"/></svg>
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-800 truncate">{{ $t['dir']==='out' ? 'Ke' : 'Dari' }} <span class="font-medium">{{ $t['other']['name'] }}</span></p>
                            @if($t['note'])<p class="text-[11px] text-slate-400 truncate">{{ $t['note'] }}</p>@endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-semibold {{ $t['dir']==='out' ? 'text-red-500' : 'text-green-600' }}">{{ $t['dir']==='out' ? '−' : '+' }}{{ $fmt($t['amount']) }} TLKM</p>
                        <a href="https://sepolia.etherscan.io/tx/{{ $t['tx'] }}" target="_blank" rel="noopener" class="text-[11px] text-slate-400 hover:text-blue-600 font-mono">{{ $t['at']->format('d M H:i') }} ↗</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
async function doSend() {
    const to = (document.getElementById('sendTo').value || '').trim();
    const amt = parseFloat(document.getElementById('sendAmount').value);
    const note = (document.getElementById('sendNote').value || '').trim();
    if (!/^0x[a-fA-F0-9]{40}$/.test(to)) { showToast('Wallet tujuan tidak valid.', 'warn'); return; }
    if (!amt || amt <= 0) { showToast('Masukkan nominal yang valid.', 'warn'); return; }
    const ok = await uiConfirm({ title: 'Kirim TLKM', message: `Kirim <b class="text-blue-600">${amt} TLKM</b> ke:<br><span class="font-mono text-xs break-all">${to}</span>`, confirmText: 'Ya, kirim' });
    if (!ok) return;
    const btn = document.getElementById('sendBtn'); btn.disabled = true;
    txProgress.open('Kirim TLKM', ['Memeriksa jaringan', 'Konfirmasi di MetaMask', 'Mencatat']);
    try {
        txProgress.active(0); await checkNetwork(); txProgress.done(0);
        txProgress.active(1, 'Konfirmasi transfer di MetaMask…');
        const hash = await sendTLKM(to, amt);
        txProgress.done(1);
        txProgress.active(2, 'Verifikasi on-chain…');
        await fetch('/wallet/send', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ tx_hash: hash, note }) });
        txProgress.done(2);
        setTimeout(() => { txProgress.close(); uiAlert({ title: 'TLKM Terkirim', message: `${amt} TLKM terkirim.<br><a href="https://sepolia.etherscan.io/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type: 'success' }).then(() => location.reload()); }, 400);
    } catch (e) {
        txProgress.close();
        uiAlert({ title: 'Gagal mengirim', message: niceError(e), type: 'error' });
        btn.disabled = false;
    }
}

function shareReq(code, amountLabel) {
    const url = window.location.origin + '/pay/' + code;
    openModal(`
        <div class="p-6 text-center">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Minta Uang</h3>
            <p class="text-sm text-slate-500 mb-4">Nominal: ${amountLabel}</p>
            <div id="qrBox" class="flex justify-center mb-4"></div>
            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                <input value="${url}" readonly class="flex-1 bg-transparent text-xs text-slate-600 outline-none" id="payLink">
                <button onclick="navigator.clipboard.writeText('${url}').then(()=>showToast('Link disalin','success'))" class="text-xs text-blue-600 font-medium shrink-0">Salin</button>
            </div>
            <button onclick="closeModal()" class="mt-4 w-full py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Tutup</button>
        </div>`);
    setTimeout(() => { try { new QRCode(document.getElementById('qrBox'), { text: url, width: 180, height: 180, colorDark: '#0f172a', colorLight: '#ffffff' }); } catch(e){} }, 30);
}
</script>
@endsection

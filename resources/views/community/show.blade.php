@extends('layouts.app')

@section('content')
@php
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
    $signers = collect($members)->where('is_signer', true);
    $lowGas = ($gasEth !== null && $gasEth < 0.0003);
@endphp

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="/community" class="hover:text-blue-600 transition">Dompet Komunitas</a>
    <span class="text-slate-300">/</span><span class="text-slate-700 truncate">{{ $wallet->name }}</span>
</nav>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900">{{ $wallet->name }}</h1>
                <span class="text-[11px] px-2 py-0.5 rounded-full {{ $wallet->isMultisig() ? 'bg-violet-50 text-violet-700 border border-violet-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">{{ $wallet->modeLabel() }}</span>
            </div>
            <a href="https://sepolia.etherscan.io/address/{{ $wallet->address }}" target="_blank" class="text-xs text-blue-600 hover:underline font-mono">{{ $wallet->address }} ↗</a>
        </div>
        <div class="text-right">
            <p class="text-xs text-slate-500">Saldo dompet</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $balance !== null ? $fmt($balance) : '—' }} <span class="text-sm text-blue-600">TLKM</span></p>
            <p class="text-[11px] {{ $lowGas ? 'text-amber-600' : 'text-slate-400' }} mt-0.5">Gas: {{ $gasEth !== null ? rtrim(rtrim(number_format($gasEth, 5), '0'), '.').' ETH' : '—' }}</p>
        </div>
    </div>
    @if($wallet->description)<p class="text-sm text-slate-500 mt-3">{{ $wallet->description }}</p>@endif
    <button onclick="doDeposit()" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">Setor TLKM</button>
</div>

{{-- Peringatan gas: dompet komunitas butuh ETH untuk mengirim/menarik dana --}}
@if($lowGas)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 mb-6 text-sm flex items-start gap-3">
        <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <div>
            <b>Dompet belum punya ETH untuk biaya gas.</b> Menarik/mengirim dana akan gagal sampai dompet diisi sedikit ETH Sepolia.
            Kirim ETH ke <a href="https://sepolia.etherscan.io/address/{{ $wallet->address }}" target="_blank" class="font-mono underline break-all">{{ $wallet->address }}</a>
            (mis. dari <a href="https://sepoliafaucet.com" target="_blank" class="underline">faucet Sepolia</a>), lalu muat ulang halaman.
        </div>
    </div>
@endif

@if($wallet->mode === 'A')
    {{-- MODE A: anggota & jatah --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-900">Anggota &amp; Jatah/bulan</h2>
            <button onclick="doWithdraw()" class="bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Tarik jatahku</button>
        </div>
        <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 text-left"><tr><th class="px-5 py-2">Anggota</th><th class="px-5 py-2">Limit/bulan</th><th class="px-5 py-2">Sisa bulan ini</th></tr></thead>
            <tbody>
                @foreach($members as $m)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-2">{{ $m['name'] }} @if($m['is_me'])<span class="text-[10px] text-blue-600">(kamu)</span>@endif</td>
                        <td class="px-5 py-2">{{ $fmt($m['limit']) }} TLKM</td>
                        <td class="px-5 py-2 text-green-600">{{ $m['remaining'] !== null ? $fmt($m['remaining']).' TLKM' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    {{-- MODE B: multisig --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="font-bold text-slate-900 mb-1">Multisig {{ $wallet->threshold }} dari {{ $signers->count() }} penanda tangan</h2>
        <p class="text-xs text-slate-500 mb-3">Kirim dana butuh persetujuan <b>{{ $wallet->threshold }}</b> dari <b>{{ $signers->count() }}</b> penanda tangan yang ditunjuk.</p>

        {{-- Daftar anggota + penanda tangan --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($members as $m)
                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full border {{ $m['is_signer'] ? 'bg-violet-50 text-violet-700 border-violet-200' : 'bg-slate-50 text-slate-500 border-slate-200' }}">
                    {{ $m['name'] }}@if($m['is_me']) (kamu)@endif
                    @if($m['is_signer'])<span title="Penanda tangan wajib">🖊️</span>@endif
                </span>
            @endforeach
        </div>

        <button onclick="doPropose()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">Usulkan Kirim Dana</button>
        @unless($iAmSigner)
            <p class="text-[11px] text-slate-400 mt-2">Kamu bukan penanda tangan — bisa mengusulkan, tapi tidak menghitung sebagai persetujuan.</p>
        @endunless
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">Usulan</h2></div>
        @if($proposals->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada usulan.</p>
        @else
            @foreach($proposals as $p)
                <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-800">{{ $fmt($p['amount']) }} TLKM → <b>{{ $p['to_name'] ?: (substr($p['to_wallet'],0,8).'…'.substr($p['to_wallet'],-4)) }}</b></p>
                        <p class="text-[11px] text-slate-400">{{ $p['approvals'] }}/{{ $wallet->threshold }} setuju @if($p['note'])· {{ $p['note'] }}@endif</p>
                    </div>
                    <div class="shrink-0">
                        @if($p['status'] === 'executed')
                            <a href="https://sepolia.etherscan.io/tx/{{ $p['tx'] }}" target="_blank" class="text-xs text-green-600 hover:underline">Terkirim ↗</a>
                        @elseif($p['approved_by_me'])
                            <span class="text-xs text-slate-400">Kamu sudah setuju</span>
                        @elseif($iAmSigner)
                            <button onclick="doApprove({{ $p['id'] }})" class="text-xs text-blue-600 font-semibold">Setujui</button>
                        @else
                            <span class="text-xs text-slate-300">Bukan penanda tangan</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endif

@endsection

@section('scripts')
<script>
const WID = @json($wallet->id);
const CADDR = @json($wallet->address);

async function post(url, body) {
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify(body) });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) throw new Error(data.message || 'Aksi gagal.');
    return data;
}
function ok(hash) { txProgress.close(); uiAlert({ title: 'Berhasil', message: hash ? `<a href="https://sepolia.etherscan.io/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>` : 'Tersimpan.', type: 'success' }).then(() => location.reload()); }
function fail(e) { txProgress.close(); uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' }); }

// Setor: transfer TLKM milik SENDIRI ke alamat komunitas (PIN embedded / MetaMask).
async function doDeposit() {
    const amt = await uiPrompt({ title: 'Setor ke Komunitas', label: 'Jumlah TLKM yang disetor:', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Setor' });
    if (amt === null || +amt <= 0) return;
    let pin = null;
    if (IS_EMBEDDED) { pin = await askPin('Setor ke Komunitas'); if (!pin) return; }
    txProgress.open('Setor TLKM', ['Menandatangani', 'Menyiarkan']);
    try {
        txProgress.active(0);
        const hash = IS_EMBEDDED ? await pinTx('/pin/transfer', { pin, to: CADDR, amount: amt }) : await sendTLKM(CADDR, amt);
        txProgress.done(0); txProgress.active(1); txProgress.done(1); ok(hash);
    } catch (e) { fail(e); }
}

// Mode A: tarik jatah (ditandatangani backend pakai kunci komunitas).
async function doWithdraw() {
    const amt = await uiPrompt({ title: 'Tarik Jatah', label: 'Jumlah TLKM yang ditarik (≤ sisa jatahmu):', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Tarik' });
    if (amt === null || +amt <= 0) return;
    const pin = await askPin('Tarik Jatah'); if (!pin) return;
    txProgress.open('Menarik dana', ['Verifikasi PIN', 'Menyiarkan']);
    try { txProgress.active(0); const d = await post('/community/withdraw', { id: WID, amount: amt, pin }); txProgress.done(0); txProgress.active(1); txProgress.done(1); ok(d.tx_hash); } catch (e) { fail(e); }
}

// Mode B: usulkan / setujui.
async function doPropose() {
    const to = await uiPrompt({ title: 'Usulkan Kirim Dana', label: 'Kirim ke (No HP / wallet 0x…):', placeholder: '08xxxx atau 0x…', confirmText: 'Lanjut' });
    if (to === null || !to.trim()) return;
    const amt = await uiPrompt({ title: 'Usulkan Kirim Dana', label: 'Jumlah TLKM:', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Lanjut' });
    if (amt === null || +amt <= 0) return;
    const note = await uiPrompt({ title: 'Usulkan Kirim Dana', label: 'Catatan (opsional):', placeholder: 'mis. bayar sewa', confirmText: 'Buat Usulan' });
    if (note === null) return; // batal
    const pin = await askPin('Usulkan Kirim'); if (!pin) return;
    txProgress.open('Membuat usulan', ['Verifikasi PIN']);
    try { txProgress.active(0); await post('/community/propose', { id: WID, to, amount: amt, note, pin }); txProgress.done(0); ok(null); } catch (e) { fail(e); }
}
async function doApprove(pid) {
    const pin = await askPin('Setujui Usulan'); if (!pin) return;
    txProgress.open('Menyetujui', ['Verifikasi PIN', 'Eksekusi bila cukup']);
    try { txProgress.active(0); const d = await post('/community/approve', { proposal_id: pid, pin }); txProgress.done(0); txProgress.active(1); txProgress.done(1); ok(d.tx_hash); } catch (e) { fail(e); }
}
</script>
@endsection

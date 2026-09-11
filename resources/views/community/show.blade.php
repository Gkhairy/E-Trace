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
            <b>Dompet belum punya gas (ETH testnet).</b> Ini <b>bukan biaya uang nyata</b> — ETH Sepolia gratis dari faucet, hanya untuk testnet.
            Isi sedikit ke <a href="https://sepolia.etherscan.io/address/{{ $wallet->address }}" target="_blank" class="font-mono underline break-all">{{ $wallet->address }}</a>
            (dari <a href="https://sepoliafaucet.com" target="_blank" class="underline">faucet Sepolia</a>) lalu muat ulang, atau admin jalankan <code class="bg-white/60 px-1 rounded">php artisan community:fund-gas</code>.
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
    {{-- MODE B: multisig (persetujuan BULAT) --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="font-bold text-slate-900 mb-1">Multisig — persetujuan bulat ({{ $signers->count() }} penanda tangan)</h2>
        <p class="text-xs text-slate-500 mb-3">Setiap aksi (kirim dana, undang/kick anggota, transfer kepemilikan) butuh persetujuan <b>semua {{ $signers->count() }}</b> penanda tangan.</p>

        {{-- Daftar anggota + penanda tangan + pemilik --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($members as $m)
                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full border {{ $m['is_signer'] ? 'bg-violet-50 text-violet-700 border-violet-200' : 'bg-slate-50 text-slate-500 border-slate-200' }}">
                    @if($m['is_owner'])<span title="Pemilik">👑</span>@endif
                    {{ $m['name'] }}@if($m['is_me']) (kamu)@endif
                    @if($m['is_signer'])<span title="Penanda tangan wajib">🖊️</span>@endif
                    @if($iAmOwner && !$m['is_owner'])
                        <button onclick="doKick({{ $m['user_id'] }}, @js($m['name']))" title="Usulkan keluarkan" class="ml-0.5 text-slate-400 hover:text-red-600">✕</button>
                    @endif
                </span>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            <button onclick="doPropose()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">Usulkan Kirim Dana</button>
            @if($iAmOwner)
                <button onclick="doInvite()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-semibold">Undang Anggota</button>
                <button onclick="doTransferOwner()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-semibold">Transfer Kepemilikan</button>
            @endif
        </div>
        @if($iAmOwner)
            <p class="text-[11px] text-slate-400 mt-2">👑 Kamu <b>pemilik</b>: bisa mengusulkan undang/kick & transfer kepemilikan — tapi tetap butuh persetujuan semua penanda tangan.</p>
        @endif
        @unless($iAmSigner)
            <p class="text-[11px] text-slate-400 mt-2">Kamu bukan penanda tangan — bisa mengusulkan, tapi tidak menghitung sebagai persetujuan.</p>
        @endunless
    </div>

    {{-- Usulan (semua jenis) --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">Usulan</h2></div>
        @if($proposals->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada usulan.</p>
        @else
            @foreach($proposals as $p)
                @php
                    $label = match($p['type']) {
                        'add_member'         => '👥 Undang <b>'.e($p['target_name'] ?: $p['to_name']).'</b>'.($p['as_signer'] ? ' (sebagai penanda tangan)' : ' (anggota biasa)'),
                        'remove_member'      => '👋 Keluarkan <b>'.e($p['target_name'] ?: $p['to_name']).'</b>',
                        'transfer_ownership' => '🔑 Transfer kepemilikan ke <b>'.e($p['target_name'] ?: $p['to_name']).'</b>',
                        default              => $fmt($p['amount']).' TLKM → <b>'.e($p['to_name'] ?: (substr($p['to_wallet'],0,8).'…'.substr($p['to_wallet'],-4))).'</b>',
                    };
                @endphp
                <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-800">{!! $label !!}</p>
                        <p class="text-[11px] text-slate-400">{{ $p['approvals'] }}/{{ $p['required'] }} setuju (bulat) @if($p['note'])· {{ $p['note'] }}@endif</p>
                    </div>
                    <div class="shrink-0">
                        @if($p['status'] === 'executed')
                            @if($p['tx'])
                                <a href="https://sepolia.etherscan.io/tx/{{ $p['tx'] }}" target="_blank" class="text-xs text-green-600 hover:underline">Terkirim ↗</a>
                            @else
                                <span class="text-xs text-green-600">Selesai ✓</span>
                            @endif
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

{{-- Mutasi setoran (siapa menyetor ke kas) --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">Mutasi Setoran</h2></div>
    @if($deposits->isEmpty())
        <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada setoran tercatat.</p>
    @else
        @foreach($deposits as $d)
            <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm text-slate-800"><b>{{ $d['name'] }}</b> menyetor</p>
                    <p class="text-[11px] text-slate-400">{{ $d['at']->diffForHumans() }}@if($d['wallet']) · {{ substr($d['wallet'],0,6) }}…{{ substr($d['wallet'],-4) }}@endif</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-semibold text-green-600">+{{ $fmt($d['amount']) }} TLKM</p>
                    @if($d['tx'])<a href="https://sepolia.etherscan.io/tx/{{ $d['tx'] }}" target="_blank" class="text-[11px] text-blue-600 hover:underline">Lihat tx ↗</a>@endif
                </div>
            </div>
        @endforeach
    @endif
</div>

@endsection

@section('scripts')
<script>
const WID = @json($wallet->id);
const CADDR = @json($wallet->address);
const INVITE_CANDIDATES = @json($inviteCandidates->values());
const OWNER_CANDIDATES = @json(collect($members)->filter(fn($m) => $m['is_signer'] && !$m['is_owner'])->map(fn($m) => ['id' => $m['user_id'], 'name' => $m['name']])->values());
const escapeHtml = (s) => (s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

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
        txProgress.done(0); txProgress.active(1); txProgress.done(1);
        // Catat mutasi: siapa yang menyetor (dana sudah terkirim; kegagalan catat tak membatalkan).
        try { await post('/community/deposit-record', { id: WID, amount: amt, tx_hash: hash }); } catch (_) {}
        ok(hash);
    } catch (e) { fail(e); }
}

// ===== Owner: undang / kick anggota, transfer kepemilikan (semua jadi usulan bulat) =====
function doInvite() {
    if (!INVITE_CANDIDATES.length) {
        uiAlert({ title: 'Tidak ada kandidat', message: 'Semua temanmu sudah jadi anggota, atau kamu belum punya teman untuk diundang.', type: 'info' });
        return;
    }
    const opts = INVITE_CANDIDATES.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
    openModal(`<div class="p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-1">Undang Anggota</h3>
        <p class="text-sm text-slate-500 mb-3">Usulan undang butuh persetujuan <b>semua</b> penanda tangan.</p>
        <select id="invSel" class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none text-sm mb-3">${opts}</select>
        <label class="flex items-center gap-2 text-sm text-slate-700 mb-4"><input id="invSigner" type="checkbox" class="rounded border-slate-300"> Jadikan penanda tangan (punya hak suara)</label>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Batal</button>
            <button id="invGo" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Buat Usulan</button>
        </div></div>`);
    document.getElementById('invGo').onclick = async () => {
        const target_id = +document.getElementById('invSel').value;
        const as_signer = document.getElementById('invSigner').checked;
        closeModal();
        const pin = await askPin('Undang Anggota'); if (!pin) return;
        txProgress.open('Membuat usulan undang', ['Verifikasi PIN']);
        try { txProgress.active(0); await post('/community/member-propose', { id: WID, action: 'add', target_id, as_signer, pin }); txProgress.done(0); ok(null); } catch (e) { fail(e); }
    };
}

async function doKick(userId, name) {
    const okc = await uiConfirm({ title: 'Keluarkan Anggota', message: `Buat usulan mengeluarkan <b>${escapeHtml(name)}</b>? Butuh persetujuan <b>semua</b> penanda tangan.`, confirmText: 'Ya, usulkan', cancelText: 'Batal', danger: true });
    if (!okc) return;
    const pin = await askPin('Keluarkan Anggota'); if (!pin) return;
    txProgress.open('Membuat usulan keluarkan', ['Verifikasi PIN']);
    try { txProgress.active(0); await post('/community/member-propose', { id: WID, action: 'remove', target_id: userId, pin }); txProgress.done(0); ok(null); } catch (e) { fail(e); }
}

function doTransferOwner() {
    if (!OWNER_CANDIDATES.length) {
        uiAlert({ title: 'Tidak ada kandidat', message: 'Pemilik baru harus salah satu penanda tangan (selain kamu). Tambahkan/tetapkan penanda tangan lain dulu.', type: 'info' });
        return;
    }
    const opts = OWNER_CANDIDATES.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
    openModal(`<div class="p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-1">Transfer Kepemilikan</h3>
        <p class="text-sm text-slate-500 mb-3">Pemilik baru harus penanda tangan. Butuh persetujuan <b>semua</b> penanda tangan (termasuk kamu).</p>
        <select id="ownSel" class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 outline-none text-sm mb-4">${opts}</select>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Batal</button>
            <button id="ownGo" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Buat Usulan</button>
        </div></div>`);
    document.getElementById('ownGo').onclick = async () => {
        const target_id = +document.getElementById('ownSel').value;
        closeModal();
        const pin = await askPin('Transfer Kepemilikan'); if (!pin) return;
        txProgress.open('Membuat usulan transfer', ['Verifikasi PIN']);
        try { txProgress.active(0); await post('/community/owner-propose', { id: WID, target_id, pin }); txProgress.done(0); ok(null); } catch (e) { fail(e); }
    };
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

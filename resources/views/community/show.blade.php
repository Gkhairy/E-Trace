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
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl font-bold text-slate-900">{{ $wallet->name }}</h1>
                <span class="text-[11px] px-2 py-0.5 rounded-full {{ $wallet->isMultisig() ? 'bg-violet-50 text-violet-700 border border-violet-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">{{ $wallet->modeLabel() }}</span>
            </div>
            <a href="{{ config('chain.explorer_url') }}/address/{{ $wallet->address }}" target="_blank" class="text-xs text-blue-600 hover:underline font-mono">{{ $wallet->address }} ↗</a>
        </div>
        <div class="text-right">
            <p class="text-xs text-slate-500">Saldo dompet</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $balance !== null ? $fmt($balance) : '—' }} <span class="text-sm text-blue-600">TLKM</span></p>
            <p class="text-[11px] {{ $lowGas ? 'text-amber-600' : 'text-slate-400' }} mt-0.5">Gas: {{ $gasEth !== null ? rtrim(rtrim(number_format($gasEth, 5), '0'), '.').' tBNB' : '—' }}</p>
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
            <b>Dompet belum punya gas (tBNB testnet).</b> Ini <b>bukan biaya uang nyata</b> — tBNB testnet gratis dari faucet, hanya untuk testnet.
            Isi sedikit ke <a href="{{ config('chain.explorer_url') }}/address/{{ $wallet->address }}" target="_blank" class="font-mono underline break-all">{{ $wallet->address }}</a>
            (dari <a href="https://testnet.bnbchain.org/faucet-smart" target="_blank" class="underline">faucet BNB Testnet</a>) lalu muat ulang, atau admin jalankan <code class="bg-white/60 px-1 rounded">php artisan community:fund-gas</code>.
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
                        <td class="px-5 py-2">
                            {{ $m['name'] }} @if($m['is_me'])<span class="text-[10px] text-blue-600">(kamu)</span>@endif
                            @if($m['nickname'])<span title="Nickname pribadimu · asli: {{ $m['real_name'] }}" class="text-slate-400">🔒</span>@endif
                            @unless($m['is_me'])<button onclick="doMemberNickname({{ $m['user_id'] }}, @js($m['nickname']), @js($m['real_name']))" title="Beri nickname (hanya kamu yang lihat)" class="ml-1 text-slate-400 hover:text-blue-600 text-xs">✎</button>@endunless
                        </td>
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

        {{-- Daftar anggota + penanda tangan + pemilik. Nickname (✎) = julukan pribadi
             yang HANYA kamu lihat untuk tiap anggota. --}}
        <div class="flex flex-wrap gap-2 mb-2">
            @foreach($members as $m)
                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full border {{ $m['is_signer'] ? 'bg-violet-50 text-violet-700 border-violet-200' : 'bg-slate-50 text-slate-500 border-slate-200' }}">
                    @if($m['is_owner'])<span title="Pemilik">👑</span>@endif
                    {{ $m['name'] }}@if($m['is_me']) (kamu)@endif
                    @if($m['nickname'])<span title="Nickname pribadimu · asli: {{ $m['real_name'] }}" class="text-slate-400">🔒</span>@endif
                    @if($m['is_signer'])<span title="Penanda tangan wajib">🖊️</span>@endif
                    @unless($m['is_me'])
                        <button onclick="doMemberNickname({{ $m['user_id'] }}, @js($m['nickname']), @js($m['real_name']))" title="Beri nickname (hanya kamu yang lihat)" class="ml-0.5 text-slate-400 hover:text-blue-600">✎</button>
                    @endunless
                    @if($iAmOwner && !$m['is_owner'])
                        <button onclick="doKick({{ $m['user_id'] }}, @js($m['name']))" title="Usulkan keluarkan" class="text-slate-400 hover:text-red-600">✕</button>
                    @endif
                </span>
            @endforeach
        </div>
        <p class="text-[11px] text-slate-400 mb-4">✎ = beri nickname pribadi (mis. “Budi”); 🔒 = kamu sudah memberi nickname. Hanya kamu yang melihatnya.</p>

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
                        'purchase'           => '🛒 Belanja <b>'.$fmt($p['amount']).' TLKM</b>'.(($p['items'] ?? 0) ? ' ('.$p['items'].' barang)' : '').' → ditahan <b>escrow</b>',
                        default              => $fmt($p['amount']).' TLKM → <b>'.e($p['to_name'] ?: (substr($p['to_wallet'],0,8).'…'.substr($p['to_wallet'],-4))).'</b>',
                    };
                @endphp
                <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        @if($p['receipt'])
                            {{-- Klik untuk melihat struk: penanda tangan perlu tahu barangnya sebelum menyetujui. --}}
                            <button type="button" onclick="showReceipt({{ $p['id'] }})" class="text-left group">
                                <p class="text-sm text-slate-800 group-hover:text-blue-700">{!! $label !!}
                                    <span class="text-[11px] text-blue-600 group-hover:underline whitespace-nowrap">· lihat struk</span>
                                </p>
                            </button>
                        @else
                        <p class="text-sm text-slate-800">{!! $label !!}</p>
                        @endif
                        <p class="text-[11px] text-slate-400">{{ $p['approvals'] }}/{{ $p['required'] }} setuju (bulat) @if($p['note'])· {{ $p['note'] }}@endif</p>
                    </div>
                    <div class="shrink-0">
                        @if($p['status'] === 'executed')
                            @if($p['tx'])
                                <a href="{{ config('chain.explorer_url') }}/tx/{{ $p['tx'] }}" target="_blank" class="text-xs text-green-600 hover:underline">Terkirim ↗</a>
                            @else
                                <span class="text-xs text-green-600">Selesai ✓</span>
                            @endif
                        @elseif($p['status'] === 'rejected')
                            <span class="text-xs text-red-500 font-medium">Ditolak ✕</span>
                        @elseif($p['status'] === 'executing')
                            <span class="text-xs text-amber-600 font-medium">Sedang diproses…</span>
                        @elseif($p['status'] === 'failed')
                            <span class="text-xs text-red-500 font-medium" title="Cek saldo dompet di Explorer sebelum mengusulkan ulang.">Gagal dieksekusi</span>
                        @elseif($p['approved_by_me'])
                            <span class="text-xs text-slate-400">Kamu sudah setuju</span>
                        @elseif($iAmSigner)
                            <div class="flex items-center gap-3">
                                <button onclick="doApprove({{ $p['id'] }})" class="text-xs text-blue-600 font-semibold hover:underline">Setujui</button>
                                <button onclick="doReject({{ $p['id'] }})" class="text-xs text-red-500 font-semibold hover:underline">Tolak</button>
                            </div>
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
                    @if($d['tx'])<a href="{{ config('chain.explorer_url') }}/tx/{{ $d['tx'] }}" target="_blank" class="text-[11px] text-blue-600 hover:underline">Lihat tx ↗</a>@endif
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
function ok(hash) { txProgress.close(); uiAlert({ title: 'Berhasil', message: hash ? `<a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>` : 'Tersimpan.', type: 'success' }).then(() => location.reload()); }
function fail(e) { txProgress.close(); uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' }); }

// Setor: transfer TLKM milik SENDIRI ke alamat komunitas (PIN embedded / MetaMask).
async function doDeposit() {
    const amt = await uiPrompt({ title: 'Setor ke Komunitas', label: 'Jumlah TLKM yang disetor:', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Setor' });
    if (amt === null || +amt <= 0) return;
    let pin = null;
    if (IS_EMBEDDED) { pin = await askPin('Setor ke Komunitas'); if (!pin) return; }
    txProgress.open('Setor TLKM', ['Menandatangani', 'Mencatat']);
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
        <div class="relative mb-3">
            <select id="invSel" class="appearance-none w-full pl-4 pr-10 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-800 cursor-pointer transition">${opts}</select>
            <svg class="w-4 h-4 absolute right-3 top-3 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700 mb-4 cursor-pointer"><input id="invSigner" type="checkbox" class="rounded border-slate-300"> Jadikan penanda tangan (punya hak suara)</label>
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
        <div class="relative mb-4">
            <select id="ownSel" class="appearance-none w-full pl-4 pr-10 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-800 cursor-pointer transition">${opts}</select>
            <svg class="w-4 h-4 absolute right-3 top-3 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
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
    txProgress.open('Menarik dana', ['Verifikasi PIN', 'Mencatat']);
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

async function doReject(pid) {
    const okc = await uiConfirm({ title: 'Tolak Usulan', message: 'Tolak usulan ini? Karena persetujuan <b>bulat</b>, penolakanmu langsung membatalkannya.', confirmText: 'Ya, tolak', cancelText: 'Batal', danger: true });
    if (!okc) return;
    const pin = await askPin('Tolak Usulan'); if (!pin) return;
    txProgress.open('Menolak usulan', ['Verifikasi PIN']);
    try { txProgress.active(0); await post('/community/reject', { proposal_id: pid, pin }); txProgress.done(0); ok(null); } catch (e) { fail(e); }
}

// Nickname pribadi untuk seorang ANGGOTA (hanya kamu yang lihat).
async function doMemberNickname(userId, currentNick, realName) {
    const nn = await uiPrompt({ title: 'Nickname untuk ' + realName, label: '🔒 Hanya kamu yang melihat julukan ini. Kosongkan untuk pakai nama asli.', placeholder: 'mis. Budi', value: currentNick || '', confirmText: 'Simpan' });
    if (nn === null) return;
    try { await post('/community/member-nickname', { id: WID, target_id: userId, nickname: nn }); showToast('Nickname disimpan.', 'success'); setTimeout(() => location.reload(), 500); } catch (e) { fail(e); }
}
// ===== Struk belanja komunitas =====
const RECEIPTS = @json($proposals->pluck('receipt', 'id')->filter());
const _rp = (n) => Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

function showReceipt(id) {
    const r = RECEIPTS[id];
    if (!r) return;
    const rows = r.items.map(it => `
        <div class="flex items-center gap-3 py-2.5 border-b border-dashed border-slate-200">
            <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
                ${it.img ? `<img src="${it.img}" class="w-full h-full object-cover" alt="">`
                         : `<div class="w-full h-full flex items-center justify-center text-slate-300 text-[10px]">foto</div>`}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-slate-800 leading-snug line-clamp-2">${it.name}</p>
                <p class="text-[11px] text-slate-400">${it.qty}x</p>
            </div>
            <p class="text-sm font-semibold text-slate-900 whitespace-nowrap">${_rp(it.amount)}</p>
        </div>`).join('');

    const ship = r.shipping > 0
        ? `<div class="flex justify-between text-xs text-slate-500 mt-1"><span>Ongkir (di luar escrow)</span><span>${_rp(r.shipping)} TLKM</span></div>` : '';

    const el = document.createElement('div');
    el.className = 'fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm';
    el.onclick = (e) => { if (e.target === el) el.remove(); };
    el.innerHTML = `
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden max-h-[85vh] flex flex-col">
            <div class="px-5 pt-5 pb-3 text-center border-b border-dashed border-slate-200">
                <p class="text-lg font-extrabold tracking-tight text-slate-900">E-Trace</p>
                <p class="text-[11px] uppercase tracking-[0.2em] text-slate-400 mt-0.5">Struk Belanja</p>
                <p class="text-xs text-slate-500 mt-2">${r.at || '-'}</p>
                <p class="text-[11px] text-slate-400 font-mono mt-0.5">${r.order_id}</p>
                <p class="text-[11px] text-slate-400 mt-1">Diajukan oleh <b class="text-slate-600">${r.by}</b></p>
            </div>
            <div class="px-5 py-2 overflow-y-auto">${rows}</div>
            <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                <div class="flex justify-between text-xs text-slate-500"><span>Subtotal (${r.items.length} barang)</span><span>${_rp(r.subtotal)} TLKM</span></div>
                ${ship}
                <div class="flex justify-between items-baseline mt-2 pt-2 border-t border-slate-200">
                    <span class="text-sm font-bold text-slate-900">TOTAL</span>
                    <span class="text-lg font-extrabold text-slate-900">${_rp(r.subtotal)} <span class="text-xs font-semibold text-slate-500">TLKM</span></span>
                </div>
                <button onclick="this.closest('.fixed').remove()" class="mt-4 w-full bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-xl text-sm font-semibold transition">Tutup</button>
            </div>
        </div>`;
    document.body.appendChild(el);
}
</script>
@endsection

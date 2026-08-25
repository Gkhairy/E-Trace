@extends('layouts.app')

@section('content')
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
            <p class="text-2xl font-extrabold text-slate-900"><span id="balance">…</span> <span class="text-sm text-blue-600">TLKM</span></p>
        </div>
    </div>
    @if($wallet->description)<p class="text-sm text-slate-500 mt-3">{{ $wallet->description }}</p>@endif

    <div class="mt-4 flex flex-wrap gap-2">
        <button onclick="doDeposit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">Setor TLKM</button>
    </div>
</div>

<div id="modeArea" class="space-y-6">
    <div class="text-sm text-slate-400">Memuat data on-chain…</div>
</div>

@endsection

@section('scripts')
<script>
const CADDR = @json($wallet->address);
const MODE  = @json($wallet->mode); // 'A' | 'B'
const RPC   = @json(config('chain.rpc_url'));
const fmt = (wei) => { try { return Number(ethers.formatUnits(wei, 18)).toLocaleString('id-ID', {maximumFractionDigits:2}); } catch(e){ return '0'; } };

const ALLOW_ABI = [
  "function balance() view returns (uint256)","function owner() view returns (address)",
  "function memberCount() view returns (uint256)","function memberList(uint256) view returns (address)",
  "function members(address) view returns (bool active,uint256 monthlyLimit,uint256 spent,uint256 periodStart)",
  "function remaining(address) view returns (uint256)",
  "function deposit(uint256)","function withdraw(uint256)","function setMember(address,uint256)","function removeMember(address)"
];
const MULTI_ABI = [
  "function balance() view returns (uint256)","function threshold() view returns (uint256)",
  "function memberCount() view returns (uint256)","function members(uint256) view returns (address)","function isMember(address) view returns (bool)",
  "function proposalCount() view returns (uint256)",
  "function getProposal(uint256) view returns (uint8 kind,address to,uint256 amount,uint256 newThreshold,uint256 approvals,bool executed,address proposer)",
  "function approvedBy(uint256,address) view returns (bool)",
  "function deposit(uint256)","function proposeTransfer(address,uint256)","function approve(uint256)","function execute(uint256)"
];
const ABI = MODE === 'B' ? MULTI_ABI : ALLOW_ABI;

function reader() { return new ethers.Contract(CADDR, ABI, new ethers.JsonRpcProvider(RPC)); }

// Eksekusi aksi: embedded → PIN (backend), lainnya → MetaMask (ethers signer).
async function act(method, args, tokenIdx = [], depositApprove = false) {
    if (IS_EMBEDDED) {
        const pin = await askPin('Konfirmasi Aksi'); if (!pin) return;
        txProgress.open('Memproses', ['Tanda tangan dengan PIN', 'Menyiarkan']);
        try {
            txProgress.active(0, 'Menandatangani…');
            const hash = await pinTx('/pin/community', { pin, address: CADDR, method, args });
            txProgress.done(0); txProgress.active(1); txProgress.done(1);
            setTimeout(() => { txProgress.close(); uiAlert({title:'Berhasil', message:`<a href="https://sepolia.etherscan.io/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type:'success'}).then(()=>location.reload()); }, 300);
        } catch (e) { txProgress.close(); uiAlert({title:'Gagal', message: niceError(e), type:'error'}); }
        return;
    }
    // MetaMask
    txProgress.open('Memproses', ['Memeriksa jaringan', 'Konfirmasi di MetaMask']);
    try {
        txProgress.active(0); await checkNetwork(); txProgress.done(0);
        const { signer } = await connectWallet();
        if (depositApprove) {
            const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
            const ap = await token.approve(CADDR, ethers.parseUnits(String(args[0]), 18)); await ap.wait();
        }
        const callArgs = args.map((a, i) => tokenIdx.includes(i) ? ethers.parseUnits(String(a), 18) : a);
        const c = new ethers.Contract(CADDR, ABI, signer);
        txProgress.active(1, 'Konfirmasi di MetaMask…');
        const tx = await c[method](...callArgs); const rc = await tx.wait();
        txProgress.done(1);
        setTimeout(() => { txProgress.close(); uiAlert({title:'Berhasil', message:`<a href="https://sepolia.etherscan.io/tx/${rc.hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type:'success'}).then(()=>location.reload()); }, 300);
    } catch (e) { txProgress.close(); uiAlert({title:'Gagal', message: niceError(e), type:'error'}); }
}

async function doDeposit() {
    const amt = prompt('Jumlah TLKM yang disetor:'); if (!amt || isNaN(amt) || +amt <= 0) return;
    act('deposit', [amt], [0], true); // butuh approve dulu
}

async function loadState() {
    const area = document.getElementById('modeArea');
    try {
        const c = reader();
        document.getElementById('balance').textContent = fmt(await c.balance());
        if (MODE === 'A') { await loadAllowance(c, area); } else { await loadMultisig(c, area); }
    } catch (e) {
        area.innerHTML = '<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm">Gagal memuat data on-chain. Pastikan alamat kontrak benar & jaringan Sepolia.</div>';
    }
}

async function loadAllowance(c, area) {
    const n = Number(await c.memberCount());
    let rows = '';
    for (let i = 0; i < n; i++) {
        const addr = await c.memberList(i);
        const m = await c.members(addr);
        if (!m.active) continue;
        const rem = await c.remaining(addr);
        rows += `<tr class="border-t border-slate-100">
            <td class="px-4 py-2 font-mono text-xs">${addr.slice(0,8)}…${addr.slice(-6)}</td>
            <td class="px-4 py-2">${fmt(m.monthlyLimit)}</td>
            <td class="px-4 py-2 text-green-600">${fmt(rem)}</td></tr>`;
    }
    area.innerHTML = `
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-3"><h2 class="font-bold text-slate-900">Anggota &amp; Jatah</h2>
              <div class="flex gap-2">
                <button onclick="doWithdraw()" class="bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Tarik (anggota)</button>
                <button onclick="doSetMember()" class="bg-white border border-slate-300 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-semibold">Set Anggota (owner)</button>
              </div>
            </div>
            <table class="w-full text-sm"><thead class="text-xs text-slate-500 text-left"><tr><th class="px-4 py-2">Anggota</th><th class="px-4 py-2">Limit/bulan</th><th class="px-4 py-2">Sisa bulan ini</th></tr></thead>
            <tbody>${rows || '<tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">Belum ada anggota.</td></tr>'}</tbody></table>
        </div>`;
}
function doWithdraw() { const a = prompt('Jumlah TLKM yang ditarik:'); if (a && +a > 0) act('withdraw', [a], [0]); }
function doSetMember() { const m = prompt('Alamat anggota (0x…):'); if (!m) return; const l = prompt('Limit per bulan (TLKM):'); if (l && +l >= 0) act('setMember', [m, l], [1]); }

async function loadMultisig(c, area) {
    const th = Number(await c.threshold());
    const nm = Number(await c.memberCount());
    const np = Number(await c.proposalCount());
    let mem = [];
    for (let i = 0; i < nm; i++) mem.push(await c.members(i));
    let props = '';
    for (let i = np - 1; i >= 0 && i >= np - 20; i--) {
        const p = await c.getProposal(i);
        const kind = Number(p.kind) === 0 ? `Kirim ${fmt(p.amount)} TLKM → ${p.to.slice(0,8)}…${p.to.slice(-4)}` : 'Ubah aturan';
        const status = p.executed ? '<span class="text-green-600">Selesai</span>' : `${p.approvals}/${th} setuju`;
        const btns = p.executed ? '' :
            `<button onclick="act('approve',[${i}])" class="text-xs text-blue-600 font-medium">Setujui</button>
             ${Number(p.approvals) >= th ? `<button onclick="act('execute',[${i}])" class="text-xs text-green-600 font-medium ml-2">Eksekusi</button>` : ''}`;
        props += `<div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
            <div><p class="text-sm text-slate-800">#${i} · ${kind}</p><p class="text-[11px] text-slate-400">${status}</p></div>
            <div class="shrink-0">${btns}</div></div>`;
    }
    area.innerHTML = `
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <h2 class="font-bold text-slate-900 mb-1">Multisig ${th} dari ${nm}</h2>
            <p class="text-xs text-slate-500 mb-2">Anggota: ${mem.map(a=>a.slice(0,6)+'…'+a.slice(-4)).join(', ')}</p>
            <button onclick="doPropose()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Usulkan Kirim Dana</button>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100"><h2 class="font-bold text-slate-900">Usulan</h2></div>
            ${props || '<p class="px-4 py-6 text-center text-sm text-slate-400">Belum ada usulan.</p>'}
        </div>`;
}
function doPropose() { const to = prompt('Kirim ke alamat (0x…):'); if (!to) return; const a = prompt('Jumlah TLKM:'); if (a && +a > 0) act('proposeTransfer', [to, a], [1]); }

loadState();
</script>
@endsection

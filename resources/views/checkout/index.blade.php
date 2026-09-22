@extends('layouts.app')

@section('content')

@php
    // Baris item untuk dikirim ke JS (amount sebagai string desimal, hindari float error).
    $lines = $items->values()->map(function ($it, $i) {
        $amount = (float) $it->product->price_usdc * $it->quantity;
        return [
            'db_product_id' => $it->product->id,
            'product_uuid'  => $it->product->product_id,
            'seller'        => $it->product->seller_wallet,
            'name'          => $it->product->name,
            'qty'           => $it->quantity,
            'amount'        => number_format($amount, 6, '.', ''),
            'index'         => $i,
        ];
    });
    $totalStr = number_format($total, 6, '.', '');
@endphp

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/cart" class="hover:text-blue-600 transition">Keranjang</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Checkout</span>
</nav>

<h1 class="text-2xl font-bold text-slate-900 mb-6">Checkout</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- KIRI: alamat + ringkasan per penjual --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- ALAMAT: pilih tersimpan atau isi baru --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-slate-900">Alamat Pengiriman</h3>
                <a href="/addresses" class="text-xs text-blue-600 hover:underline">Kelola alamat</a>
            </div>
            <p class="text-xs text-slate-400 mb-4">Data pribadi ini disimpan di database, <b>tidak</b> masuk blockchain.</p>

            @if($addresses->isNotEmpty())
                <div class="space-y-2 mb-4">
                    @foreach($addresses as $a)
                        <label data-addrcard class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="addr" value="{{ $a->id }}" data-city="{{ $a->city }}" class="mt-1 accent-blue-600" onchange="selectAddr(this)" @checked($loop->first)>
                            <div class="min-w-0 text-sm">
                                <p class="font-medium text-slate-800">{{ $a->recipient_name }} <span class="text-slate-400 font-normal">· {{ $a->phone }}</span>
                                    @if($a->label)<span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">{{ $a->label }}</span>@endif
                                    @if($a->is_default)<span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-600">Utama</span>@endif
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $a->address }}, {{ $a->city }} {{ $a->postal_code }}</p>
                            </div>
                        </label>
                    @endforeach
                    <label data-addrcard class="flex items-center gap-3 p-3 rounded-xl border border-dashed border-slate-300 cursor-pointer hover:border-blue-400 transition">
                        <input type="radio" name="addr" value="new" class="accent-blue-600" onchange="selectAddr(this)">
                        <span class="text-sm font-medium text-blue-600">+ Alamat baru</span>
                    </label>
                </div>
            @endif

            <div id="newAddrForm" class="{{ $addresses->isNotEmpty() ? 'hidden' : '' }} grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <button type="button" onclick="pilihAlamatPeta()"
                        class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-sm font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10zM12 11a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        Pilih dari Peta
                    </button>
                    <p class="text-[11px] text-slate-400 mt-1.5">Geser pin ke lokasimu — alamat, kota & kode pos terisi otomatis (akurat untuk ongkir).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Penerima</label>
                    <input id="recipient_name" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">No HP</label>
                    <input id="phone" placeholder="0812…" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Lengkap</label>
                    <textarea id="address" rows="2" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kota</label>
                    <input id="city" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kode Pos</label>
                    <input id="postal_code" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Label (opsional)</label>
                    <input id="addr_label" placeholder="Rumah / Kantor" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Catatan (opsional)</label>
                    <input id="notes" placeholder="Patokan, warna, dll" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <p class="sm:col-span-2 text-[11px] text-slate-400">Alamat baru otomatis tersimpan ke buku alamat untuk pembelian berikutnya.</p>
            </div>
        </div>

        {{-- RINGKASAN PER PENJUAL --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-slate-900 mb-1">Ringkasan per Penjual</h3>
            <p class="text-xs text-slate-400 mb-4">Dana tiap penjual ditahan di <b>escrow terpisah</b>. Konfirmasi 1 penjual tidak melepas dana penjual lain.</p>

            <div class="space-y-5">
                @foreach($groups as $seller => $group)
                    @php $subtotal = $group->sum(fn($it) => (float)$it->product->price_usdc * $it->quantity); @endphp
                    <div class="border border-slate-100 rounded-xl overflow-hidden">
                        <div class="bg-slate-50 px-4 py-2.5 flex items-center justify-between">
                            <span class="text-xs font-mono text-slate-500">{{ $shipEstimates[$seller]['store'] ?? ('Penjual '.substr($seller, 0, 8).'…'.substr($seller, -6)) }}</span>
                            <span class="text-xs font-semibold text-slate-700">{{ rtrim(rtrim(number_format($subtotal, 2), '0'), '.') }} TLKM</span>
                        </div>
                        @if(isset($shipEstimates[$seller]))
                            <div class="px-4 py-1.5 bg-slate-50/60 border-b border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span data-ongkir-method="{{ $seller }}">Ongkir · {{ $shipEstimates[$seller]['method'] }}{{ $shipEstimates[$seller]['km'] !== null ? ' (~'.$shipEstimates[$seller]['km'].' km)' : '' }}</span>
                                <span data-ongkir-seller="{{ $seller }}">{{ rtrim(rtrim(number_format($shipEstimates[$seller]['fee_tlkm'], 2), '0'), '.') }} TLKM</span>
                            </div>
                        @endif
                        <div class="divide-y divide-slate-100">
                            @foreach($group as $it)
                                <div class="px-4 py-3 flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-white border border-slate-100 flex items-center justify-center p-1 shrink-0">
                                        <img src="{{ $it->product?->thumbnail() ?? 'https://placehold.co/80x80/f1f5f9/94a3b8?text=—' }}" onerror="this.src='https://placehold.co/80x80/f1f5f9/94a3b8?text=—'" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 line-clamp-1">{{ $it->product->name }}</p>
                                        <p class="text-xs text-slate-400">{{ rtrim(rtrim(number_format($it->product->price_usdc, 2), '0'), '.') }} TLKM × {{ $it->quantity }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700">{{ rtrim(rtrim(number_format((float)$it->product->price_usdc * $it->quantity, 2), '0'), '.') }} TLKM</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- KANAN: total + bayar --}}
    <div class="lg:col-span-1">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 lg:sticky lg:top-24">
            <h3 class="font-semibold text-slate-900 mb-4">Pembayaran</h3>
            <div class="flex justify-between text-sm text-slate-600 mb-2"><span>Jumlah item</span><span>{{ $items->sum('quantity') }}</span></div>
            <div class="flex justify-between text-sm text-slate-600 mb-2"><span>Penjual</span><span>{{ $groups->count() }}</span></div>
            @php
                $f = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
                $shipWallet  = config('chain.shipping.fee_wallet') ?: config('chain.insurance.pool_wallet');
                $shipCharged = (bool) $shipWallet;                      // ongkir ditagih on-chain?
                $grand       = $total + ($shipCharged ? $shipTotalTlkm : 0);
            @endphp
            {{-- Rincian bayar (produk escrow + ongkir) --}}
            <div class="flex justify-between text-sm text-slate-500 mb-2 border-t border-slate-100 pt-3 mt-3">
                <span>Produk (escrow)</span>
                <span>{{ $f($total) }} TLKM</span>
            </div>
            <div class="flex justify-between text-sm text-slate-600 mb-3">
                <span>{{ $shipCharged ? 'Ongkir' : 'Estimasi ongkir' }}<sup class="text-slate-400">*</sup></span>
                <span id="ongkirTotal">{{ $f($shipTotalTlkm) }} TLKM</span>
            </div>
            <div class="flex justify-between items-end border-t border-slate-100 pt-3">
                <span class="text-sm font-semibold text-slate-700">{{ $shipCharged ? 'Total bayar' : 'Total produk (on-chain)' }}</span>
                <span class="text-2xl font-extrabold text-slate-900"><span id="grandTotal">{{ $f($grand) }}</span> <span class="text-sm text-blue-600 font-semibold">TLKM</span></span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5 leading-snug">*Ongkir dihitung via <b>RajaOngkir</b> (tarif kurir termurah) atau estimasi jarak bila kota tak dikenali, dikonversi ke TLKM (Rp1.000 = 1 TLKM). {{ $shipCharged ? 'Ongkir dibayar sebagai transaksi terpisah ke wallet platform (di luar escrow produk).' : 'Diselesaikan terpisah dari escrow produk.' }} {{ $buyerCity ? 'Kota tujuan: '.$buyerCity.'.' : 'Pilih/isi alamat untuk estimasi akurat.' }}</p>

            @if($insurance['enabled'])
            {{-- Garansi Tepat Waktu (asuransi pengiriman parametrik) — opsional --}}
            <label id="insBox" class="flex items-start gap-2.5 mt-4 p-3 rounded-xl border border-amber-200 bg-amber-50/60 cursor-pointer transition hover:bg-amber-50">
                <input type="checkbox" id="insToggle" onchange="onInsToggle()" class="mt-0.5 accent-amber-500 shrink-0">
                <span class="text-xs text-slate-700 leading-snug">
                    <b>{{ __('insurance.checkout_title') }}</b>
                    <span class="text-amber-700 font-semibold">(+{{ rtrim(rtrim(number_format($insurance['premium_tlkm'], 2), '0'), '.') }} TLKM)</span>
                    — {{ __('insurance.checkout_desc') }}
                    <span class="block text-[11px] text-slate-500 mt-1">
                        {{ __('insurance.eta_label') }} <b id="insEtaDate">{{ $insurance['promised_date']->translatedFormat('d M Y') }}</b>.
                        {{ __('insurance.terms_short', ['grace' => $insurance['grace_days'], 'cap' => rtrim(rtrim(number_format($insurance['payout_cap_tlkm'], 2), '0'), '.')]) }}
                        <span class="text-amber-600">{{ __('insurance.demo_note') }}</span>
                    </span>
                </span>
            </label>
            @endif

            <div class="flex items-center gap-2 text-xs text-green-700 mt-4 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Bayar 1× — escrow terpisah per penjual
            </div>
            {{-- H6: kebijakan refund yang adil --}}
            <div class="flex items-start gap-2 text-[11px] text-slate-500 mt-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                <svg class="w-4 h-4 shrink-0 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Refund bisa diajukan bila barang tak diterima setelah <b>3 hari</b>. Sengketa ditinjau pengawas dengan bukti (resi/foto) — bukan refund otomatis.</span>
            </div>

            <button id="payBtn" onclick="checkoutPay()"
                class="mt-4 w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-3 rounded-xl text-sm font-bold transition shadow-sm">
                Bayar <span id="payBtnAmt">{{ $f($grand) }}</span> TLKM
            </button>

            @if(config('chain.paylater_address'))
                <button id="payLaterBtn" onclick="checkoutPayWithPaylater()"
                    class="mt-2 w-full bg-white hover:bg-teal-50 border border-teal-200 text-teal-700 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white"
                    {{ $paylaterBtn['available'] ? '' : 'disabled' }}>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    {{ __('paylater.pay_with') }}
                </button>
                @if($paylaterBtn['available'])
                    <p class="text-[11px] text-slate-400 text-center mt-1">Pinjam TLKM dari agunanmu bila saldo kurang, lalu bayar seperti biasa. <span class="text-amber-600">Demo testnet.</span></p>
                @else
                    <p class="text-[11px] text-amber-600 text-center mt-1">{{ $paylaterBtn['reason'] ?? 'Paylater tidak tersedia untuk order ini.' }}</p>
                @endif
            @endif

            {{-- Dana Komunitas (multisig Mode B) — hanya tampil bila user anggota minimal satu. --}}
            @if($communityWallets->isNotEmpty())
                @php $fmtBal = fn ($b) => $b === null ? '—' : rtrim(rtrim(number_format($b, 2), '0'), '.'); @endphp
                <div class="mt-3 pt-3 border-t border-slate-200">
                    <p class="text-xs font-semibold text-slate-600 mb-1.5">Bayar dari Dana Komunitas</p>
                    @if($communityWallets->count() === 1)
                        @php $cw = $communityWallets[0]; @endphp
                        <input type="hidden" id="commWallet" value="{{ $cw['id'] }}">
                        <p class="text-[11px] text-slate-500 mb-2">
                            <b class="text-slate-700">{{ $cw['name'] }}</b> · saldo {{ $fmtBal($cw['balance']) }} TLKM · {{ $cw['signers'] }} penanda tangan
                            @unless($cw['enough'])<span class="text-amber-600 font-semibold">· saldo kurang</span>@endunless
                        </p>
                    @else
                        {{-- Anggota di beberapa komunitas → pilih mau minta persetujuan ke yang mana. --}}
                        <select id="commWallet" class="w-full px-3 py-2 mb-2 rounded-xl bg-white border border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-100 outline-none text-sm">
                            @foreach($communityWallets as $cw)
                                <option value="{{ $cw['id'] }}">{{ $cw['name'] }} — {{ $fmtBal($cw['balance']) }} TLKM ({{ $cw['signers'] }} signer){{ $cw['enough'] ? '' : ' · saldo kurang' }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button id="commBtn" onclick="checkoutPayWithCommunity()"
                        class="w-full bg-white hover:bg-violet-50 border border-violet-200 text-violet-700 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0a5 5 0 00-9.28 0M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Minta Persetujuan Komunitas
                    </button>
                    <p class="text-[11px] text-slate-400 text-center mt-1">Dana diambil dari kas komunitas setelah <b>semua penanda tangan</b> menyetujui. Tetap ditahan escrow seperti biasa.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@include('partials.map-picker')

@endsection

@section('scripts')
<script>
const LINES = @json($lines);
const TOTAL = @json($totalStr);
// Versi tampilan: buang nol/desimal berlebih (mis. "100.000000" -> "100").
const TOTAL_FMT = (parseFloat(TOTAL) || 0).toLocaleString('en-US', { maximumFractionDigits: 6 });

// ===== Garansi Tepat Waktu (asuransi) =====
const INSURANCE_ENABLED = @json($insurance['enabled']);
const INSURANCE_POOL    = @json($insurance['pool_wallet'] ?? null);
const INSURANCE_PREMIUM = @json($insurance['premium_tlkm']);
function onInsToggle() {
    const box = document.getElementById('insBox');
    const on = document.getElementById('insToggle')?.checked;
    if (box) { box.classList.toggle('ring-2', on); box.classList.toggle('ring-amber-300', on); }
    if (typeof refreshGrand === 'function') refreshGrand();
}
// Bayar premi ke pool asuransi (transaksi terpisah dari escrow). Return tx hash.
async function payInsurancePremium(pin) {
    const amt = INSURANCE_PREMIUM.toString();
    if (IS_EMBEDDED) return await pinTx('/pin/transfer', { pin, to: INSURANCE_POOL, amount: amt });
    const { signer } = await connectWallet();
    const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
    const tx = await token.transfer(INSURANCE_POOL, ethers.parseUnits(amt, TOKEN_DECIMALS));
    return (await tx.wait()).hash;
}

// ===== Ongkir (transfer terpisah ke wallet platform) =====
const SHIPPING_WALLET = @json(config('chain.shipping.fee_wallet') ?: config('chain.insurance.pool_wallet'));
const SHIP_CHARGED    = {{ (config('chain.shipping.fee_wallet') ?: config('chain.insurance.pool_wallet')) ? 'true' : 'false' }};
let   SHIP_TLKM       = @json($shipTotalTlkm);
const PROD_TOTAL      = parseFloat(TOTAL) || 0;
function _fmtTlkm(n) { return (Math.round((+n || 0) * 100) / 100).toString(); }
// Perbarui "Total bayar" & label tombol = produk + ongkir (+ premi garansi bila dicentang).
function refreshGrand() {
    const insured = INSURANCE_ENABLED && (document.getElementById('insToggle')?.checked || false);
    const grand = PROD_TOTAL
        + (SHIP_CHARGED ? (SHIP_TLKM || 0) : 0)
        + (insured ? (+INSURANCE_PREMIUM || 0) : 0);
    const g = document.getElementById('grandTotal'); if (g) g.textContent = _fmtTlkm(grand);
    const b = document.getElementById('payBtnAmt');  if (b) b.textContent = _fmtTlkm(grand);
}
async function payShipping(pin) {
    if (!SHIP_CHARGED || !SHIPPING_WALLET || !(SHIP_TLKM > 0)) return null;
    const amt = SHIP_TLKM.toString();
    if (IS_EMBEDDED) return await pinTx('/pin/transfer', { pin, to: SHIPPING_WALLET, amount: amt });
    const { signer } = await connectWallet();
    const token = new ethers.Contract(TLKM_ADDRESS, ERC20_ABI, signer);
    const tx = await token.transfer(SHIPPING_WALLET, ethers.parseUnits(amt, TOKEN_DECIMALS));
    return (await tx.wait()).hash;
}

function val(id) { const el = document.getElementById(id); return el ? el.value : ''; }

// Pilih alamat dari peta -> isi alamat, kota, kode pos otomatis + hitung ulang ongkir.
function pilihAlamatPeta() {
    openMapPicker((loc) => {
        if (loc.address) document.getElementById('address').value = loc.address;
        if (loc.city) document.getElementById('city').value = loc.city;
        if (loc.postcode) document.getElementById('postal_code').value = loc.postcode;
        showToast('Lokasi terisi dari peta.', 'success');
        updateOngkir(loc.city || '');
    });
}

function selectedAddr() {
    const r = document.querySelector('input[name=addr]:checked');
    return r ? r.value : 'new';   // tanpa alamat tersimpan -> selalu form baru
}
function selectAddr(radio) {
    document.querySelectorAll('[data-addrcard]').forEach(l => l.classList.remove('border-blue-500', 'bg-blue-50/40'));
    radio.closest('[data-addrcard]')?.classList.add('border-blue-500', 'bg-blue-50/40');
    const form = document.getElementById('newAddrForm');
    if (form) form.classList.toggle('hidden', radio.value !== 'new');
    // Hitung ulang ongkir untuk kota alamat yang dipilih.
    const city = radio.value === 'new' ? val('city') : (radio.dataset.city || '');
    updateOngkir(city);
}

// ===== Hitung ulang ongkir (TLKM) saat kota berganti =====
let _ongkirTimer = null;
function updateOngkir(city) {
    if (_ongkirTimer) clearTimeout(_ongkirTimer);
    _ongkirTimer = setTimeout(async () => {
        const totalEl = document.getElementById('ongkirTotal');
        if (totalEl) totalEl.textContent = '…';
        try {
            const res = await fetch('/shipping/quote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({ city: city || '' }),
            });
            const d = await res.json();
            const fmt = (n) => (Math.round(n * 100) / 100).toString();
            if (totalEl) totalEl.textContent = fmt(d.total_tlkm) + ' TLKM';
            Object.entries(d.sellers || {}).forEach(([wallet, s]) => {
                const feeEl = document.querySelector(`[data-ongkir-seller="${wallet}"]`);
                if (feeEl) feeEl.textContent = fmt(s.fee_tlkm) + ' TLKM';
                const mEl = document.querySelector(`[data-ongkir-method="${wallet}"]`);
                if (mEl) mEl.textContent = 'Ongkir · ' + s.method + (s.km != null ? ' (~' + s.km + ' km)' : '');
            });
            // Garansi Tepat Waktu: estimasi tiba (SLA) ikut berubah menurut jarak.
            const etaEl = document.getElementById('insEtaDate');
            if (etaEl && d.promised_date) etaEl.textContent = d.promised_date;
            // Ongkir berubah → perbarui "Total bayar" & label tombol.
            SHIP_TLKM = d.total_tlkm;
            refreshGrand();
        } catch (_) { if (totalEl) totalEl.textContent = '—'; }
    }, 400);
}

// Ketik kota manual -> hitung ulang (debounced) + hitung awal sesuai alamat terpilih.
document.addEventListener('DOMContentLoaded', () => {
    const cityInput = document.getElementById('city');
    if (cityInput) cityInput.addEventListener('input', () => updateOngkir(cityInput.value));
    const checked = document.querySelector('input[name=addr]:checked');
    if (checked && checked.value !== 'new' && checked.dataset.city) updateOngkir(checked.dataset.city);
});

// Bayar pakai Paylater: DANAI SELURUH order dari kredit Paylater (bukan cuma
// kekurangan), lalu jalankan alur escrow yang SUDAH ADA. Saldo TLKM pribadi tidak
// berkurang (net): pinjaman masuk ke wallet lalu langsung dibayarkan ke escrow.
async function checkoutPayWithPaylater() {
    if (!LINES.length) return;
    if (!PAYLATER_ADDRESS) { uiAlert({ title: 'Paylater nonaktif', message: 'Fitur Paylater belum dikonfigurasi.', type: 'warn' }); return; }
    try {
        const need = parseFloat(TOTAL) || 0;
        const needWei = ethers.parseUnits(need.toString(), TOKEN_DECIMALS);

        // Cek SISA LIMIT + LIKUIDITAS paylater on-chain (read-only, jalan utk MetaMask & embedded).
        const pl = new ethers.Contract(PAYLATER_ADDRESS, PAYLATER_ABI, readProvider());
        const pos = await pl.positionOf(ACCOUNT_WALLET);   // [collateral, principal, dueAmount, dueDate, limit]
        const availableWei = pos[4] - pos[1];              // limit - principal
        if (needWei > availableWei) {
            const availTlkm = (+ethers.formatUnits(availableWei < 0n ? 0n : availableWei, TOKEN_DECIMALS)).toLocaleString('en-US', { maximumFractionDigits: 2 });
            uiAlert({ title: 'Limit Paylater kurang', message: `Sisa limit kreditmu <b>${availTlkm} TLKM</b>, sedangkan total <b>${need.toLocaleString('en-US')} TLKM</b>. Tambah agunan (tBNB) di Dompet dulu, atau bayar biasa.`, type: 'warn' });
            return;
        }
        // Dua-sisi: availableLiquidity() = TLKM menganggur dari penyuplai.
        const liqWei = await pl.availableLiquidity();
        if (needWei > liqWei) {
            const liqTlkm = (+ethers.formatUnits(liqWei, TOKEN_DECIMALS)).toLocaleString('en-US', { maximumFractionDigits: 2 });
            uiAlert({ title: 'Likuiditas pool kurang', message: `Likuiditas pool Paylater tinggal <b>${liqTlkm} TLKM</b> (butuh ${need.toLocaleString('en-US')} TLKM). Perlu ada penyuplai yang mendanai pool dulu. Untuk sekarang, silakan bayar biasa.`, type: 'warn' });
            return;
        }

        const dueTotal = need * (10000 + PAYLATER_INTEREST_BPS) / 10000;
        const ok = await uiConfirm({
            title: @json(__('paylater.pay_with')),
            message: `Danai <b>${need.toLocaleString('en-US')} TLKM</b> dari Paylater. Wajib bayar <b>${dueTotal.toLocaleString('en-US', { maximumFractionDigits: 6 })} TLKM</b> (bunga ${PAYLATER_INTEREST_BPS / 100}%) sebelum tenggat.<br><span class="text-xs text-slate-400">Saldo TLKM pribadimu tidak berkurang.</span>`,
            confirmText: 'Ya, pinjam & bayar'
        });
        if (!ok) return;

        const prevBal = parseFloat(await fetchTlkmBalance(ACCOUNT_WALLET)) || 0;

        txProgress.open('Danai via Paylater', ['Meminjam TLKM', 'Menunggu dana masuk']);
        txProgress.active(0);
        const h = await borrowPaylater(need.toString());   // pinjam SELURUH nilai order
        if (!h) return txProgress.close();                 // batal PIN
        await recordPaylater('borrow', need.toString(), h);
        txProgress.done(0);

        // Tunggu pinjaman benar-benar masuk (balance naik ~need) sebelum membayar.
        txProgress.active(1, 'Menunggu TLKM pinjaman masuk…');
        let landed = false;
        for (let i = 0; i < 25; i++) {
            const b = parseFloat(await fetchTlkmBalance(ACCOUNT_WALLET)) || 0;
            if (b + 1e-6 >= prevBal + need) { landed = true; break; }
            await new Promise(r => setTimeout(r, 2000));
        }
        txProgress.done(1); txProgress.close();
        if (!landed) {
            uiAlert({ title: 'Sedang diproses', message: 'Pinjaman TLKM masih diproses jaringan. Tunggu sebentar lalu klik “Bayar” biasa.', type: 'warn' });
            return;
        }

        // Lanjut alur escrow yang SUDAH ADA (bayar dari saldo yang kini sudah termasuk pinjaman).
        await checkoutPay();
    } catch (e) {
        txProgress.close();
        uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' });
    }
}

// ===== Bayar dari DANA KOMUNITAS (multisig Mode B) =====
// Tidak langsung membayar: membuat USULAN yang dikunci snapshot-nya di server.
// Pembayaran escrow dijalankan dompet komunitas setelah semua penanda tangan setuju.
async function checkoutPayWithCommunity() {
    const sel = selectedAddr();
    let shipping = null, shippingAddressId = null, addressLabel = null;
    if (sel === 'new') {
        shipping = {
            recipient_name: val('recipient_name'), phone: val('phone'), address: val('address'),
            city: val('city'), postal_code: val('postal_code'), notes: val('notes'),
        };
        for (const k of ['recipient_name', 'phone', 'address', 'city', 'postal_code']) {
            if (!shipping[k].trim()) {
                showToast('Lengkapi alamat pengiriman dulu.', 'warn');
                document.getElementById(k)?.focus();
                return;
            }
        }
        addressLabel = val('addr_label');
    } else {
        shippingAddressId = sel;
    }

    const walletId = document.getElementById('commWallet')?.value;
    if (!walletId) return;

    const ok = await uiConfirm({
        title: 'Minta Persetujuan Komunitas',
        message: `Ajukan pembelian <b class="text-violet-600">${TOTAL_FMT} TLKM</b> memakai dana komunitas?<br><span class="text-xs text-slate-400">Pembayaran baru dijalankan setelah semua penanda tangan menyetujui.</span>`,
        confirmText: 'Ya, ajukan',
    });
    if (!ok) return;

    const pin = await askPin('Ajukan Belanja Komunitas');
    if (!pin) return;

    const btn = document.getElementById('commBtn');
    btn.disabled = true;
    try {
        const res = await fetch('/community/purchase-propose', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({
                id: walletId, pin,
                shipping_address_id: shippingAddressId, shipping, address_label: addressLabel,
                is_insured: (INSURANCE_ENABLED && document.getElementById('insToggle')?.checked) ? 1 : 0,
            }),
        });
        const d = await res.json().catch(() => ({}));
        if (!res.ok || !d.success) throw new Error(d.message || 'Gagal mengajukan usulan.');

        if (d.executed) {
            uiAlert({ title: 'Disetujui & dibayar', message: 'Semua penanda tangan sudah setuju — pesanan dibayar dari dana komunitas dan ditahan escrow.', type: 'success' });
            setTimeout(() => location.href = '/orders', 1300);
        } else {
            uiAlert({ title: 'Usulan terkirim', message: 'Menunggu persetujuan penanda tangan komunitas. Kamu akan diberi tahu saat disetujui.', type: 'success' });
            setTimeout(() => location.href = '/community/' + d.wallet_id, 1300);
        }
    } catch (e) {
        btn.disabled = false;
        uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' });
    }
}

async function checkoutPay() {
    const sel = selectedAddr();
    let shipping = null, shippingAddressId = null, addressLabel = null;

    if (sel === 'new') {
        shipping = {
            recipient_name: val('recipient_name'), phone: val('phone'), address: val('address'),
            city: val('city'), postal_code: val('postal_code'), notes: val('notes'),
        };
        for (const k of ['recipient_name', 'phone', 'address', 'city', 'postal_code']) {
            if (!shipping[k].trim()) {
                showToast('Lengkapi alamat pengiriman dulu.', 'warn');
                document.getElementById(k)?.focus();
                return;
            }
        }
        addressLabel = val('addr_label');
    } else {
        shippingAddressId = sel;
    }
    if (!IS_EMBEDDED && !window.ethereum) return uiAlert({ title: 'MetaMask dibutuhkan', message: 'Install ekstensi MetaMask untuk melanjutkan.', type: 'warn' });
    if (!LINES.length) return;

    const sellers    = LINES.map(l => l.seller);
    const amounts    = LINES.map(l => l.amount);
    const productIds = LINES.map(l => l.product_uuid);
    const orderId    = 'CART-' + Date.now();

    const _grandFmt = _fmtTlkm(PROD_TOTAL + (SHIP_CHARGED ? (SHIP_TLKM || 0) : 0));
    const ok = await uiConfirm({
        title: 'Konfirmasi Pembayaran',
        message: (SHIP_CHARGED && SHIP_TLKM > 0)
            ? `Bayar <b class="text-blue-600">${_grandFmt} TLKM</b> untuk ${LINES.length} item?<br><span class="text-xs text-slate-400">Produk ${TOTAL_FMT} ke escrow penjual + ongkir ${_fmtTlkm(SHIP_TLKM)} ke platform (transaksi terpisah).</span>`
            : `Bayar total <b class="text-blue-600">${TOTAL_FMT} TLKM</b> untuk ${LINES.length} item?<br><span class="text-xs text-slate-400">Dana tiap penjual ditahan di escrow terpisah.</span>`,
        confirmText: 'Ya, bayar'
    });
    if (!ok) return;

    let pin = null;
    if (IS_EMBEDDED) { pin = await askPin('Bayar Belanja'); if (!pin) return; }

    const btn = document.getElementById('payBtn');
    btn.disabled = true;

    const INSURED = INSURANCE_ENABLED && (document.getElementById('insToggle')?.checked || false);

    // Payload disiapkan; tx_hash diisi setelah bayar. items minimal (backend ambil dari chain).
    const payload = {
        order_id: orderId, tx_hash: null,
        shipping_address_id: shippingAddressId, shipping, address_label: addressLabel,
        items: LINES.map(l => ({ product_id: l.db_product_id, item_index: l.index })),
        is_insured: INSURED ? 1 : 0,
    };
    // Bayar premi garansi (transaksi terpisah). Best-effort: gagal → order tetap jalan tanpa garansi.
    const payPremium = async () => {
        if (!INSURED) return;
        try { payload.premium_tx = await payInsurancePremium(pin); }
        catch (e) { payload.is_insured = 0; showToast('Premi garansi gagal dibayar — order diproses tanpa garansi.', 'warn'); }
    };
    // Bayar ongkir ke wallet platform (transaksi terpisah). Best-effort.
    const payShip = async () => {
        if (!SHIP_CHARGED || !(SHIP_TLKM > 0)) return;
        try { payload.shipping_tx = await payShipping(pin); }
        catch (e) { showToast('Ongkir gagal dibayar — order tetap diproses (ongkir menyusul).', 'warn'); }
    };

    txProgress.open('Memproses Pembayaran', IS_EMBEDDED
        ? ['Tanda tangan dengan PIN', 'Verifikasi & simpan']
        : ['Memeriksa jaringan', 'Menyetujui total (approve)', 'Membayar ke escrow', 'Verifikasi & simpan']);

    try {
        let txHash;
        if (IS_EMBEDDED) {
            // Embedded: backend approve + payCart pakai PIN, kembalikan hash payCart.
            txProgress.active(0, 'Approve + bayar via PIN…');
            txHash = await pinTx('/pin/checkout', { pin, order_id: orderId, sellers, amounts, productIds });
            payload.tx_hash = txHash;
            txProgress.done(0);
            if (INSURED) { txProgress.active(1, 'Membayar premi garansi…'); await payPremium(); }
            if (SHIP_CHARGED && SHIP_TLKM > 0) { txProgress.active(1, 'Membayar ongkir…'); await payShip(); }
            localStorage.setItem('pendingOrder:' + orderId, JSON.stringify(payload));
            txProgress.active(1, 'Verifikasi on-chain & menyimpan…');
            await submitOrder(payload);
            txProgress.done(1);
        } else {
            txProgress.active(0); await checkNetwork(); txProgress.done(0);
            txProgress.active(1, 'Setujui approve total di MetaMask…');
            await approveToken(TOTAL);
            txProgress.done(1);
            txProgress.active(2, 'Konfirmasi pembayaran di MetaMask…');
            const r = await payCart({ sellers, amounts, productIds, orderId });
            txHash = r.txHash;
            payload.tx_hash = txHash;
            txProgress.done(2);
            if (INSURED) { txProgress.active(3, 'Membayar premi garansi…'); await payPremium(); }
            if (SHIP_CHARGED && SHIP_TLKM > 0) { txProgress.active(3, 'Membayar ongkir…'); await payShip(); }
            localStorage.setItem('pendingOrder:' + orderId, JSON.stringify(payload));
            txProgress.active(3, 'Verifikasi on-chain & menyimpan…');
            await submitOrder(payload);
            txProgress.done(3);
        }

        setTimeout(() => {
            txProgress.close();
            updateCartBadge(0);
            uiAlert({
                title: 'Pembayaran Berhasil',
                message: `Order terverifikasi on-chain.<br><a href="${EXPLORER_URL}/tx/${txHash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`,
                type: 'success'
            }).then(() => window.location.href = '/orders');
        }, 500);

    } catch (e) {
        console.error(e);
        txProgress.close();
        if (payload.tx_hash) {
            // Sudah bayar on-chain, tapi simpan/verifikasi tertunda -> akan di-retry otomatis.
            uiAlert({
                title: 'Pembayaran Terkirim',
                message: 'Pembayaran on-chain berhasil, tapi penyimpanan tertunda (mungkin menunggu konfirmasi jaringan). Sistem akan menyimpan otomatis — cek halaman Order sebentar lagi.',
                type: 'warn'
            }).then(() => window.location.href = '/orders');
        } else {
            uiAlert({ title: 'Transaksi Gagal', message: niceError(e), type: 'error' });
            btn.disabled = false;
        }
    }
}
</script>
@endsection

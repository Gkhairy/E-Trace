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
                            <input type="radio" name="addr" value="{{ $a->id }}" class="mt-1 accent-blue-600" onchange="selectAddr(this)" @checked($loop->first)>
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
                                <span>Ongkir · {{ $shipEstimates[$seller]['method'] }}{{ $shipEstimates[$seller]['km'] !== null ? ' (~'.$shipEstimates[$seller]['km'].' km)' : '' }}</span>
                                <span>{{ rtrim(rtrim(number_format($shipEstimates[$seller]['fee_tlkm'], 2), '0'), '.') }} TLKM</span>
                            </div>
                        @endif
                        <div class="divide-y divide-slate-100">
                            @foreach($group as $it)
                                <div class="px-4 py-3 flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-white border border-slate-100 flex items-center justify-center p-1 shrink-0">
                                        <img src="{{ $it->product->image ? '/product_images/'.$it->product->image : 'https://placehold.co/80x80/f1f5f9/94a3b8?text=—' }}" onerror="this.src='https://placehold.co/80x80/f1f5f9/94a3b8?text=—'" class="max-w-full max-h-full object-contain">
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
            {{-- H7: estimasi ongkir (produk fisik) dalam TLKM (Rp1.000 = 1 TLKM) --}}
            <div class="flex justify-between text-sm text-slate-600 mb-2 border-t border-slate-100 pt-3 mt-3">
                <span>Estimasi ongkir<sup class="text-slate-400">*</sup></span>
                <span>{{ rtrim(rtrim(number_format($shipTotalTlkm, 2), '0'), '.') }} TLKM</span>
            </div>
            <div class="flex justify-between items-end">
                <span class="text-sm text-slate-500">Total produk (on-chain)</span>
                <span class="text-2xl font-extrabold text-slate-900">{{ rtrim(rtrim(number_format($total, 2), '0'), '.') }} <span class="text-sm text-blue-600 font-semibold">TLKM</span></span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5 leading-snug">*Ongkir dihitung via <b>RajaOngkir</b> (tarif kurir termurah) atau estimasi jarak bila kota tak dikenali, dikonversi ke TLKM (Rp1.000 = 1 TLKM), diselesaikan terpisah dari escrow produk. {{ $buyerCity ? 'Kota tujuan: '.$buyerCity.'.' : 'Pilih/isi alamat untuk estimasi akurat.' }}</p>

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
                Bayar {{ rtrim(rtrim(number_format($total, 2), '0'), '.') }} TLKM
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const LINES = @json($lines);
const TOTAL = @json($totalStr);

function val(id) { const el = document.getElementById(id); return el ? el.value : ''; }

function selectedAddr() {
    const r = document.querySelector('input[name=addr]:checked');
    return r ? r.value : 'new';   // tanpa alamat tersimpan -> selalu form baru
}
function selectAddr(radio) {
    document.querySelectorAll('[data-addrcard]').forEach(l => l.classList.remove('border-blue-500', 'bg-blue-50/40'));
    radio.closest('[data-addrcard]')?.classList.add('border-blue-500', 'bg-blue-50/40');
    const form = document.getElementById('newAddrForm');
    if (form) form.classList.toggle('hidden', radio.value !== 'new');
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

    const ok = await uiConfirm({
        title: 'Konfirmasi Pembayaran',
        message: `Bayar total <b class="text-blue-600">${TOTAL} TLKM</b> untuk ${LINES.length} item?<br><span class="text-xs text-slate-400">Dana tiap penjual ditahan di escrow terpisah.</span>`,
        confirmText: 'Ya, bayar'
    });
    if (!ok) return;

    let pin = null;
    if (IS_EMBEDDED) { pin = await askPin('Bayar Belanja'); if (!pin) return; }

    const btn = document.getElementById('payBtn');
    btn.disabled = true;

    // Payload disiapkan; tx_hash diisi setelah bayar. items minimal (backend ambil dari chain).
    const payload = {
        order_id: orderId, tx_hash: null,
        shipping_address_id: shippingAddressId, shipping, address_label: addressLabel,
        items: LINES.map(l => ({ product_id: l.db_product_id, item_index: l.index })),
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
            localStorage.setItem('pendingOrder:' + orderId, JSON.stringify(payload));
            txProgress.done(0);
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
            localStorage.setItem('pendingOrder:' + orderId, JSON.stringify(payload));
            txProgress.done(2);
            txProgress.active(3, 'Verifikasi on-chain & menyimpan…');
            await submitOrder(payload);
            txProgress.done(3);
        }

        setTimeout(() => {
            txProgress.close();
            updateCartBadge(0);
            uiAlert({
                title: 'Pembayaran Berhasil',
                message: `Order terverifikasi on-chain.<br><a href="https://sepolia.etherscan.io/tx/${txHash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`,
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

@extends('layouts.app')

@section('content')

@php
    $statusStore = [
        'active'    => ['Aktif', 'bg-green-50 text-green-700 border-green-200'],
        'pending'   => ['Menunggu Review', 'bg-amber-50 text-amber-700 border-amber-200'],
        'suspended' => ['Disuspend', 'bg-red-50 text-red-700 border-red-200'],
    ][$store->status] ?? ['—', 'bg-slate-100 text-slate-600 border-slate-200'];

    $itemBadge = [
        'paid'                 => ['Bayar masuk (escrow)', 'bg-amber-50 text-amber-700 border-amber-200'],
        'pending_confirmation' => ['Menunggu jaringan',    'bg-slate-100 text-slate-500 border-slate-200'],
        'completed'            => ['Selesai — dana diterima','bg-green-50 text-green-700 border-green-200'],
        'refunded'             => ['Refund',               'bg-slate-100 text-slate-600 border-slate-200'],
        'disputed'             => ['Sengketa',             'bg-red-50 text-red-700 border-red-200'],
    ];
    $fBadge = [
        'pending'    => ['Belum diproses', 'bg-slate-100 text-slate-600 border-slate-200'],
        'processing' => ['Diproses',       'bg-blue-50 text-blue-700 border-blue-200'],
        'shipped'    => ['Dikirim',        'bg-indigo-50 text-indigo-700 border-indigo-200'],
        'delivered'  => ['Diterima',       'bg-green-50 text-green-700 border-green-200'],
    ];
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
@endphp

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ session('error') }}</div>
@endif

{{-- ===== HEADER TOKO ===== --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="h-24 md:h-28 bg-blue-600">
        @if($store->banner)
            <img src="/store_images/{{ $store->banner }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
        @endif
    </div>
    <div class="px-6 pb-5">
        <div class="flex flex-wrap items-center gap-4">
            <div class="-mt-10 w-20 h-20 rounded-2xl bg-white border-4 border-white shadow flex items-center justify-center overflow-hidden shrink-0">
                @if($store->logo)
                    <img src="/store_images/{{ $store->logo }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                @else
                    <div class="w-full h-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl font-bold">{{ strtoupper(substr($store->name,0,1)) }}</div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl font-bold text-slate-900">{{ $store->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusStore[1] }}">{{ $statusStore[0] }}</span>
                </div>
                <p class="text-xs text-slate-400 font-mono mt-0.5">Payout {{ substr($store->payout_wallet,0,8) }}…{{ substr($store->payout_wallet,-6) }}</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="/seller/store" class="text-sm font-medium px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 transition">Edit Toko</a>
                <a href="/products/create" class="text-sm font-semibold px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm">+ Produk</a>
            </div>
        </div>
    </div>
</div>

{{-- ===== STATISTIK ===== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @php
        $cards = [
            ['Produk', $stats['products'], '', 'text-slate-900'],
            ['Order Masuk', $stats['orders'], '', 'text-slate-900'],
            ['Ditahan Escrow', $fmt($stats['escrow_active']).' TLKM', 'menunggu konfirmasi pembeli', 'text-amber-600'],
            ['Dana Diterima', $fmt($stats['released_net']).' TLKM', 'setelah fee 1%', 'text-green-600'],
        ];
    @endphp
    @foreach($cards as $c)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <p class="text-xs text-slate-500">{{ $c[0] }}</p>
            <p class="text-2xl font-extrabold mt-1 {{ $c[3] }}">{{ $c[1] }}</p>
            @if($c[2])<p class="text-[11px] text-slate-400 mt-0.5">{{ $c[2] }}</p>@endif
        </div>
    @endforeach
</div>

{{-- ===== RINCIAN BIAYA & PAJAK (khusus penjual — H8/H9) ===== --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-8">
    <div class="flex items-center gap-2 mb-1">
        <h2 class="text-lg font-bold text-slate-900">Rincian Biaya &amp; Pajak</h2>
        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">khusus penjual</span>
    </div>
    <p class="text-sm text-slate-500 mb-4">Dihitung dari penjualan <b>selesai</b>. Fee &amp; pajak ini <b>tidak</b> ditampilkan ke pembeli — pembeli hanya membayar harga produk.</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Penjualan bruto</p>
            <p class="text-lg font-extrabold text-slate-900 mt-0.5">{{ $fmt($stats['gross']) }} <span class="text-xs text-blue-600">TLKM</span></p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Fee platform ({{ rtrim(rtrim(number_format($stats['fee_pct'],2),'0'),'.') }}%)</p>
            <p class="text-lg font-extrabold text-amber-600 mt-0.5">−{{ $fmt($stats['fee']) }} <span class="text-xs">TLKM</span></p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">PPN {{ rtrim(rtrim(number_format($stats['vat_pct'],2),'0'),'.') }}% <span class="text-slate-400">(atas fee)</span></p>
            <p class="text-lg font-extrabold text-slate-700 mt-0.5">{{ $fmt($stats['vat']) }} <span class="text-xs">TLKM</span></p>
        </div>
        <div class="rounded-xl border border-green-100 bg-green-50 p-4">
            <p class="text-xs text-green-700">Diterima (on-chain)</p>
            <p class="text-lg font-extrabold text-green-700 mt-0.5">{{ $fmt($stats['released_net']) }} <span class="text-xs">TLKM</span></p>
        </div>
    </div>
    <p class="text-[11px] text-slate-400 mt-3">Fee platform dipotong otomatis on-chain saat dana dilepas. PPN 11% dihitung atas fee jasa platform (untuk pelaporan pajak) dan ditanggung penjual.</p>
</div>

{{-- ===== LAPORAN PENJUALAN (Excel/PDF) ===== --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-8">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Laporan Penjualan</h2>
            <p class="text-sm text-slate-500 mt-0.5">Unduh otomatis dari transaksi toko. Pilih rentang tanggal, lalu unduh Excel/PDF.</p>
        </div>
        <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 17v-6h6v6M5 3h9l5 5v13a1 1 0 01-1 1H5a1 1 0 01-1-1V4a1 1 0 011-1z"/></svg>
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Dari tanggal</label>
            <input type="date" id="repFrom" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="px-3 py-2 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Ke tanggal</label>
            <input type="date" id="repTo" value="{{ now()->format('Y-m-d') }}" class="px-3 py-2 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
        <div class="border border-slate-200 rounded-xl p-4">
            <p class="text-sm font-semibold text-slate-800 mb-0.5">Laporan Harian</p>
            <p class="text-xs text-slate-500 mb-3">Rincian per transaksi (tanggal, no. order, produk/pembeli, nilai, status).</p>
            <div class="flex gap-2">
                <button type="button" onclick="dlReport('daily','xlsx')" class="flex-1 text-sm font-medium px-3 py-2 rounded-lg bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 transition">⬇ Excel</button>
                <button type="button" onclick="dlReport('daily','pdf')" class="flex-1 text-sm font-medium px-3 py-2 rounded-lg bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 transition">⬇ PDF</button>
            </div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <p class="text-sm font-semibold text-slate-800 mb-0.5">Laporan Bulanan</p>
            <p class="text-xs text-slate-500 mb-3">Rekap per bulan (bulan, tahun, total penjualan, jumlah transaksi).</p>
            <div class="flex gap-2">
                <button type="button" onclick="dlReport('monthly','xlsx')" class="flex-1 text-sm font-medium px-3 py-2 rounded-lg bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 transition">⬇ Excel</button>
                <button type="button" onclick="dlReport('monthly','pdf')" class="flex-1 text-sm font-medium px-3 py-2 rounded-lg bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 transition">⬇ PDF</button>
            </div>
        </div>
    </div>
</div>

{{-- ===== ORDER MASUK ===== --}}
<h2 class="text-lg font-bold text-slate-900 mb-3">Order Masuk</h2>
@if($items->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center text-slate-500 mb-8">Belum ada order.</div>
@else
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 font-medium">Produk</th>
                        <th class="px-4 py-3 font-medium">Pembeli & Alamat</th>
                        <th class="px-4 py-3 font-medium">Nominal</th>
                        <th class="px-4 py-3 font-medium">Status Bayar</th>
                        <th class="px-4 py-3 font-medium">Pengiriman</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $it)
                        @php $b = $itemBadge[$it->status] ?? $itemBadge['paid']; $addr = $it->order->shippingAddress ?? null; @endphp
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $it->product->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs max-w-[280px]">
                                @if($addr)
                                    <span class="font-medium text-slate-700">{{ $addr->recipient_name }}</span> ({{ $addr->phone }})<br>
                                    {{ $addr->address }}, {{ $addr->city }} {{ $addr->postal_code }}
                                    @if($addr->notes)<br><span class="text-slate-400">Catatan: {{ $addr->notes }}</span>@endif
                                @else — @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-semibold text-blue-600">{{ $fmt($it->amount) }} TLKM</span>
                                <span class="block text-[11px] text-slate-400">≈ {{ $fmt((float)$it->amount * 0.99) }} diterima</span>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $b[1] }} whitespace-nowrap">{{ $b[0] }}</span></td>
                            <td class="px-4 py-3">
                                @php $fb = $fBadge[$it->fulfillment_status] ?? $fBadge['pending']; @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $fb[1] }} whitespace-nowrap">{{ $fb[0] }}</span>
                                @if($it->tracking_number)
                                    <div class="text-[11px] text-slate-400 mt-1">Resi: <b class="text-slate-600">{{ $it->tracking_number }}</b>@if($it->courier) · {{ $it->courier }}@endif</div>
                                @endif
                                @if($it->status === 'paid' && in_array($it->fulfillment_status, ['pending','processing']))
                                    <div class="mt-2 space-y-1.5">
                                        @if($it->fulfillment_status === 'pending')
                                            <form method="POST" action="/seller/fulfill">@csrf
                                                <input type="hidden" name="item_id" value="{{ $it->id }}">
                                                <input type="hidden" name="action" value="process">
                                                <button class="text-xs px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700">Proses Pesanan</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="/seller/fulfill" class="flex flex-col gap-1">@csrf
                                            <input type="hidden" name="item_id" value="{{ $it->id }}">
                                            <input type="hidden" name="action" value="ship">
                                            <input name="tracking_number" placeholder="No. Resi" required class="text-xs px-2 py-1 rounded-lg border border-slate-300 w-36 outline-none focus:border-blue-500">
                                            <input name="courier" placeholder="Kurir (opsional)" class="text-xs px-2 py-1 rounded-lg border border-slate-300 w-36 outline-none focus:border-blue-500">
                                            <button class="text-xs px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium w-fit">Kirim + Resi</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">{{ $it->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- ===== PRODUK ===== --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-bold text-slate-900">Produk Toko</h2>
    <a href="/products/create" class="text-sm text-blue-600 hover:underline">+ Tambah produk</a>
</div>
@if($products->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center text-slate-500">Belum ada produk. <a href="/products/create" class="text-blue-600 hover:underline">Tambah sekarang</a>.</div>
@else
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($products as $p)
            <a href="/products/{{ $p->id }}" class="bg-white border border-slate-200 rounded-2xl overflow-hidden hover:border-blue-400 hover:shadow-md transition">
                <div class="aspect-square bg-white flex items-center justify-center p-3 border-b border-slate-100">
                    <img src="{{ $p->image ? '/product_images/'.$p->image : 'https://placehold.co/300x300/f1f5f9/94a3b8?text=—' }}" onerror="this.src='https://placehold.co/300x300/f1f5f9/94a3b8?text=—'" class="max-w-full max-h-full object-contain">
                </div>
                <div class="p-3">
                    <p class="text-sm font-medium text-slate-800 line-clamp-1">{{ $p->name }}</p>
                    <p class="text-sm font-bold text-slate-900 mt-0.5">{{ $fmt($p->price_usdc) }} <span class="text-xs text-blue-600">TLKM</span></p>
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection

@section('scripts')
<script>
    // Unduh laporan penjualan sesuai rentang tanggal yang dipilih.
    function dlReport(type, format) {
        const from = document.getElementById('repFrom').value;
        const to   = document.getElementById('repTo').value;
        const q = new URLSearchParams({ type, format, from, to });
        window.location = '/seller/reports/download?' + q.toString();
    }
</script>
@endsection

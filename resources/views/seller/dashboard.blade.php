@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $thumb = fn ($p) => $p?->thumbnail() ?? 'https://placehold.co/160x160/f1f5f9/94a3b8?text=-';

    // Penjualan 30 hari terakhir vs 30 hari sebelumnya, untuk kartu pertama.
    $last30 = array_sum(array_column(array_slice($daily, -30), 'sales'));
    $prev30 = array_sum(array_column(array_slice($daily, -60, 30), 'sales'));
    $delta = $prev30 > 0 ? round(($last30 - $prev30) / $prev30 * 100, 1) : null;

    $tiles = [
        [
            'label' => 'Penjualan 30 hari', 'value' => $fmt($last30) . ' TLKM',
            'icon'  => 'M3 17l6-6 4 4 8-8M14 7h7v7', 'iconTone' => 'bg-blue-50 text-blue-600',
            'note'  => $delta === null ? 'Belum ada pembanding bulan lalu' : null, 'delta' => $delta,
        ],
        [
            'label' => 'Perlu dikirim', 'value' => $toShip . ' pesanan',
            'icon'  => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'iconTone' => $toShip ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500',
            'note'  => $toShip ? null : 'Semua pesanan sudah dikirim', 'link' => $toShip ? ['/seller/orders?tab=kirim', 'Kirim sekarang'] : null,
        ],
        [
            'label' => 'Dana tertahan', 'value' => $fmt($money['escrow']) . ' TLKM',
            'icon'  => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'iconTone' => 'bg-amber-50 text-amber-600',
            'note'  => 'Cair setelah pembeli menerima barang',
        ],
        [
            'label' => 'Sudah cair', 'value' => $fmt($money['released_net']) . ' TLKM',
            'icon'  => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'iconTone' => 'bg-emerald-50 text-emerald-600',
            'note'  => 'Masuk ke wallet toko, setelah fee ' . $fmt($money['fee_pct']) . '%',
        ],
    ];
@endphp

@include('seller._nav')

{{-- ================= KARTU RINGKAS ================= --}}
<section aria-label="Ringkasan toko" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach($tiles as $t)
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 flex gap-4">
            <span class="w-11 h-11 rounded-full flex items-center justify-center shrink-0 {{ $t['iconTone'] }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $t['icon'] }}"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">{{ $t['label'] }}</p>
                <p class="text-xl font-bold text-slate-900 tabular-nums leading-snug">{{ $t['value'] }}</p>
                @if(array_key_exists('delta', $t) && $t['delta'] !== null)
                    @php $up = $t['delta'] >= 0; @endphp
                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold {{ $up ? 'text-emerald-700' : 'text-red-700' }}">
                        <svg class="w-3.5 h-3.5 {{ $up ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 15l7-7 7 7"/></svg>
                        {{ $up ? '+' : '' }}{{ $t['delta'] }}%
                        <span class="font-normal text-slate-500">dari bulan lalu</span>
                    </p>
                @elseif(!empty($t['link']))
                    <a href="{{ $t['link'][0] }}" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:text-blue-800 hover:underline">
                        {{ $t['link'][1] }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"/></svg>
                    </a>
                @elseif($t['note'])
                    <p class="mt-1 text-xs text-slate-500">{{ $t['note'] }}</p>
                @endif
            </div>
        </div>
    @endforeach
</section>

{{-- ================= LAPORAN PENJUALAN ================= --}}
<section aria-labelledby="sales-title" class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6 mb-6">
    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,2.4fr)] gap-6 lg:gap-10">
        <div class="flex flex-col">
            <h2 id="sales-title" class="text-lg font-bold text-slate-900">Laporan penjualan</h2>
            <p class="text-sm text-slate-500">Total nilai pesanan yang masuk, tanpa pesanan yang dikembalikan.</p>

            <p class="mt-6 text-4xl font-extrabold tracking-tight text-slate-900 tabular-nums leading-none">
                <span id="salesTotal">{{ $fmt($last30) }}</span> <span class="text-lg font-bold text-slate-400">TLKM</span>
            </p>
            <p id="salesDelta" class="mt-2 text-sm text-slate-500 min-h-[1.25rem]"></p>
            <p id="salesOrders" class="text-sm text-slate-500"></p>

            <div class="mt-6 lg:mt-auto inline-flex self-start rounded-xl bg-slate-100 p-1 text-sm" role="group" aria-label="Rentang waktu">
                @foreach([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $d => $label)
                    <button type="button" data-range="{{ $d }}" class="range-btn px-3.5 py-1.5 rounded-lg font-medium transition-colors">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="min-w-0">
            <div class="flex flex-wrap justify-end gap-4 mb-2 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 rounded bg-blue-600"></span>Penjualan (TLKM)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-0 border-t-2 border-dashed border-amber-500"></span>Jumlah pesanan</span>
            </div>
            <div class="relative h-64 md:h-72"><canvas id="salesChart" role="img" aria-describedby="salesSummary"></canvas></div>
            <p id="salesSummary" class="sr-only"></p>
        </div>
    </div>
</section>

{{-- ================= PESANAN TERBARU & TERLARIS ================= --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <section aria-labelledby="recent-title" class="lg:col-span-2 bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm">
        <div class="flex items-center justify-between gap-3 px-5 md:px-6 pt-5">
            <h2 id="recent-title" class="text-lg font-bold text-slate-900">Pesanan terbaru</h2>
            <a href="/seller/orders?tab=semua" class="text-sm font-semibold text-blue-700 hover:text-blue-800 hover:underline">Lihat semua</a>
        </div>

        @if($recent->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-slate-800">Belum ada pesanan</p>
                <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">Pesanan muncul di sini begitu pembeli membayar. Produk dengan foto yang jelas biasanya lebih cepat laku.</p>
                <a href="/seller/products" class="mt-4 inline-flex items-center h-10 px-4 rounded-xl bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold transition-colors">Rapikan produk</a>
            </div>
        @else
            <div class="overflow-x-auto mt-3">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-100">
                            <th scope="col" class="font-medium px-5 md:px-6 py-2.5">Produk</th>
                            <th scope="col" class="font-medium px-3 py-2.5 hidden sm:table-cell">Pembeli</th>
                            <th scope="col" class="font-medium px-3 py-2.5 hidden md:table-cell">Tanggal</th>
                            <th scope="col" class="font-medium px-3 py-2.5 text-right">Nominal</th>
                            <th scope="col" class="font-medium px-5 md:px-6 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recent as $it)
                            @php $st = \App\Support\SellerStatus::for($it); $addr = $it->order?->shippingAddress; @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 md:px-6 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img src="{{ $thumb($it->product) }}" alt="" loading="lazy" onerror="this.src='https://placehold.co/160x160/f1f5f9/94a3b8?text=-'" class="w-10 h-10 rounded-lg object-contain bg-slate-50 ring-1 ring-slate-100 shrink-0">
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-800 truncate max-w-[14rem]">{{ $it->product->name ?? 'Produk dihapus' }}</p>
                                            @if(($it->quantity ?? 1) > 1)<p class="text-xs text-slate-500">{{ $it->quantity }} barang</p>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-slate-600 hidden sm:table-cell">{{ $addr->recipient_name ?? '-' }}</td>
                                <td class="px-3 py-3 text-slate-500 whitespace-nowrap hidden md:table-cell">{{ $it->created_at->translatedFormat('d M Y') }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-slate-900 tabular-nums whitespace-nowrap">{{ $fmt($it->amount) }}</td>
                                <td class="px-5 md:px-6 py-3">
                                    <span class="inline-flex px-2 py-1 rounded-md ring-1 text-xs font-medium whitespace-nowrap {{ $st['tone'] }}" title="{{ $st['hint'] }}">{{ $st['label'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="px-5 md:px-6 py-3 text-xs text-slate-500 border-t border-slate-100">Nominal dalam TLKM, sebelum fee platform.</p>
        @endif
    </section>

    <section aria-labelledby="best-title" class="rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 bg-gradient-to-b from-blue-50 to-white flex flex-col">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="best-title" class="text-lg font-bold text-slate-900">Barang terlaris</h2>
                <p class="text-sm text-slate-600">Produk yang paling banyak dibeli.</p>
            </div>
            @if($best->count() > 1)
                <div class="flex gap-1.5 shrink-0">
                    <button type="button" data-best="-1" aria-label="Produk sebelumnya" class="w-9 h-9 rounded-full bg-white ring-1 ring-slate-200 hover:ring-slate-300 flex items-center justify-center text-slate-600 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" data-best="1" aria-label="Produk berikutnya" class="w-9 h-9 rounded-full bg-white ring-1 ring-slate-200 hover:ring-slate-300 flex items-center justify-center text-slate-600 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            @endif
        </div>

        @if($best->isEmpty())
            <div class="flex-1 flex flex-col items-center justify-center text-center py-10">
                <p class="font-semibold text-slate-800">Belum ada yang terjual</p>
                <p class="mt-1 text-sm text-slate-500 max-w-[16rem]">Produk terlarismu akan tampil di sini setelah penjualan pertama.</p>
            </div>
        @else
            <ol id="bestTrack" class="mt-4 flex gap-3 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Daftar barang terlaris">
                @foreach($best as $i => $b)
                    <li class="snap-center shrink-0 w-full">
                        <a href="/products/{{ $b['product']->id }}" class="block rounded-2xl bg-white ring-1 ring-slate-200 p-4 hover:ring-blue-300 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                            <div class="relative aspect-[4/3] rounded-xl bg-slate-50 flex items-center justify-center overflow-hidden">
                                <img src="{{ $thumb($b['product']) }}" alt="{{ $b['product']->name }}" loading="lazy" onerror="this.src='https://placehold.co/320x240/f1f5f9/94a3b8?text=-'" class="max-w-full max-h-full object-contain">
                            </div>
                            <p class="mt-3 text-xs font-semibold text-blue-700">Terlaris #{{ $i + 1 }}</p>
                            <p class="font-semibold text-slate-900 truncate">{{ $b['product']->name }}</p>
                            <p class="text-sm text-slate-600"><b class="tabular-nums text-slate-900">{{ $b['sold'] }}</b> terjual, <span class="tabular-nums">{{ $fmt($b['revenue']) }}</span> TLKM</p>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>

{{-- ================= STOK, DANA, LAPORAN ================= --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <section aria-labelledby="stock-title" class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5">
        <h2 id="stock-title" class="font-bold text-slate-900">Stok menipis</h2>
        <p class="text-sm text-slate-500">Produk dengan stok 3 atau kurang.</p>
        @if($lowStock->isEmpty())
            <p class="mt-5 flex items-center gap-2 text-sm text-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Stok semua produk aman.
            </p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach($lowStock as $p)
                    <li class="flex items-center gap-3">
                        <img src="{{ $thumb($p) }}" alt="" loading="lazy" onerror="this.src='https://placehold.co/160x160/f1f5f9/94a3b8?text=-'" class="w-9 h-9 rounded-lg object-contain bg-slate-50 ring-1 ring-slate-100 shrink-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $p->name }}</p>
                            <p class="text-xs {{ $p->stock == 0 ? 'text-red-700 font-semibold' : 'text-amber-700' }}">{{ $p->stock == 0 ? 'Habis, tidak bisa dibeli' : 'Sisa ' . $p->stock }}</p>
                        </div>
                        <a href="/products/{{ $p->id }}/edit" class="text-sm font-semibold text-blue-700 hover:text-blue-800 hover:underline shrink-0">Tambah stok</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-labelledby="money-title" class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5">
        <h2 id="money-title" class="font-bold text-slate-900">Rincian dana</h2>
        <p class="text-sm text-slate-500">Dari pesanan yang sudah selesai. Hanya kamu yang melihat ini.</p>
        <dl class="mt-4 space-y-2.5 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-600">Penjualan selesai</dt><dd class="tabular-nums font-medium text-slate-900">{{ $fmt($money['gross']) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-600">Fee platform {{ $fmt($money['fee_pct']) }}%</dt><dd class="tabular-nums font-medium text-red-700">-{{ $fmt($money['fee']) }}</dd></div>
            <div class="flex justify-between gap-3 pt-2.5 border-t border-slate-100"><dt class="font-semibold text-slate-900">Diterima di wallet</dt><dd class="tabular-nums font-bold text-emerald-700">{{ $fmt($money['released_net']) }} TLKM</dd></div>
        </dl>
        <p class="mt-4 text-xs text-slate-500 leading-relaxed">
            Fee dipotong otomatis saat dana cair. Untuk laporan pajak: PPN {{ $fmt($money['vat_pct']) }}% atas fee = <span class="tabular-nums">{{ $fmt($money['vat']) }}</span> TLKM, ditanggung penjual.
            @if($money['refunded'] > 0) Total dikembalikan ke pembeli: <span class="tabular-nums">{{ $fmt($money['refunded']) }}</span> TLKM.@endif
        </p>
    </section>

    <section aria-labelledby="report-title" class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:col-span-2 lg:col-span-1">
        <h2 id="report-title" class="font-bold text-slate-900">Unduh laporan</h2>
        <p class="text-sm text-slate-500">Pilih tanggal, lalu unduh untuk pembukuan.</p>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div>
                <label for="repFrom" class="block text-xs font-medium text-slate-600 mb-1">Dari</label>
                <input type="date" id="repFrom" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="w-full h-10 px-3 rounded-xl ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div>
                <label for="repTo" class="block text-xs font-medium text-slate-600 mb-1">Sampai</label>
                <input type="date" id="repTo" value="{{ now()->format('Y-m-d') }}" class="w-full h-10 px-3 rounded-xl ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
        </div>
        <div class="mt-4 space-y-2">
            @foreach(['daily' => ['Per transaksi', 'Setiap pesanan satu baris'], 'monthly' => ['Rekap bulanan', 'Total per bulan']] as $type => [$name, $desc])
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800">{{ $name }}</p>
                        <p class="text-xs text-slate-500">{{ $desc }}</p>
                    </div>
                    <button type="button" onclick="dlReport('{{ $type }}','xlsx')" class="h-8 px-2.5 rounded-lg bg-white ring-1 ring-slate-200 hover:ring-slate-300 text-xs font-semibold text-slate-700 transition">Excel</button>
                    <button type="button" onclick="dlReport('{{ $type }}','pdf')" class="h-8 px-2.5 rounded-lg bg-white ring-1 ring-slate-200 hover:ring-slate-300 text-xs font-semibold text-slate-700 transition">PDF</button>
                </div>
            @endforeach
        </div>
        <p id="repError" role="alert" class="mt-2 text-xs text-red-700 hidden"></p>
    </section>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
function dlReport(type, format) {
    var from = document.getElementById('repFrom').value, to = document.getElementById('repTo').value;
    var err = document.getElementById('repError');
    if (!from || !to || from > to) {
        err.textContent = 'Tanggal "Dari" harus sebelum atau sama dengan "Sampai".';
        err.classList.remove('hidden');
        return;
    }
    err.classList.add('hidden');
    window.location = '/seller/reports/download?' + new URLSearchParams({ type: type, format: format, from: from, to: to });
}

(function () {
    // ---- Barang terlaris: geser satu kartu.
    var track = document.getElementById('bestTrack');
    document.querySelectorAll('[data-best]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (track) track.scrollBy({ left: +b.dataset.best * track.clientWidth, behavior: 'smooth' });
        });
    });

    // ---- Laporan penjualan: 7/30/90 hari, dibanding periode sebelumnya yang sama panjang.
    var daily = @json($daily);
    var money = function (n) { return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(n); };
    var sum = function (rows, k) { return rows.reduce(function (a, r) { return a + r[k]; }, 0); };
    var range = 30;
    try { range = +localStorage.getItem('seller:range') || 30; } catch (e) {}
    var chart = null;

    function paint() {
        var cur = daily.slice(-range), prev = daily.slice(-2 * range, -range);
        var total = sum(cur, 'sales'), before = sum(prev, 'sales'), orders = sum(cur, 'orders');
        document.getElementById('salesTotal').textContent = money(total);
        document.getElementById('salesOrders').textContent = orders + ' pesanan dalam ' + range + ' hari terakhir';

        var d = document.getElementById('salesDelta');
        if (before > 0) {
            var pct = Math.round((total - before) / before * 1000) / 10, up = pct >= 0;
            d.innerHTML = '<span class="font-semibold ' + (up ? 'text-emerald-700' : 'text-red-700') + '">' + (up ? '+' : '') + pct + '%</span> dibanding ' + range + ' hari sebelumnya';
        } else {
            d.textContent = total > 0 ? 'Periode sebelumnya belum ada penjualan' : 'Belum ada penjualan di periode ini';
        }

        var best = cur.reduce(function (a, r) { return r.sales > a.sales ? r : a; }, { sales: 0 });
        document.getElementById('salesSummary').textContent = 'Penjualan ' + range + ' hari: ' + money(total) + ' TLKM dari ' + orders + ' pesanan.'
            + (best.sales > 0 ? ' Hari terbaik ' + best.date + ' dengan ' + money(best.sales) + ' TLKM.' : '');

        document.querySelectorAll('.range-btn').forEach(function (b) {
            var on = +b.dataset.range === range;
            b.setAttribute('aria-pressed', on);
            b.className = 'range-btn px-3.5 py-1.5 rounded-lg font-medium transition-colors ' + (on ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800');
        });

        if (!chart) return;
        chart.data.labels = cur.map(function (r) { return r.date; });
        chart.data.datasets[0].data = cur.map(function (r) { return r.sales; });
        chart.data.datasets[1].data = cur.map(function (r) { return r.orders; });
        chart.data.datasets[0].pointRadius = range <= 7 ? 3 : 0;
        chart.update();
    }

    document.querySelectorAll('.range-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            range = +b.dataset.range;
            try { localStorage.setItem('seller:range', range); } catch (e) {}
            paint();
        });
    });

    var ctx = document.getElementById('salesChart');
    if (typeof Chart !== 'undefined' && ctx) {
        var g = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
        g.addColorStop(0, 'rgba(37,99,235,0.18)');
        g.addColorStop(1, 'rgba(37,99,235,0)');
        var tick = { color: '#64748b', font: { size: 11, family: 'Hanken Grotesk' } };
        chart = new Chart(ctx, {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'Penjualan', data: [], yAxisID: 'y', borderColor: '#2563eb', backgroundColor: g, fill: true,
                  cubicInterpolationMode: 'monotone', borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5, pointBackgroundColor: '#2563eb' },
                { label: 'Pesanan', data: [], yAxisID: 'y1', borderColor: '#f59e0b', borderDash: [5, 4], borderWidth: 2,
                  cubicInterpolationMode: 'monotone', pointRadius: 0, pointHoverRadius: 4, pointBackgroundColor: '#f59e0b', fill: false }
            ] },
            options: {
                responsive: true, maintainAspectRatio: false, animation: { duration: 250 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a', padding: 12, cornerRadius: 10, displayColors: true, boxPadding: 4,
                        titleFont: { family: 'Hanken Grotesk', weight: '700' }, bodyFont: { family: 'Hanken Grotesk' },
                        callbacks: { label: function (c) { return c.datasetIndex === 0 ? ' ' + money(c.parsed.y) + ' TLKM' : ' ' + c.parsed.y + ' pesanan'; } }
                    }
                },
                scales: {
                    x:  { grid: { display: false }, border: { display: false }, ticks: Object.assign({ maxRotation: 0, autoSkipPadding: 16 }, tick) },
                    y:  { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: Object.assign({ maxTicksLimit: 5, callback: function (v) { return money(v); } }, tick) },
                    y1: { position: 'right', beginAtZero: true, grid: { display: false }, border: { display: false }, ticks: Object.assign({ precision: 0, maxTicksLimit: 5 }, tick) }
                }
            }
        });
    }
    paint();
})();
</script>
@endsection

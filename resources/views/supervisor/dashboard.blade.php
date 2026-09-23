@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $explorer = rtrim((string) config('chain.explorer_url'), '/');
    $short = fn ($a) => $a ? \App\Services\OpsWallets::short($a) : '—';

    // Umur antrian → warna: < 1 hari tenang, 1–2 hari perlu dilihat, > 2 hari terlambat.
    $ageTone = function ($since) {
        $h = $since ? $since->diffInHours(now()) : 0;
        return $h >= 48 ? 'bg-red-50 text-red-700 ring-red-200'
             : ($h >= 24 ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-slate-50 text-slate-600 ring-slate-200');
    };
    $age = fn ($since) => $since ? $since->diffForHumans(null, true) : '—';

    $queueMeta = [
        'disputes' => ['Sengketa', 'Pembeli mengajukan keberatan; dana tertahan sampai kamu memutus.', '/supervisor/disputes',
                       'text-red-600 bg-red-50', 'M12 3v18M5 7h14M7 7l-3 7a3 3 0 006 0L7 7zm10 0l-3 7a3 3 0 006 0l-3-7z',
                       'Tidak ada sengketa terbuka.'],
        'held'     => ['Ditahan AI', 'Keeper belum cukup yakin, atau nominalnya di atas batas otomatis.', '/supervisor/held',
                       'text-indigo-600 bg-indigo-50', 'M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                       'Semua order diputus otomatis.'],
        'claims'   => ['Klaim garansi', 'Pengiriman terlambat; keputusan bayar ongkir menunggu tinjauan.', '/supervisor/held',
                       'text-amber-600 bg-amber-50', 'M12 3l7 3v5c0 5-3.4 8.6-7 10-3.6-1.4-7-5-7-10V6l7-3z',
                       'Tidak ada klaim yang aktif.'],
    ];
    $waiting = collect($queue)->sum('count');
    $oldestAll = collect($queue)->flatMap(fn ($q) => $q['oldest'])->pluck('since')->filter()->min();

    // Distribusi dana per status item escrow (urutan = urutan batang).
    $itemMeta = [
        'paid'                 => ['Terkunci di escrow', '#f59e0b'],
        'disputed'             => ['Disengketakan',      '#ef4444'],
        'pending_confirmation' => ['Menunggu konfirmasi', '#cbd5e1'],
        'completed'            => ['Dilepas ke penjual', '#10b981'],
        'refunded'             => ['Dikembalikan',       '#94a3b8'],
    ];
    $items = collect($itemMeta)->map(fn ($m, $k) => [
        'label' => $m[0], 'color' => $m[1],
        'amount' => $stats['items'][$k]['amount'] ?? 0, 'count' => $stats['items'][$k]['count'] ?? 0,
    ]);
    $itemsTotal = max(1, $items->sum('amount'));
    $locked = ($stats['items']['paid']['amount'] ?? 0) + ($stats['items']['disputed']['amount'] ?? 0);

    $outMeta = [
        'auto_release' => ['Dilepas otomatis',   'bg-emerald-500'],
        'auto_refund'  => ['Refund otomatis',    'bg-slate-400'],
        'manual'       => ['Diputus pengawas',   'bg-violet-500'],
        'held'         => ['Ditahan untukmu',    'bg-indigo-500'],
        'pending'      => ['Escrow berjalan',    'bg-slate-200'],
    ];
    $outTotal = max(1, array_sum($stats['outcomes']));
    $decided = $stats['outcomes']['auto_release'] + $stats['outcomes']['auto_refund'];
    $autoShare = ($decided + $stats['outcomes']['manual'] + $stats['outcomes']['held']) > 0
        ? round($decided / ($decided + $stats['outcomes']['manual'] + $stats['outcomes']['held']) * 100) : null;

    $confTotal = array_sum($stats['confidence']);
    $confAbove = 0;
    foreach ($stats['confidence'] as $b => $n) { if ($b / 10 >= $cfg['min_conf'] - 1e-9) $confAbove += $n; }

    $todayPct = $cfg['daily_cap'] > 0 ? min(100, $stats['paid_today'] / $cfg['daily_cap'] * 100) : 0;

    // Status dompet operasional.
    $arb = $wallets['arbiter'];
    $arbState = !$arb['key'] ? ['Kunci belum dipasang', 'bad']
        : (!$arb['onchain'] ? ['Kontrak tak terbaca', 'warn']
        : ($arb['key'] === $arb['onchain'] ? ['Siap', 'ok'] : ['Tidak cocok dengan kontrak', 'bad']));
    $pool = $wallets['pool'];
    $poolState = !$pool['address'] ? ['Belum dikonfigurasi', 'bad']
        : (!$pool['key'] ? ['Kunci belum dipasang', 'bad']
        : ($pool['key'] !== $pool['address'] ? ['Kunci bukan milik pool', 'bad']
        : ($pool['tlkm'] === null ? ['Saldo tak terbaca', 'warn'] : ['Siap', 'ok'])));
    $gas = $wallets['gas'];
    $drips = ($gas['bnb'] !== null && $cfg['drip'] > 0) ? (int) floor((float) $gas['bnb'] / $cfg['drip']) : null;
    $gasState = !$gas['address'] ? ['Kunci belum dipasang', 'bad']
        : ($drips === null ? ['Saldo tak terbaca', 'warn']
        : ($drips < 5 ? ['Hampir habis', 'bad'] : ($drips < 15 ? ['Mulai menipis', 'warn'] : ['Siap', 'ok'])));
    $tone = ['ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'warn' => 'bg-amber-50 text-amber-700 ring-amber-200', 'bad' => 'bg-red-50 text-red-700 ring-red-200'];
    $dot  = ['ok' => 'bg-emerald-500', 'warn' => 'bg-amber-500', 'bad' => 'bg-red-500'];

    $recentMeta = [
        'released' => ['Dilepas',  'bg-emerald-50 text-emerald-700'],
        'refunded' => ['Refund',   'bg-slate-100 text-slate-600'],
        'held'     => ['Ditahan',  'bg-indigo-50 text-indigo-700'],
        'pending'  => ['Berjalan', 'bg-slate-50 text-slate-500'],
    ];
@endphp

@include('supervisor._nav')

{{-- ================= ANTRIAN: yang menunggu putusan ================= --}}
<section aria-labelledby="q-title" class="mb-8">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
        <div>
            <h2 id="q-title" class="text-lg font-bold text-slate-900">Menunggu putusanmu</h2>
            <p class="text-sm text-slate-500">
                @if($waiting === 0)
                    Antrian bersih — tidak ada yang perlu kamu putuskan sekarang.
                @else
                    <b class="text-slate-800">{{ $waiting }} hal</b> perlu ditinjau{{ $oldestAll ? ', yang terlama sudah menunggu' : '' }}
                    @if($oldestAll)<b class="text-slate-800">{{ $age($oldestAll) }}</b>@endif
                @endif
            </p>
        </div>
        <p class="text-xs text-slate-400 flex items-center gap-3">
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-300"></span>&lt; 1 hari</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span>1–2 hari</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>&gt; 2 hari</span>
        </p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">
        @foreach($queueMeta as $key => [$name, $explain, $href, $iconTone, $icon, $emptyText])
            @php $q = $queue[$key]; @endphp
            <div class="p-5 flex flex-col">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $iconTone }}">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900">{{ $name }}</h3>
                        <p class="text-xs text-slate-500 leading-snug">{{ $explain }}</p>
                    </div>
                </div>

                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tabular-nums {{ $q['count'] ? 'text-slate-900' : 'text-slate-300' }}">{{ $q['count'] }}</span>
                    @if($q['count'])
                        <span class="text-sm text-slate-500"><b class="text-slate-700 tabular-nums">{{ $fmt($q['amount']) }} TLKM</b> {{ $key === 'claims' ? 'potensi payout' : 'tertahan' }}</span>
                    @endif
                </div>

                @if($q['count'])
                    <ul class="mt-3 space-y-1.5 flex-1">
                        @foreach($q['oldest'] as $row)
                            <li class="flex items-center gap-2 text-xs">
                                <span class="shrink-0 px-1.5 py-0.5 rounded-md ring-1 font-medium tabular-nums {{ $ageTone($row['since']) }}">{{ $age($row['since']) }}</span>
                                <span class="truncate text-slate-600" title="{{ $row['ref'] }} — {{ $row['label'] }}">{{ $row['label'] ?: $row['ref'] }}</span>
                                @if($row['label'] && $row['ref'])<span class="shrink-0 font-mono text-[11px] text-slate-400">…{{ substr($row['ref'], -4) }}</span>@endif
                                <span class="ml-auto shrink-0 tabular-nums text-slate-500">{{ $fmt($row['tlkm']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ $href }}" class="mt-4 inline-flex items-center justify-center gap-1.5 w-full py-2 rounded-lg bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                        Tinjau {{ $q['count'] > 3 ? 'semua ' . $q['count'] : 'sekarang' }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"/></svg>
                    </a>
                @else
                    <p class="mt-3 flex-1 flex items-center gap-1.5 text-sm text-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $emptyText }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>
</section>

<h2 class="text-lg font-bold text-slate-900 mb-3">Kesehatan platform</h2>

{{-- ================= ESCROW & VOLUME ================= --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <section class="lg:col-span-2 min-w-0 bg-white border border-slate-200 rounded-2xl shadow-sm p-5" aria-labelledby="vol-title">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h3 id="vol-title" class="font-semibold text-slate-900">Volume transaksi</h3>
                <p class="text-xs text-slate-500">TLKM yang masuk escrow per hari, dan berapa order yang dibuat.</p>
            </div>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs" role="group" aria-label="Rentang waktu">
                <button type="button" data-range="14" class="range-btn px-3 py-1 rounded-md font-medium transition-colors">14 hari</button>
                <button type="button" data-range="30" class="range-btn px-3 py-1 rounded-md font-medium transition-colors">30 hari</button>
            </div>
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-1 mb-3 text-sm">
            <p><span class="text-slate-500">Total</span> <b id="volSum" class="text-slate-900 tabular-nums">—</b></p>
            <p><span class="text-slate-500">Order</span> <b id="cntSum" class="text-slate-900 tabular-nums">—</b></p>
            <p class="flex items-center gap-4 text-[11px] text-slate-500 ml-auto">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500"></span>Volume</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Order</span>
            </p>
        </div>
        <div class="relative h-60"><canvas id="chartVolume" aria-label="Grafik volume harian" role="img"></canvas></div>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex flex-col" aria-labelledby="esc-title">
        <h3 id="esc-title" class="font-semibold text-slate-900">Ke mana dananya</h3>
        <p class="text-xs text-slate-500">Semua dana item yang pernah dibayar, menurut statusnya sekarang.</p>

        <p class="mt-4 text-sm text-slate-500">Masih dikunci kontrak</p>
        <p class="text-2xl font-extrabold text-amber-600 tabular-nums">{{ $fmt($locked) }} <span class="text-base font-bold">TLKM</span></p>

        <div class="mt-4 flex h-3 rounded-full overflow-hidden bg-slate-100" role="img"
             aria-label="Proporsi dana per status">
            @foreach($items as $it)
                @if($it['amount'] > 0)
                    <span style="width: {{ $it['amount'] / $itemsTotal * 100 }}%; background: {{ $it['color'] }}" title="{{ $it['label'] }}: {{ $fmt($it['amount']) }} TLKM"></span>
                @endif
            @endforeach
        </div>
        <ul class="mt-4 space-y-2 text-xs">
            @foreach($items as $it)
                <li class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background: {{ $it['color'] }}"></span>
                    <span class="text-slate-600">{{ $it['label'] }}</span>
                    <span class="ml-auto tabular-nums text-slate-800 font-medium">{{ $fmt($it['amount']) }}</span>
                    <span class="w-14 text-right tabular-nums text-slate-400">{{ $it['count'] }} item</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>

{{-- ================= KEEPER AI & GARANSI ================= --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5" aria-labelledby="ai-title">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 id="ai-title" class="font-semibold text-slate-900">Keeper AI</h3>
                <p class="text-xs text-slate-500">Membaca tracking lalu melepas atau me-refund escrow sendiri bila yakin.</p>
            </div>
            @if($autoShare !== null)
                <div class="text-right shrink-0">
                    <p class="text-2xl font-extrabold text-slate-900 tabular-nums leading-none">{{ $autoShare }}%</p>
                    <p class="text-[11px] text-slate-500 mt-1">diputus tanpa pengawas</p>
                </div>
            @endif
        </div>

        <div class="mt-4 flex h-3 rounded-full overflow-hidden bg-slate-100" role="img" aria-label="Hasil keeper AI">
            @foreach($outMeta as $k => [$label, $bg])
                @if($stats['outcomes'][$k] > 0)
                    <span class="{{ $bg }}" style="width: {{ $stats['outcomes'][$k] / $outTotal * 100 }}%" title="{{ $label }}: {{ $stats['outcomes'][$k] }}"></span>
                @endif
            @endforeach
        </div>
        <ul class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-1.5 text-xs">
            @foreach($outMeta as $k => [$label, $bg])
                <li class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0 {{ $bg }}"></span>
                    <span class="text-slate-600 truncate">{{ $label }}</span>
                    <span class="ml-auto tabular-nums text-slate-800 font-medium">{{ $stats['outcomes'][$k] }}</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-5 pt-4 border-t border-slate-100">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h4 class="text-sm font-semibold text-slate-800">Seberapa yakin AI</h4>
                @if($confTotal)
                    <p class="text-xs text-slate-500"><b class="text-slate-800 tabular-nums">{{ round($confAbove / $confTotal * 100) }}%</b> keputusan melewati ambang {{ $cfg['min_conf'] }}</p>
                @endif
            </div>
            @if($confTotal)
                <div class="relative h-36 mt-2"><canvas id="chartConfidence" role="img" aria-label="Sebaran tingkat keyakinan AI"></canvas></div>
                <p class="text-[11px] text-slate-500 mt-2 flex flex-wrap gap-x-4 gap-y-1">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400"></span>Di bawah ambang → ditahan untukmu</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span>Cukup yakin → dieksekusi otomatis (maks {{ $fmt($cfg['max_auto']) }} TLKM)</span>
                </p>
            @else
                <p class="mt-2 text-sm text-slate-400">Belum ada keputusan AI yang tercatat. Grafik muncul setelah keeper memproses order pertama.</p>
            @endif
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5" aria-labelledby="ins-title">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 id="ins-title" class="font-semibold text-slate-900">Garansi Tepat Waktu</h3>
                <p class="text-xs text-slate-500">Ongkir diganti dari pool bila kurir terlambat melewati janji.</p>
            </div>
            <span class="shrink-0 inline-flex items-center gap-1.5 px-2 py-1 rounded-full ring-1 text-[11px] font-medium {{ $cfg['insurance'] ? $tone['ok'] : 'bg-slate-50 text-slate-500 ring-slate-200' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $cfg['insurance'] ? $dot['ok'] : 'bg-slate-400' }}"></span>{{ $cfg['insurance'] ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>

        <dl class="mt-4 grid grid-cols-3 gap-3">
            @foreach([['Menunggu', $stats['claims']['active'], 'text-amber-600'], ['Dibayar', $stats['claims']['paid'], 'text-emerald-600'], ['Ditolak', $stats['claims']['rejected'], 'text-slate-500']] as [$l, $v, $c])
                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                    <dt class="text-[11px] text-slate-500">{{ $l }}</dt>
                    <dd class="text-xl font-extrabold tabular-nums {{ $c }}">{{ $v }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-5">
            <div class="flex items-baseline justify-between text-sm">
                <span class="font-semibold text-slate-800">Payout hari ini</span>
                <span class="tabular-nums text-slate-600"><b class="text-slate-900">{{ $fmt($stats['paid_today']) }}</b> / {{ $fmt($cfg['daily_cap']) }} TLKM</span>
            </div>
            <div class="mt-2 h-2.5 rounded-full bg-slate-100 overflow-hidden" role="meter" aria-valuemin="0" aria-valuemax="{{ $cfg['daily_cap'] }}" aria-valuenow="{{ $stats['paid_today'] }}" aria-label="Payout hari ini terhadap batas harian">
                <div class="h-full rounded-full {{ $todayPct >= 90 ? 'bg-red-500' : ($todayPct >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ max($todayPct, $stats['paid_today'] > 0 ? 2 : 0) }}%"></div>
            </div>
            <p class="mt-1.5 text-[11px] text-slate-500">Bila batas harian tercapai, klaim berikutnya otomatis ditunda ke besok (circuit breaker).</p>
        </div>

        <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-slate-500 text-xs">Saldo pool</p>
                <p class="font-bold text-slate-900 tabular-nums">{{ $pool['tlkm'] !== null ? $fmt($pool['tlkm']) . ' TLKM' : 'Tak terbaca' }}</p>
                @if($pool['tlkm'] !== null && $cfg['claim_cap'] > 0)
                    <p class="text-[11px] text-slate-500">cukup untuk ≈ {{ (int) floor((float) $pool['tlkm'] / $cfg['claim_cap']) }} klaim maksimum</p>
                @endif
            </div>
            <div>
                <p class="text-slate-500 text-xs">Total sudah dibayar</p>
                <p class="font-bold text-slate-900 tabular-nums">{{ $fmt($stats['paid_total']) }} TLKM</p>
                <p class="text-[11px] text-slate-500">maks {{ $fmt($cfg['claim_cap']) }} TLKM per klaim</p>
            </div>
        </div>
    </section>
</div>

{{-- ================= DOMPET OPERASIONAL & AKTIVITAS ================= --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5" aria-labelledby="ops-title">
        <h3 id="ops-title" class="font-semibold text-slate-900">Dompet operasional</h3>
        <p class="text-xs text-slate-500">Wallet milik platform yang menandatangani transaksi otomatis. Bila salah satu bermasalah, fitur terkait berhenti.</p>

        <ul class="mt-4 divide-y divide-slate-100">
            @foreach([
                ['Arbiter escrow', 'Melepas / me-refund escrow atas nama keeper & pengawas.', $arb['key'], $arbState, null],
                ['Pool asuransi', 'Membayar klaim Garansi Tepat Waktu.', $pool['address'], $poolState, $pool['tlkm'] !== null ? $fmt($pool['tlkm']) . ' TLKM' : null],
                ['Gas platform', 'Mengisi tBNB ke wallet baru supaya bisa bertransaksi.', $gas['address'], $gasState, $gas['bnb'] !== null ? $fmt($gas['bnb']) . ' tBNB' : null],
            ] as [$name, $what, $addr, [$stateText, $state], $bal])
                <li class="py-3 first:pt-0 last:pb-0">
                    <div class="flex items-start gap-3">
                        <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $dot[$state] }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="font-medium text-slate-900">{{ $name }}</span>
                                <span class="px-1.5 py-0.5 rounded-md ring-1 text-[11px] font-medium {{ $tone[$state] }}">{{ $stateText }}</span>
                                @if($bal)<span class="ml-auto text-sm font-semibold tabular-nums text-slate-800">{{ $bal }}</span>@endif
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $what }}</p>
                            @if($addr)
                                <a href="{{ $explorer }}/address/{{ $addr }}" target="_blank" rel="noopener" class="text-[11px] font-mono text-slate-400 hover:text-blue-600">{{ $short($addr) }} ↗</a>
                            @endif
                        </div>
                    </div>
                    @if($name === 'Gas platform' && $drips !== null)
                        <div class="ml-5 mt-2">
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $dot[$state] }}" style="width: {{ min(100, $drips / 50 * 100) }}%"></div>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Cukup untuk ≈ <b class="text-slate-700">{{ $drips }} wallet baru</b> ({{ $fmt($cfg['drip']) }} tBNB per wallet). Isi ulang dari faucet testnet sebelum di bawah 15.</p>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="mt-4 text-[11px] text-slate-400">Dibaca dari blockchain {{ $wallets['read_at']->diffForHumans() }} · diperbarui tiap 5 menit.</p>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5" aria-labelledby="log-title">
        <h3 id="log-title" class="font-semibold text-slate-900">Keputusan terbaru</h3>
        <p class="text-xs text-slate-500">Alasan yang ditulis keeper AI atau pengawas pada setiap order.</p>

        @if($recent->isEmpty())
            <p class="mt-6 text-sm text-slate-400">Belum ada keputusan. Catatan muncul di sini begitu keeper memproses order pertama.</p>
        @else
            <ol class="mt-4 space-y-3">
                @foreach($recent as $o)
                    @php
                        $m = $recentMeta[$o->settlement_status] ?? [$o->settlement_status, 'bg-slate-50 text-slate-500'];
                        $manual = str_starts_with((string) $o->ai_reason, 'Diputus manual') || str_contains((string) $o->ai_reason, 'manual oleh pengawas');
                    @endphp
                    <li class="flex gap-3">
                        <span class="shrink-0 w-16 text-right text-[11px] text-slate-400 pt-0.5 tabular-nums">{{ $o->updated_at->diffForHumans(null, true) }}</span>
                        <div class="min-w-0 flex-1 pb-3 border-b border-slate-100">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="px-1.5 py-0.5 rounded-md text-[11px] font-medium {{ $m[1] }}">{{ $m[0] }}</span>
                                @if($o->is_insured && in_array($o->insurance_status, ['paid', 'rejected'], true))
                                    <span class="px-1.5 py-0.5 rounded-md text-[11px] font-medium {{ $o->insurance_status === 'paid' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500' }}">Klaim {{ $o->insurance_status === 'paid' ? 'dibayar' : 'ditolak' }}</span>
                                @endif
                                <span class="text-[11px] {{ $manual ? 'text-violet-600' : 'text-slate-400' }}">{{ $manual ? 'oleh pengawas' : 'oleh AI' }}</span>
                                <span class="ml-auto font-mono text-[11px] text-slate-400 truncate">{{ $o->order_id }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-700 line-clamp-2">{{ $o->ai_reason }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    var daily = @json($stats['daily']);
    var conf  = @json($stats['confidence']);
    var minConf = {{ $cfg['min_conf'] }};
    var money = function (n) { return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(n); };
    var tick = { color: '#94a3b8', font: { size: 10, family: 'Hanken Grotesk' } };

    // ---- Volume: rentang 14/30 hari, diingat per browser.
    var range = 14;
    try { range = +localStorage.getItem('sup:range') || 14; } catch (e) {}
    var volChart = null;

    function paint() {
        var rows = daily.slice(-range);
        document.getElementById('volSum').textContent = money(rows.reduce(function (a, d) { return a + d.volume; }, 0)) + ' TLKM';
        document.getElementById('cntSum').textContent = rows.reduce(function (a, d) { return a + d.count; }, 0);
        document.querySelectorAll('.range-btn').forEach(function (b) {
            var on = +b.dataset.range === range;
            b.setAttribute('aria-pressed', on);
            b.className = 'range-btn px-3 py-1 rounded-md font-medium transition-colors ' + (on ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800');
        });
        if (!volChart) return;
        volChart.data.labels = rows.map(function (d) { return d.date; });
        volChart.data.datasets[0].data = rows.map(function (d) { return d.volume; });
        volChart.data.datasets[1].data = rows.map(function (d) { return d.count; });
        volChart.update();
    }
    document.querySelectorAll('.range-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            range = +b.dataset.range;
            try { localStorage.setItem('sup:range', range); } catch (e) {}
            paint();
        });
    });

    if (typeof Chart !== 'undefined') {
        var vctx = document.getElementById('chartVolume');
        // Volume = batang (total per hari, tak ada nilai di antara hari); order = garis titik.
        volChart = new Chart(vctx, {
            data: { labels: [], datasets: [
                { type: 'bar', label: 'Volume', data: [], yAxisID: 'y', backgroundColor: '#3b82f6', borderRadius: 4, maxBarThickness: 18, order: 2 },
                { type: 'line', label: 'Order', data: [], yAxisID: 'y1', borderColor: '#10b981', backgroundColor: '#10b981',
                  borderWidth: 1.5, pointRadius: 2.5, pointHoverRadius: 4, tension: 0, order: 1 }
            ] },
            options: {
                responsive: true, maintainAspectRatio: false, animation: { duration: 200 },
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) {
                    return c.datasetIndex === 0 ? ' ' + money(c.parsed.y) + ' TLKM' : ' ' + c.parsed.y + ' order';
                } } } },
                scales: {
                    x:  { grid: { display: false }, ticks: Object.assign({ maxRotation: 0, autoSkipPadding: 12 }, tick) },
                    y:  { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: Object.assign({ callback: function (v) { return money(v); } }, tick) },
                    y1: { position: 'right', beginAtZero: true, grid: { display: false }, border: { display: false }, ticks: Object.assign({ precision: 0 }, tick) }
                }
            }
        });

        // ---- Keyakinan AI: kelompok di bawah ambang kuning, di atasnya hijau.
        var cctx = document.getElementById('chartConfidence');
        if (cctx) {
            new Chart(cctx, {
                type: 'bar',
                data: {
                    labels: conf.map(function (_, i) { return (i / 10).toFixed(1); }),
                    datasets: [{ data: conf, borderRadius: 3, maxBarThickness: 28,
                                 backgroundColor: conf.map(function (_, i) { return i / 10 >= minConf - 1e-9 ? '#10b981' : '#fbbf24'; }) }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, animation: { duration: 200 },
                    plugins: { legend: { display: false }, tooltip: { callbacks: {
                        title: function (c) { var i = c[0].dataIndex; return 'Keyakinan ' + (i / 10).toFixed(1) + '–' + ((i + 1) / 10).toFixed(1); },
                        label: function (c) { return ' ' + c.parsed.y + ' keputusan'; }
                    } } },
                    scales: {
                        x: { grid: { display: false }, ticks: tick },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: Object.assign({ precision: 0 }, tick) }
                    }
                }
            });
        }
    }
    paint();
})();
</script>
@endsection

@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<h1 class="text-2xl font-bold text-slate-900 mb-1">Dompet TLKM</h1>
<p class="text-sm text-slate-500 mb-6">Kirim TLKM, minta uang (link &amp; QR), atau <b>bayar QRIS pakai stablecoin</b>.</p>

{{-- ===== BAYAR QRIS (PROTOTIPE) ===== --}}
<div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl shadow-sm p-5 mb-6 flex items-center justify-between gap-4">
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            <h2 class="font-bold text-white">Bayar QRIS pakai Stablecoin</h2>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-400/90 text-slate-900 font-bold">PROTOTIPE</span>
        </div>
        <p class="text-xs text-slate-300 mt-1">Scan QRIS/GPN, masukkan nominal, konfirmasi PIN — dibayar dari saldo stablecoin (USDC). <b>Simulasi</b>: belum settlement nyata.</p>
    </div>
    <button onclick="qrisStart()" class="shrink-0 bg-white text-slate-900 hover:bg-slate-100 px-4 py-2.5 rounded-xl text-sm font-bold transition inline-flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 3h3m0 0h3m-3 0v3m0-3v-3"/></svg>
        Scan &amp; Bayar
    </button>
</div>

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
        <label class="block text-sm font-medium text-slate-700 mb-1.5">No HP atau wallet tujuan</label>
        <input id="sendTo" type="text" oninput="onSendToInput()" placeholder="08xxxx  atau  0x…" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-1.5">
        <p id="sendResolved" class="hidden text-xs mb-3 px-1"></p>
        <div class="mb-4"></div>
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

{{-- ===== PAYLATER (kredit berjaminan on-chain, DEMO) ===== --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </span>
        <h2 class="font-bold text-slate-900">{{ __('paylater.title') }}</h2>
        <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-semibold">DEMO · belum diaudit</span>
    </div>

    @if(!($paylater['configured'] ?? false))
        <p class="px-5 py-8 text-center text-sm text-slate-400">{{ __('paylater.not_configured') }}</p>
    @else
        <div class="p-5">
            <p class="text-xs text-slate-500 mb-4">{{ __('paylater.subtitle') }} <span class="text-amber-600">{{ __('paylater.interest_note', ['pct' => rtrim(rtrim(number_format($paylater['interest_bps']/100, 2), '0'), '.')]) }}</span></p>

            {{-- Ringkasan posisi (dibaca dari chain) --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                    <p class="text-[11px] text-slate-500">{{ __('paylater.collateral') }}</p>
                    <p class="text-base font-bold text-slate-900">{{ $paylater['collateral'] }} <span class="text-[11px] text-slate-400">tBNB</span></p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                    <p class="text-[11px] text-slate-500">{{ __('paylater.limit') }}</p>
                    <p class="text-base font-bold text-blue-600">{{ $paylater['limit'] }} <span class="text-[11px] text-slate-400">TLKM</span></p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                    <p class="text-[11px] text-slate-500">{{ __('paylater.due_amount') }}</p>
                    <p class="text-base font-bold {{ $paylater['has_debt'] ? 'text-red-600' : 'text-slate-900' }}">{{ $paylater['due_amount'] }} <span class="text-[11px] text-slate-400">TLKM</span></p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                    <p class="text-[11px] text-slate-500">{{ __('paylater.available') }}</p>
                    <p class="text-base font-bold text-green-600">{{ $paylater['available'] }} <span class="text-[11px] text-slate-400">TLKM</span></p>
                </div>
            </div>

            @php $poolLiq = (float) str_replace(',', '', $paylater['supply']['liquidity'] ?? '0'); @endphp
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2 text-xs text-slate-500">
                <span>Status kredit:
                    @if($poolLiq > 0)
                        <b class="text-green-600">● Aktif</b> <span class="text-slate-400">— pool didanai penyuplai</span>
                    @else
                        <b class="text-amber-600">○ Menunggu dana</b> <span class="text-slate-400">— belum ada penyuplai</span>
                    @endif
                </span>
                @if($paylater['due_date'])
                    <span>{{ __('paylater.due') }}: <b class="{{ $paylater['has_debt'] && $paylater['due_date']->isPast() ? 'text-red-600' : 'text-slate-700' }}">{{ $paylater['due_date']->format('d M Y H:i') }}</b>
                        @if($paylater['has_debt'])<span class="text-slate-400">({{ $paylater['due_date']->diffForHumans() }})</span>@endif
                    </span>
                @endif
            </div>

            {{-- Info model dua-sisi + alamat kontrak --}}
            @if($paylater['contract'])
                <div class="bg-teal-50 border border-teal-200 rounded-xl px-3 py-2 mb-4 text-[11px] text-teal-800 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span>ℹ <b>Lending dua-sisi</b>: pinjam dari dana <b>penyuplai</b> (agunan tBNB), bunga peminjam dibagi ke penyuplai sesuai nisbah. Kontrak:</span>
                    <code class="font-mono bg-white/70 px-1.5 py-0.5 rounded">{{ $paylater['contract'] }}</code>
                    <button type="button" onclick="plCopyContract()" class="underline hover:text-teal-900">salin</button>
                    <a href="{{ config('chain.explorer_url') }}/address/{{ $paylater['contract'] }}" target="_blank" rel="noopener" class="underline hover:text-teal-900">explorer ↗</a>
                </div>
            @endif

            {{-- Deposit agunan + estimasi --}}
            <div class="flex flex-col sm:flex-row gap-2 mb-1">
                <div class="relative flex-1">
                    <input id="plDepAmt" type="number" min="0" step="any" oninput="plEstLimit()" placeholder="0.00"
                        class="w-full px-4 py-2.5 pr-16 rounded-xl border border-slate-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none text-sm">
                    <span class="absolute right-4 top-2.5 text-sm text-slate-400 font-medium">tBNB</span>
                </div>
                <button onclick="doPaylaterDeposit()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.deposit_btn') }}</button>
            </div>
            <p id="plEst" class="text-xs text-slate-400 mb-4">{{ __('paylater.est_hint') }}</p>

            <div class="flex flex-wrap gap-2">
                <button onclick="doPaylaterBorrow()" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.borrow_btn') }}</button>
                <button onclick="doPaylaterRepayFull()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $paylater['has_debt'] ? '' : 'disabled' }}>{{ __('paylater.repay_btn') }}@if($paylater['has_debt']) <span class="text-slate-400">({{ $paylater['due_amount'] }})</span>@endif</button>
                <button onclick="doPaylaterWithdraw()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.withdraw_btn') }}</button>
            </div>

            {{-- ===== SISI PENYUPLAI (Danai / Earn) — deposit berjangka + bagi hasil ===== --}}
            @php $sup = $paylater['supply'] ?? []; $terms = $sup['terms'] ?? []; @endphp
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-7 h-7 rounded-lg bg-green-50 text-green-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <h3 class="font-bold text-slate-900 text-sm">Danai Pool <span class="font-normal text-slate-400">— bagi hasil dari bunga peminjam</span></h3>
                </div>
                <p class="text-xs text-slate-500 mb-3">Setor TLKM &amp; pilih jangka. Makin lama dikunci, makin besar <b>nisbah bagi hasil</b>-mu dari bunga peminjam ({{ rtrim(rtrim(number_format($paylater['interest_bps']/100, 2), '0'), '.') }}% per pinjaman).</p>

                {{-- Statistik pool --}}
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">Likuiditas pool</p>
                        <p class="text-sm font-bold text-slate-900">{{ $sup['liquidity'] ?? '0' }} <span class="text-[10px] text-slate-400">TLKM</span></p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">Utilisasi</p>
                        <p class="text-sm font-bold text-slate-900">{{ $sup['util'] !== null ? $sup['util'].'%' : '—' }}</p>
                        <p class="text-[10px] text-slate-400">dipinjam {{ $sup['borrows'] ?? '0' }}</p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">Pendapatan platform</p>
                        <p class="text-sm font-bold text-slate-900">{{ $sup['reserve'] ?? '0' }} <span class="text-[10px] text-slate-400">TLKM</span></p>
                    </div>
                </div>

                {{-- Kartu per jangka: nisbah + posisiku + tarik --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    @foreach($terms as $t => $tm)
                        <div class="border border-slate-200 rounded-xl p-3 flex flex-col">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-bold text-slate-900">{{ $tm['label'] }}</p>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200 font-semibold">bagi hasil {{ $tm['nisbah'] ?? '—' }}%</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mb-2">{{ $t == 0 ? 'Tarik kapan saja' : 'Dikunci '.$tm['lock_days'].' hari' }}</p>
                            @if($tm['has_pos'])
                                <div class="text-xs text-slate-600 space-y-0.5 mb-2">
                                    <div class="flex justify-between"><span>Danaku</span><b class="text-green-700">{{ $tm['value'] }} TLKM</b></div>
                                    <div class="flex justify-between"><span>Untung</span><b class="text-emerald-600">+{{ $tm['earned'] }}</b></div>
                                    @if($tm['maturity'])
                                        <div class="flex justify-between"><span>Jatuh tempo</span><b class="{{ $tm['matured'] ? 'text-green-600' : 'text-slate-700' }}">{{ $tm['matured'] ? 'Sudah' : $tm['maturity']->diffForHumans() }}</b></div>
                                    @endif
                                </div>
                                <button onclick="doPaylaterWithdrawSupply({{ $t }})" class="mt-auto text-xs bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-3 py-2 rounded-lg font-semibold transition">
                                    {{ $t == 0 || $tm['matured'] ? 'Tarik dana' : 'Tarik (pokok saja)' }}
                                </button>
                            @else
                                <p class="mt-auto text-[11px] text-slate-400">Belum ada dana di jangka ini.</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Form danai: jumlah + pilih jangka --}}
                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1">
                        <input id="plSupAmt" type="number" min="0" step="any" placeholder="0.00"
                            class="w-full px-4 py-2.5 pr-16 rounded-xl border border-slate-300 focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none text-sm">
                        <span class="absolute right-4 top-2.5 text-sm text-slate-400 font-medium">TLKM</span>
                    </div>
                    <select id="plSupTerm" class="px-3 py-2.5 rounded-xl border border-slate-300 focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none text-sm bg-white">
                        @foreach($terms as $t => $tm)
                            <option value="{{ $t }}">{{ $tm['label'] }} · {{ $tm['nisbah'] ?? '—' }}%</option>
                        @endforeach
                    </select>
                    <button onclick="doPaylaterSupply()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition">Danai</button>
                </div>
            </div>
        </div>

        {{-- Riwayat paylater --}}
        @if($paylaterHistory->isNotEmpty())
            @php $plLabels = ['deposit'=>__('paylater.act_deposit'),'borrow'=>__('paylater.act_borrow'),'repay'=>__('paylater.act_repay'),'withdraw'=>__('paylater.act_withdraw'),'seize'=>__('paylater.act_seize'),'supply'=>__('paylater.act_supply'),'withdraw_supply'=>__('paylater.act_withdraw_supply')];
                  $plUnits  = ['deposit'=>'tBNB','borrow'=>'TLKM','repay'=>'TLKM','withdraw'=>'tBNB','seize'=>'tBNB','supply'=>'TLKM','withdraw_supply'=>'TLKM']; @endphp
            <div class="border-t border-slate-100">
                @foreach($paylaterHistory as $h)
                    <div class="px-5 py-2.5 border-t border-slate-50 flex items-center justify-between gap-3 first:border-t-0">
                        @php $plAmt = in_array($h->action, ['deposit','withdraw','seize']) ? rtrim(rtrim(number_format((float) $h->amount, 6), '0'), '.') : $fmt($h->amount); @endphp
                        <p class="text-sm text-slate-700">{{ $plLabels[$h->action] ?? $h->action }} <b>{{ $plAmt }} {{ $plUnits[$h->action] ?? '' }}</b> <span class="text-[11px] text-slate-400">· {{ $h->created_at->diffForHumans() }}</span></p>
                        <a href="{{ config('chain.explorer_url') }}/tx/{{ $h->tx_hash }}" target="_blank" rel="noopener" class="text-[11px] text-blue-600 hover:underline shrink-0">tx ↗</a>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
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
                        <a href="{{ config('chain.explorer_url') }}/tx/{{ $t['tx'] }}" target="_blank" rel="noopener" class="text-[11px] text-slate-400 hover:text-blue-600 font-mono">{{ $t['at']->format('d M H:i') }} ↗</a>
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
// ===== PAYLATER (aksi di halaman Wallet) =====
const PL_INTEREST_BPS = (typeof PAYLATER_INTEREST_BPS !== 'undefined') ? PAYLATER_INTEREST_BPS : 300;
const PL_DUE_RAW = @json($paylater['due_amount_raw'] ?? '0'); // kewajiban eksak (TLKM)
const PL_HAS_DEBT = @json($paylater['has_debt'] ?? false);
const PL_CONTRACT = @json($paylater['contract'] ?? null);
@php
    $plSupplyTerms = collect($paylater['supply']['terms'] ?? [])
        ->map(fn ($tm) => ['shares_raw' => $tm['shares_raw'], 'value' => $tm['value'], 'matured' => $tm['matured'], 'label' => $tm['label']])
        ->values();
@endphp
// Posisi penyuplai per jangka (index = term 0/1/2): {shares_raw, value, matured, label}
const PL_SUPPLY_TERMS = @json($plSupplyTerms);
function plCopyContract() {
    if (!PL_CONTRACT) return;
    navigator.clipboard?.writeText(PL_CONTRACT).then(() => showToast('Alamat kontrak Paylater disalin.', 'success')).catch(() => showToast('Gagal menyalin.', 'warn'));
}

function plEstLimit() {
    const el = document.getElementById('plDepAmt'); if (!el) return;
    const v = parseFloat(el.value) || 0;
    const est = v * PAYLATER_RATE_TLKM_PER_BNB;
    document.getElementById('plEst').textContent = v > 0 ? `≈ ${est.toLocaleString('id-ID')} TLKM limit` : @json(__('paylater.est_hint'));
}
function plOk(hash) { txProgress.close(); uiAlert({ title: 'Berhasil', message: hash ? `<a href="{{ config('chain.explorer_url') }}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>` : 'Tersimpan.', type: 'success' }).then(() => location.reload()); }
function plFail(e) { txProgress.close(); uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' }); }

async function doPaylaterDeposit() {
    let amt = document.getElementById('plDepAmt')?.value;
    if (!amt || +amt <= 0) amt = await uiPrompt({ title: @json(__('paylater.deposit_btn')), label: 'Jumlah tBNB agunan:', type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: 'Lanjut' });
    if (amt === null || +amt <= 0) return;
    txProgress.open('Deposit agunan', ['Menandatangani', 'Mencatat']);
    try { txProgress.active(0); const h = await depositCollateralPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('deposit', amt, h); plOk(h); } catch (e) { plFail(e); }
}
async function doPaylaterBorrow() {
    const amt = await uiPrompt({ title: @json(__('paylater.borrow_btn')), label: 'Jumlah TLKM yang dipinjam (≤ sisa limit):', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Lanjut' });
    if (amt === null || +amt <= 0) return;
    const due = (+amt) * (10000 + PL_INTEREST_BPS) / 10000;
    const ok = await uiConfirm({ title: @json(__('paylater.borrow_btn')), message: `Pinjam <b>${(+amt).toLocaleString('id-ID')} TLKM</b>. Wajib bayar <b>${due.toLocaleString('id-ID')} TLKM</b> (bunga ${PL_INTEREST_BPS / 100}%) sebelum tenggat.`, confirmText: 'Ya, pinjam' });
    if (!ok) return;
    txProgress.open('Pinjam TLKM', ['Menandatangani', 'Mencatat']);
    try { txProgress.active(0); const h = await borrowPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('borrow', amt, h); plOk(h); } catch (e) { plFail(e); }
}
// Lunasi PENUH (tanpa input) — bayar seluruh kewajiban (pokok + bunga) sekaligus.
async function doPaylaterRepayFull() {
    if (!PL_HAS_DEBT || parseFloat(PL_DUE_RAW) <= 0) {
        uiAlert({ title: 'Tidak ada kewajiban', message: 'Kamu belum punya utang paylater untuk dilunasi.', type: 'info' });
        return;
    }
    const shown = (parseFloat(PL_DUE_RAW) || 0).toLocaleString('en-US', { maximumFractionDigits: 6 });
    const ok = await uiConfirm({ title: @json(__('paylater.repay_btn')), message: `Lunasi seluruh kewajiban <b>${shown} TLKM</b> (pokok + bunga) sekaligus?`, confirmText: 'Ya, lunasi' });
    if (!ok) return;
    txProgress.open('Melunasi', ['Approve TLKM', 'Mencatat']);
    try { txProgress.active(0); const h = await repayPaylater(PL_DUE_RAW); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('repay', PL_DUE_RAW, h); plOk(h); } catch (e) { plFail(e); }
}
async function doPaylaterWithdraw() {
    const amt = await uiPrompt({ title: @json(__('paylater.withdraw_btn')), label: 'Jumlah tBNB agunan yang ditarik (kewajiban harus 0):', type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: 'Tarik' });
    if (amt === null || +amt <= 0) return;
    txProgress.open('Tarik agunan', ['Menandatangani', 'Mencatat']);
    try { txProgress.active(0); const h = await withdrawCollateralPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('withdraw', amt, h); plOk(h); } catch (e) { plFail(e); }
}

// ===== Sisi PENYUPLAI (Danai / Earn) — dengan jangka =====
async function doPaylaterSupply() {
    let amt = document.getElementById('plSupAmt')?.value;
    const term = parseInt(document.getElementById('plSupTerm')?.value ?? '0', 10) || 0;
    if (!amt || +amt <= 0) amt = await uiPrompt({ title: 'Danai Pool', label: 'Jumlah TLKM yang didanai:', type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: 'Lanjut' });
    if (amt === null || +amt <= 0) return;
    const info = PL_SUPPLY_TERMS[term] || {};
    const lockNote = term == 0 ? 'Bisa ditarik kapan saja.' : `Dana dikunci hingga jatuh tempo. Tarik lebih awal = <b>pokok saja</b> (bagi hasil hangus).`;
    const ok = await uiConfirm({ title: 'Danai — ' + (info.label || 'Pool'), message: `Setor <b>${(+amt).toLocaleString('id-ID')} TLKM</b> ke jangka <b>${info.label || ''}</b>. ${lockNote}`, confirmText: 'Ya, danai' });
    if (!ok) return;
    txProgress.open('Danai pool', ['Approve TLKM', 'Mencatat']);
    try { txProgress.active(0); const h = await supplyPaylater(term, amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('supply', amt, h); plOk(h); } catch (e) { plFail(e); }
}
// Tarik SELURUH dana penyuplai pada satu jangka (semua share) — pokok + yield (atau pokok saja bila belum jatuh tempo).
async function doPaylaterWithdrawSupply(term) {
    const info = PL_SUPPLY_TERMS[term] || {};
    if (!info.shares_raw || info.shares_raw === '0') {
        uiAlert({ title: 'Belum mendanai', message: 'Kamu belum punya dana di jangka ini.', type: 'info' });
        return;
    }
    const early = !info.matured;
    const msg = early
        ? `Jangka <b>${info.label}</b> belum jatuh tempo. Tarik sekarang hanya mengembalikan <b>pokok</b> (bagi hasil hangus). Lanjut?`
        : `Tarik seluruh dana <b>${info.label}</b> (± <b>${(parseFloat(info.value)||0).toLocaleString('en-US', { maximumFractionDigits: 4 })} TLKM</b>, termasuk bagi hasil)?<br><span class="text-xs text-slate-400">Gagal bila likuiditas pool sedang dipinjam habis.</span>`;
    const ok = await uiConfirm({ title: 'Tarik dana', message: msg, confirmText: early ? 'Ya, tarik pokok' : 'Ya, tarik' });
    if (!ok) return;
    txProgress.open('Tarik dana', ['Menandatangani', 'Mencatat']);
    try { txProgress.active(0); const h = await withdrawSupplyPaylater(term, info.shares_raw); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('withdraw_supply', info.value, h); plOk(h); } catch (e) { plFail(e); }
}

// Cari penerima via No HP / wallet → tampilkan namanya.
let _lookupTimer = null;
async function lookupRecipient(q) {
    const res = await fetch('/wallet/lookup', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ q }) });
    return await res.json().catch(() => ({ found: false }));
}
function onSendToInput() {
    const el = document.getElementById('sendResolved');
    const q = (document.getElementById('sendTo').value || '').trim();
    el.classList.add('hidden');
    if (q.length < 5) return;
    clearTimeout(_lookupTimer);
    _lookupTimer = setTimeout(async () => {
        const r = await lookupRecipient(q);
        if (r.self) { el.textContent = 'Itu wallet kamu sendiri.'; el.className = 'text-xs mb-3 px-1 text-amber-600'; }
        else if (r.found && r.name) { el.innerHTML = '→ <b class="text-slate-700">' + r.name + '</b> <span class="font-mono text-slate-400">' + r.wallet.slice(0,6) + '…' + r.wallet.slice(-4) + '</span>'; el.className = 'text-xs mb-3 px-1 text-green-600'; }
        else if (r.found) { el.textContent = '→ Wallet valid (bukan pengguna terdaftar)'; el.className = 'text-xs mb-3 px-1 text-slate-500'; }
        else { el.textContent = 'Penerima tidak ditemukan.'; el.className = 'text-xs mb-3 px-1 text-red-500'; }
        el.classList.remove('hidden');
    }, 400);
}

async function doSend() {
    const input = (document.getElementById('sendTo').value || '').trim();
    const amt = parseFloat(document.getElementById('sendAmount').value);
    const note = (document.getElementById('sendNote').value || '').trim();
    if (!amt || amt <= 0) { showToast('Masukkan nominal yang valid.', 'warn'); return; }

    // Resolusi penerima: No HP → wallet+nama; atau langsung 0x.
    let to = input, name = null;
    if (!/^0x[a-fA-F0-9]{40}$/.test(input)) {
        const r = await lookupRecipient(input);
        if (r.self) { showToast('Tidak bisa kirim ke wallet sendiri.', 'warn'); return; }
        if (!r.found || !r.wallet) { showToast('Penerima (No HP/wallet) tidak ditemukan.', 'warn'); return; }
        to = r.wallet; name = r.name;
    } else {
        const r = await lookupRecipient(input); if (r.found) name = r.name;
    }

    const who = name ? `<b class="text-slate-900">${name}</b><br><span class="font-mono text-xs break-all text-slate-400">${to}</span>` : `<span class="font-mono text-xs break-all">${to}</span>`;
    const ok = await uiConfirm({ title: 'Kirim TLKM', message: `Kirim <b class="text-blue-600">${amt} TLKM</b> ke:<br>${who}`, confirmText: 'Ya, kirim' });
    if (!ok) return;
    let pin = null;
    if (IS_EMBEDDED) { pin = await askPin('Kirim TLKM'); if (!pin) return; }
    const btn = document.getElementById('sendBtn'); btn.disabled = true;
    txProgress.open('Kirim TLKM', ['Memeriksa jaringan', IS_EMBEDDED ? 'Tanda tangan dengan PIN' : 'Konfirmasi di MetaMask', 'Mencatat']);
    try {
        txProgress.active(0); if (!IS_EMBEDDED) await checkNetwork(); txProgress.done(0);
        txProgress.active(1, IS_EMBEDDED ? 'Menandatangani & menyiarkan…' : 'Konfirmasi transfer di MetaMask…');
        const hash = IS_EMBEDDED ? await pinTx('/pin/transfer', { pin, to, amount: amt }) : await sendTLKM(to, amt);
        txProgress.done(1);
        txProgress.active(2, 'Verifikasi on-chain…');
        await fetch('/wallet/send', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ tx_hash: hash, note }) });
        txProgress.done(2);
        setTimeout(() => { txProgress.close(); uiAlert({ title: 'TLKM Terkirim', message: `${amt} TLKM terkirim.<br><a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>`, type: 'success' }).then(() => location.reload()); }, 400);
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

{{-- ===== BAYAR QRIS (PROTOTIPE) — scan → tinjau → PIN → receipt simulasi ===== --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const QRIS_RATE = 16000; // Rp per USDC (mock, harus sama dgn QrisController)
let qrScanner = null, qrisMerchant = '', qrisCity = '';

function stopScanner() { if (qrScanner) { try { qrScanner.stop().catch(()=>{}); } catch(e){} qrScanner = null; } }

function qrisStart() {
    openModal(`
        <div class="p-5">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Scan QRIS / GPN</h3>
            <p class="text-xs text-slate-500 mb-3">Arahkan kamera ke kode QRIS, unggah gambar/screenshot QR, tempel kodenya, atau pakai contoh demo.</p>
            <div id="qrReader" class="rounded-xl overflow-hidden bg-slate-100 mb-3" style="min-height:200px"></div>
            <div id="qrFileReader" class="hidden"></div>
            <input type="file" id="qrFile" accept="image/*" class="hidden" onchange="qrisFromImage(this)">
            <button onclick="qrisPickImage()" class="w-full mb-2 py-2.5 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-slate-600 hover:border-blue-500 hover:text-blue-600 text-sm font-medium transition inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Unggah gambar QRIS
            </button>
            <textarea id="qrPaste" rows="2" placeholder="…atau tempel payload QRIS di sini" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs outline-none focus:border-blue-500 resize-none mb-2"></textarea>
            <div class="flex gap-2">
                <button onclick="qrisUseText()" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold">Gunakan kode</button>
                <button onclick="qrisDemo()" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Pakai contoh</button>
            </div>
            <button onclick="stopScanner();closeModal()" class="mt-2 w-full py-2 text-slate-500 text-sm">Batal</button>
        </div>`);
    setTimeout(() => {
        try {
            qrScanner = new Html5Qrcode('qrReader');
            qrScanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 200 },
                (txt) => { stopScanner(); qrisFromRaw(txt); }, () => {});
        } catch (e) { document.getElementById('qrReader').innerHTML = '<p class="text-xs text-slate-400 p-4 text-center">Kamera tak tersedia — tempel kode atau pakai contoh.</p>'; }
    }, 80);
}
function qrisUseText() { const v = (document.getElementById('qrPaste').value || '').trim(); if (!v) return showToast('Tempel kode QRIS dulu.', 'warn'); stopScanner(); qrisFromRaw(v); }
function qrisPickImage() { const f = document.getElementById('qrFile'); if (f) f.click(); }
async function qrisFromImage(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    stopScanner(); // lepas kamera dulu agar tak bentrok dengan decode file
    try {
        const fileScanner = new Html5Qrcode('qrFileReader');
        const txt = await fileScanner.scanFile(file, false);
        try { await fileScanner.clear(); } catch (e) {}
        qrisFromRaw(txt);
    } catch (e) {
        showToast('QR tidak terbaca dari gambar. Pastikan kode QRIS jelas & tidak terpotong.', 'warn');
    } finally { input.value = ''; }
}
function qrisDemo() { stopScanner(); qrisReview('WARUNG MADURA BAROKAH', 'JAKARTA'); }
function qrisFromRaw(raw) { const p = parseQris(raw); qrisReview(p.merchant || 'Merchant QRIS', p.city || ''); }

// Parser EMVCo QRIS sederhana (TLV): tag 59 = nama merchant, 60 = kota.
function parseQris(s) {
    const out = {}; let i = 0;
    try { while (i < s.length - 4) { const tag = s.substr(i, 2); const len = parseInt(s.substr(i + 2, 2), 10); if (isNaN(len)) break; out[tag] = s.substr(i + 4, len); i += 4 + len; } } catch (e) {}
    return { merchant: out['59'], city: out['60'] };
}

// Layar "Tinjau order" (mengikuti pola Bitget Pay).
function qrisReview(merchant, city) {
    qrisMerchant = merchant; qrisCity = city || '';
    openModal(`
        <div class="p-5">
            <p class="text-xs text-slate-400">Tinjau order</p>
            <div class="flex items-baseline gap-2 mt-1 mb-4">
                <input id="qrisAmt" type="number" min="1" placeholder="0" class="text-3xl font-extrabold w-44 outline-none border-b border-slate-200 focus:border-blue-500">
                <span class="text-lg text-slate-400 font-semibold">IDR</span>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm space-y-2.5">
                <div class="flex justify-between gap-3"><span class="text-slate-500">Bayar ke</span><span class="font-semibold text-slate-800 text-right truncate">${merchant}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Jumlah pembayaran</span><span id="qrisUsdc" class="font-semibold text-blue-600">0 USDC</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Saldo</span><span class="text-slate-700">52.272277 USDC</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Biaya jaringan</span><span class="text-green-600 font-medium">Gratis</span></div>
            </div>
            <p class="text-[11px] text-amber-600 mt-3">Prototipe — pembayaran ini <b>simulasi</b>, belum ada settlement nyata ke merchant.</p>
            <button onclick="qrisConfirm()" class="mt-4 w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold">Konfirmasi pembayaran</button>
            <button onclick="closeModal()" class="mt-2 w-full py-2 text-slate-500 text-sm">Batal</button>
        </div>`);
    const amt = document.getElementById('qrisAmt');
    amt.oninput = () => { const v = parseFloat(amt.value) || 0; document.getElementById('qrisUsdc').textContent = (v / QRIS_RATE).toFixed(4) + ' USDC'; };
    setTimeout(() => amt.focus(), 60);
}

async function qrisConfirm() {
    const amt = parseFloat(document.getElementById('qrisAmt').value);
    if (!amt || amt < 1) return showToast('Masukkan nominal (IDR).', 'warn');
    const pin = await askPin('Bayar QRIS'); if (!pin) return;
    txProgress.open('Membayar QRIS', ['Verifikasi PIN', 'Memproses (simulasi)']);
    try {
        txProgress.active(0);
        const res = await fetch('/qris/pay', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ pin, merchant: qrisMerchant, city: qrisCity, amount: amt }) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || 'Pembayaran gagal.');
        txProgress.done(0); txProgress.active(1); txProgress.done(1);
        setTimeout(() => { txProgress.close(); qrisReceipt(data.receipt); }, 300);
    } catch (e) { txProgress.close(); uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' }); }
}

function qrisReceipt(r) {
    openModal(`
        <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Pembayaran Berhasil</h3>
            <span class="inline-block text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-bold mt-1">SIMULASI · PROTOTIPE</span>
            <p class="text-3xl font-extrabold text-slate-900 mt-3">Rp ${Number(r.amount_idr).toLocaleString('id-ID')}</p>
            <p class="text-sm text-slate-500">${r.stablecoin_amount} ${r.stablecoin} · kurs Rp${Number(r.rate).toLocaleString('id-ID')}</p>
            <div class="text-left text-sm bg-slate-50 border border-slate-200 rounded-xl p-3 mt-4 space-y-1.5">
                <div class="flex justify-between gap-3"><span class="text-slate-500">Merchant</span><span class="font-medium text-right truncate">${r.merchant}</span></div>
                ${r.city ? `<div class="flex justify-between"><span class="text-slate-500">Kota</span><span>${r.city}</span></div>` : ''}
                <div class="flex justify-between"><span class="text-slate-500">Waktu</span><span>${r.time}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">No. Ref</span><span class="font-mono text-xs">${r.ref}</span></div>
            </div>
            <p class="text-[11px] text-amber-600 mt-3">Receipt simulasi untuk prototipe — <b>bukan</b> bukti pembayaran nyata.</p>
            <button onclick="closeModal()" class="mt-4 w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Selesai</button>
        </div>`);
}
</script>
@endsection

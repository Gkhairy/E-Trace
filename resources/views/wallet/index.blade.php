@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<h1 class="text-2xl font-bold text-slate-900 mb-1">{{ __('Dompet TLKM') }}</h1>
<p class="text-sm text-slate-500 mb-6">{{ __('Kirim TLKM, minta uang (link & QR), atau') }} <b>{{ __('bayar QRIS pakai stablecoin') }}</b>.</p>

{{-- ===== SALDO ===== --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 sm:p-6 mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-5">
    <div class="min-w-0">
        <p class="text-sm font-medium text-slate-500">{{ __('Saldo TLKM') }}</p>
        @if($balance !== null)
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ $fmt($balance) }}</span>
                <span class="text-base font-bold text-blue-600">TLKM</span>
            </p>
        @else
            <p class="mt-1 text-4xl font-extrabold text-slate-300">—</p>
            <p class="text-xs text-slate-500 mt-1">{{ __('Saldo belum bisa dibaca dari blockchain. Muat ulang halaman sebentar lagi.') }}</p>
        @endif
        @if($wallet)
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span class="font-mono text-slate-600 bg-slate-100 border border-slate-200 rounded-lg px-2.5 py-1">{{ substr($wallet, 0, 6) }}…{{ substr($wallet, -4) }}</span>
                <button type="button" onclick="navigator.clipboard.writeText(@js($wallet)).then(() => showToast(@js(__('Alamat wallet disalin')), 'success'))" class="inline-flex items-center gap-1 text-slate-600 hover:text-blue-600 font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>{{ __('Salin') }}
                </button>
                <a href="/explorer/{{ $wallet }}" class="inline-flex items-center gap-1 text-slate-600 hover:text-blue-600 font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 3h6v6M10 14L21 3M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/></svg>{{ __('Riwayat on-chain') }}
                </a>
            </div>
        @endif
    </div>
    <div class="grid grid-cols-2 gap-3 sm:w-80 shrink-0">
        <div class="rounded-xl bg-green-50 border border-green-100 px-4 py-3">
            <p class="text-[11px] font-medium text-green-700">{{ __('Masuk · 30 hari') }}</p>
            <p class="text-lg font-bold text-green-700 tabular-nums mt-0.5">+{{ $fmt($flow['in']) }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
            <p class="text-[11px] font-medium text-slate-500">{{ __('Keluar · 30 hari') }}</p>
            <p class="text-lg font-bold text-slate-700 tabular-nums mt-0.5">−{{ $fmt($flow['out']) }}</p>
        </div>
    </div>
</div>

{{-- ===== BAYAR QRIS (PROTOTIPE) ===== --}}
<div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl shadow-sm p-5 mb-6 flex items-center justify-between gap-4">
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            <h2 class="font-bold text-white">{{ __('Bayar QRIS pakai Stablecoin') }}</h2>
        </div>
        <p class="text-xs text-slate-300 mt-1">{{ __('Scan QRIS/GPN, masukkan nominal, konfirmasi PIN — dibayar dari saldo stablecoin (USDC).') }} <b>{{ __('Simulasi') }}</b>{{ __(': belum settlement nyata.') }}</p>
    </div>
    <button onclick="qrisStart()" class="shrink-0 bg-white text-slate-900 hover:bg-slate-100 px-4 py-2.5 rounded-xl text-sm font-bold transition inline-flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 3h3m0 0h3m-3 0v3m0-3v-3"/></svg>
        {{ __('Scan & Bayar') }}
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
        <h2 class="font-bold text-slate-900 mb-4">{{ __('Kirim TLKM') }}</h2>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('No HP atau wallet tujuan') }}</label>
        <input id="sendTo" type="text" oninput="onSendToInput()" placeholder="{{ __('08xxxx  atau  0x…') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-1.5">
        <p id="sendResolved" class="hidden text-xs mb-3 px-1"></p>
        <div class="mb-4"></div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('Nominal (TLKM)') }}</label>
        <input id="sendAmount" type="number" min="0" step="any" placeholder="{{ __('mis. 100') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('Catatan (opsional)') }}</label>
        <input id="sendNote" type="text" maxlength="120" placeholder="{{ __('mis. bayar patungan') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-4">
        <button id="sendBtn" onclick="doSend()" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">{{ __('Kirim') }}</button>
    </div>

    {{-- MINTA UANG --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">{{ __('Minta Uang') }}</h2>
        <form action="/wallet/requests" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('Nominal (opsional)') }}</label>
                <input name="amount" type="number" min="0" step="any" placeholder="{{ __('Kosongkan untuk bebas') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('Catatan (opsional)') }}</label>
                <input name="note" type="text" maxlength="120" placeholder="{{ __('mis. iuran kelas') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
            </div>
            <button class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition">{{ __('Buat link permintaan') }}</button>
        </form>

        @if($requests->isNotEmpty())
            <div class="mt-5 pt-4 border-t border-slate-100 space-y-2">
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Link permintaan aktif') }}</p>
                @foreach($requests as $r)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <span class="font-semibold text-slate-800">{{ $r->amount ? $fmt($r->amount).' TLKM' : __('Nominal bebas') }}</span>
                            @if($r->note)<span class="text-slate-400 text-xs">· {{ $r->note }}</span>@endif
                        </div>
                        <button onclick="shareReq(@js($r->code), @js($r->amount ? $fmt($r->amount).' TLKM' : __('bebas')))" class="shrink-0 text-xs text-blue-600 hover:underline font-medium">{{ __('Bagikan / QR') }}</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ===== LENDING DESK (kredit berjaminan on-chain, DEMO) ===== --}}
@php
    $poolLiq = (float) str_replace(',', '', $paylater['supply']['liquidity'] ?? '0');
    $sup = $paylater['supply'] ?? []; $terms = $sup['terms'] ?? [];
    $intPct = rtrim(rtrim(number_format($paylater['interest_bps']/100, 2), '0'), '.');
@endphp
<section class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden mt-6">
    {{-- Header --}}
    <div class="px-5 sm:px-7 py-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-slate-100">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-gradient-to-br from-amber-50 to-green-50 border border-slate-200">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="text-lg font-extrabold text-slate-900 leading-tight">Lending Desk</h2>
                <p class="text-xs text-slate-500">{{ __('Pinjam TLKM dengan kolateral tBNB, atau supply likuiditas & panen bagi hasil.') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 ml-auto">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>BNB Chain Testnet
            </span>
            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">DEMO · unaudited</span>
        </div>
    </div>

    @if(!($paylater['configured'] ?? false))
        <p class="px-6 py-10 text-center text-sm text-slate-400">{{ __('paylater.not_configured') }}</p>
    @else
        <div class="grid lg:grid-cols-2">
            {{-- ============ BORROW ============ --}}
            <div class="p-5 sm:p-7">
                <div class="flex items-center justify-between mb-4">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-900"><span class="w-2 h-2 rounded-full bg-amber-500"></span>{{ __('Pinjam') }} @if(app()->getLocale() !== 'en')<span class="font-medium text-slate-400 text-xs">· Borrow</span>@endif</span>
                    @if($poolLiq > 0)
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-green-600"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>{{ __('Pool aktif') }}</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-600"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>{{ __('Pool kosong') }}</span>
                    @endif
                </div>

                {{-- Fokus: sisa limit --}}
                <p class="text-xs text-slate-500 mb-1">{{ __('paylater.available') }} — {{ __('siap dipinjam') }}</p>
                <p class="ld-num text-4xl font-extrabold text-slate-900 mb-4">{{ $paylater['available'] }} <span class="text-base font-bold text-slate-400">TLKM</span></p>

                {{-- Ringkasan posisi --}}
                <div class="grid grid-cols-3 gap-2.5 mb-5">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">{{ __('paylater.collateral') }}</p>
                        <p class="ld-num text-sm font-bold text-slate-900 mt-0.5">{{ $paylater['collateral'] }} <span class="text-[10px] text-slate-400">tBNB</span></p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">{{ __('paylater.limit') }}</p>
                        <p class="ld-num text-sm font-bold text-slate-900 mt-0.5">{{ $paylater['limit'] }} <span class="text-[10px] text-slate-400">TLKM</span></p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">{{ __('paylater.due_amount') }}</p>
                        <p class="ld-num text-sm font-bold mt-0.5 {{ $paylater['has_debt'] ? 'text-amber-600' : 'text-slate-900' }}">{{ $paylater['due_amount'] }} <span class="text-[10px] text-slate-400">TLKM</span></p>
                    </div>
                </div>

                @if($paylater['due_date'])
                    <p class="text-[11px] text-slate-500 mb-4">{{ __('paylater.due') }}:
                        <span class="font-semibold {{ $paylater['has_debt'] && $paylater['due_date']->isPast() ? 'text-amber-600' : 'text-slate-700' }}">{{ $paylater['due_date']->format('d M Y H:i') }}</span>
                        @if($paylater['has_debt'])<span class="text-slate-400">({{ $paylater['due_date']->diffForHumans() }})</span>@endif
                    </p>
                @endif

                {{-- Setor kolateral --}}
                <label class="text-[11px] font-semibold text-slate-500">{{ __('Setor kolateral') }}</label>
                <div class="flex gap-2 mt-1.5 mb-1">
                    <div class="relative flex-1">
                        <input id="plDepAmt" type="number" min="0" step="any" oninput="plEstLimit()" placeholder="0.00" class="w-full px-4 py-2.5 pr-14 rounded-xl border border-slate-300 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm">
                        <span class="absolute right-4 top-2.5 text-sm font-medium text-slate-400">tBNB</span>
                    </div>
                    <button onclick="doPaylaterDeposit()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition shrink-0">{{ __('Setor') }}</button>
                </div>
                <p id="plEst" class="text-[11px] text-slate-400 mb-5">{{ __('paylater.est_hint') }}</p>

                {{-- Aksi --}}
                <div class="flex flex-wrap gap-2">
                    <button onclick="doPaylaterBorrow()" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.borrow_btn') }}</button>
                    <button onclick="doPaylaterRepayFull()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $paylater['has_debt'] ? '' : 'disabled' }}>{{ __('paylater.repay_btn') }}@if($paylater['has_debt']) <span class="text-slate-400">({{ $paylater['due_amount'] }})</span>@endif</button>
                    <button onclick="doPaylaterWithdraw()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.withdraw_btn') }}</button>
                </div>
            </div>

            {{-- ============ SUPPLY / EARN ============ --}}
            <div class="p-5 sm:p-7 border-t lg:border-t-0 lg:border-l border-slate-100">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-900"><span class="w-2 h-2 rounded-full bg-green-500"></span>Supply <span class="font-medium text-slate-400 text-xs">· Earn</span></span>
                    <span class="text-[11px] text-slate-400 text-right">{{ __('Bunga peminjam :pct% → bagi hasil', ['pct' => $intPct]) }}</span>
                </div>

                {{-- Fokus: TVL + utilisasi --}}
                <p class="text-xs text-slate-500 mb-1">{{ __('Likuiditas pool (TVL)') }}</p>
                <div class="flex items-end gap-3 mb-4">
                    <p class="ld-num text-4xl font-extrabold text-slate-900">{{ $sup['liquidity'] ?? '0' }} <span class="text-base font-bold text-slate-400">TLKM</span></p>
                    <span class="text-[11px] font-medium mb-1.5 px-2 py-0.5 rounded-full bg-green-50 text-green-700">{{ __('Utilisasi') }} {{ $sup['util'] !== null ? $sup['util'].'%' : '—' }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5 mb-5">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">{{ __('Dipinjam (borrows)') }}</p>
                        <p class="ld-num text-sm font-bold text-slate-900 mt-0.5">{{ $sup['borrows'] ?? '0' }} <span class="text-[10px] text-slate-400">TLKM</span></p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[11px] text-slate-500">{{ __('Reserve protokol') }}</p>
                        <p class="ld-num text-sm font-bold text-slate-900 mt-0.5">{{ $sup['reserve'] ?? '0' }} <span class="text-[10px] text-slate-400">TLKM</span></p>
                    </div>
                </div>

                {{-- Baris jangka --}}
                <div class="space-y-2.5 mb-5">
                    @foreach($terms as $t => $tm)
                        <div class="border border-slate-200 rounded-xl p-3.5 transition hover:border-green-300">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900">{{ __($tm['label']) }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $t == 0 ? __('Tarik kapan saja') : __('Terkunci :n hari', ['n' => $tm['lock_days']]) }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="ld-nisbah text-lg leading-none">{{ $tm['nisbah'] ?? '—' }}%</p>
                                    <p class="text-[10px] text-slate-400">{{ __('bagi hasil') }}</p>
                                </div>
                            </div>
                            @if($tm['has_pos'])
                                <div class="mt-3 pt-3 flex items-center justify-between gap-3 border-t border-slate-100">
                                    <div class="text-[11px] text-slate-500 leading-tight">
                                        <span class="ld-num font-bold text-slate-900">{{ $tm['value'] }} TLKM</span>
                                        <span class="text-green-600"> · +{{ $tm['earned'] }} yield</span>
                                        @if($tm['maturity'])<br><span>{{ $tm['matured'] ? __('Jatuh tempo: sudah') : __('Jatuh tempo').' '.$tm['maturity']->diffForHumans() }}</span>@endif
                                    </div>
                                    <button onclick="doPaylaterWithdrawSupply({{ $t }})" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-[11px] font-semibold px-3 py-1.5 rounded-lg transition shrink-0">
                                        {{ $t == 0 || $tm['matured'] ? __('Tarik') : __('Tarik pokok') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Form supply: jumlah + dropdown animasi + tombol --}}
                <label class="text-[11px] font-semibold text-slate-500">{{ __('Supply likuiditas') }}</label>
                <div class="mt-1.5 space-y-2">
                    <div class="relative">
                        <input id="plSupAmt" type="number" min="0" step="any" placeholder="0.00" class="w-full px-4 py-2.5 pr-14 rounded-xl border border-slate-300 focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none text-sm">
                        <span class="absolute right-4 top-2.5 text-sm font-medium text-slate-400">TLKM</span>
                    </div>
                    <div class="flex gap-2">
                        {{-- custom animated term dropdown --}}
                        <div class="ld-select flex-1" id="plTermSelect" data-open="false">
                            <input type="hidden" id="plSupTerm" value="0">
                            <button type="button" class="ld-trigger text-sm" id="plTermBtn" aria-haspopup="listbox" aria-expanded="false" onclick="plTermToggle(event)">
                                <span class="text-left leading-tight min-w-0">
                                    <span id="plTermLabel" class="font-semibold text-slate-900 block truncate">{{ __($terms[0]['label'] ?? 'Fleksibel') }}</span>
                                    <span id="plTermSub" class="text-[11px] text-slate-500">{{ __('bagi hasil') }} {{ $terms[0]['nisbah'] ?? '—' }}%</span>
                                </span>
                                <svg class="ld-chev w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <ul class="ld-menu" id="plTermMenu" role="listbox" aria-label="{{ __('Pilih jangka supply') }}">
                                @foreach($terms as $t => $tm)
                                    <li class="ld-opt" role="option" data-val="{{ $t }}" data-label="{{ __($tm['label']) }}" data-sub="{{ __('bagi hasil') }} {{ $tm['nisbah'] ?? '—' }}%" aria-selected="{{ $t == 0 ? 'true' : 'false' }}" onclick="plTermPick(this)">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-900 leading-tight">{{ __($tm['label']) }}</p>
                                            <p class="text-[11px] text-slate-500">{{ $t == 0 ? __('Tarik kapan saja') : __('Kunci :n hari', ['n' => $tm['lock_days']]) }}</p>
                                        </div>
                                        <span class="ld-nisbah text-sm ml-auto">{{ $tm['nisbah'] ?? '—' }}%</span>
                                        <svg class="ld-opt-check w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"/></svg>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <button onclick="doPaylaterSupply()" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shrink-0">Supply</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kontrak + riwayat --}}
        @if($paylater['contract'])
            <div class="px-5 sm:px-7 py-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 border-t border-slate-100 bg-slate-50/60">
                <span>{{ __('Kontrak lending dua-sisi:') }}</span>
                <code class="font-mono px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-700">{{ $paylater['contract'] }}</code>
                <button type="button" onclick="plCopyContract()" class="text-slate-500 hover:text-slate-800 underline">{{ __('salin') }}</button>
                <a href="{{ config('chain.explorer_url') }}/address/{{ $paylater['contract'] }}" target="_blank" rel="noopener" class="text-slate-500 hover:text-slate-800 underline">explorer ↗</a>
            </div>
        @endif

        @if($paylaterHistory->isNotEmpty())
            @php $plLabels = ['deposit'=>__('paylater.act_deposit'),'borrow'=>__('paylater.act_borrow'),'repay'=>__('paylater.act_repay'),'withdraw'=>__('paylater.act_withdraw'),'seize'=>__('paylater.act_seize'),'supply'=>__('paylater.act_supply'),'withdraw_supply'=>__('paylater.act_withdraw_supply')];
                  $plUnits  = ['deposit'=>'tBNB','borrow'=>'TLKM','repay'=>'TLKM','withdraw'=>'tBNB','seize'=>'tBNB','supply'=>'TLKM','withdraw_supply'=>'TLKM']; @endphp
            <div class="border-t border-slate-100">
                @foreach($paylaterHistory as $h)
                    <div class="px-5 sm:px-7 py-2.5 flex items-center justify-between gap-3 border-t border-slate-50 first:border-t-0">
                        @php $plAmt = in_array($h->action, ['deposit','withdraw','seize']) ? rtrim(rtrim(number_format((float) $h->amount, 6), '0'), '.') : $fmt($h->amount); @endphp
                        <p class="text-sm text-slate-700">{{ $plLabels[$h->action] ?? $h->action }} <b class="ld-num">{{ $plAmt }} {{ $plUnits[$h->action] ?? '' }}</b> <span class="text-[11px] text-slate-400">· {{ $h->created_at->diffForHumans() }}</span></p>
                        <a href="{{ config('chain.explorer_url') }}/tx/{{ $h->tx_hash }}" target="_blank" rel="noopener" class="text-[11px] text-blue-600 hover:underline shrink-0">tx ↗</a>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</section>

{{-- RIWAYAT --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-6">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">{{ __('Riwayat transfer') }}</h2></div>
    @if($transfers->isEmpty())
        <p class="px-5 py-10 text-center text-sm text-slate-400">{{ __('Belum ada transfer.') }}</p>
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
                            <p class="text-sm text-slate-800 truncate">{{ $t['dir']==='out' ? __('Ke') : __('Dari') }} <span class="font-medium">{{ $t['other']['name'] }}</span></p>
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
    navigator.clipboard?.writeText(PL_CONTRACT).then(() => showToast(@json(__('Alamat kontrak disalin.')), 'success')).catch(() => showToast(@json(__('Gagal menyalin.')), 'warn'));
}

// ===== Dropdown jangka supply (custom, beranimasi) =====
function _plSel() { return document.getElementById('plTermSelect'); }
function plTermSetOpen(open) {
    const s = _plSel(); if (!s) return;
    s.dataset.open = open ? 'true' : 'false';
    document.getElementById('plTermBtn')?.setAttribute('aria-expanded', open ? 'true' : 'false');
}
function plTermToggle(e) {
    if (e) e.stopPropagation();
    const s = _plSel(); if (!s) return;
    plTermSetOpen(s.dataset.open !== 'true');
}
function plTermPick(el) {
    document.getElementById('plSupTerm').value = el.dataset.val;
    document.getElementById('plTermLabel').textContent = el.dataset.label;
    document.getElementById('plTermSub').textContent = el.dataset.sub;
    document.querySelectorAll('#plTermMenu .ld-opt').forEach(o => o.setAttribute('aria-selected', o === el ? 'true' : 'false'));
    plTermSetOpen(false);
}
document.addEventListener('click', (e) => { const s = _plSel(); if (s && !s.contains(e.target)) plTermSetOpen(false); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') plTermSetOpen(false); });

function plEstLimit() {
    const el = document.getElementById('plDepAmt'); if (!el) return;
    const v = parseFloat(el.value) || 0;
    const est = v * PAYLATER_RATE_TLKM_PER_BNB;
    document.getElementById('plEst').textContent = v > 0 ? '≈ ' + tr(@json(__(':n TLKM limit')), { n: est.toLocaleString(UI_NUM_LOCALE) }) : @json(__('paylater.est_hint'));
}
function plOk(hash) { txProgress.close(); uiAlert({ title: @json(__('Berhasil')), message: hash ? `<a href="{{ config('chain.explorer_url') }}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">` + @json(__('Lihat transaksi')) + ` ↗</a>` : @json(__('Tersimpan.')), type: 'success' }).then(() => location.reload()); }
function plFail(e) { txProgress.close(); uiAlert({ title: @json(__('Gagal')), message: niceError(e), type: 'error' }); }

async function doPaylaterDeposit() {
    let amt = document.getElementById('plDepAmt')?.value;
    if (!amt || +amt <= 0) amt = await uiPrompt({ title: @json(__('paylater.deposit_btn')), label: @json(__('Jumlah tBNB agunan:')), type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: @json(__('Lanjut')) });
    if (amt === null || +amt <= 0) return;
    txProgress.open(@json(__('Deposit agunan')), [@json(__('Menandatangani')), @json(__('Mencatat'))]);
    try { txProgress.active(0); const h = await depositCollateralPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('deposit', amt, h); plOk(h); } catch (e) { plFail(e); }
}
async function doPaylaterBorrow() {
    const amt = await uiPrompt({ title: @json(__('paylater.borrow_btn')), label: @json(__('Jumlah TLKM yang dipinjam (≤ sisa limit):')), type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: @json(__('Lanjut')) });
    if (amt === null || +amt <= 0) return;
    const due = (+amt) * (10000 + PL_INTEREST_BPS) / 10000;
    const ok = await uiConfirm({ title: @json(__('paylater.borrow_btn')), message: tr(@json(__('Pinjam :amt. Wajib bayar :due (bunga :rate%) sebelum tenggat.')), { amt: `<b>${(+amt).toLocaleString(UI_NUM_LOCALE)} TLKM</b>`, due: `<b>${due.toLocaleString(UI_NUM_LOCALE)} TLKM</b>`, rate: PL_INTEREST_BPS / 100 }), confirmText: @json(__('Ya, pinjam')) });
    if (!ok) return;
    txProgress.open(@json(__('Pinjam TLKM')), [@json(__('Menandatangani')), @json(__('Mencatat'))]);
    try { txProgress.active(0); const h = await borrowPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('borrow', amt, h); plOk(h); } catch (e) { plFail(e); }
}
// Lunasi PENUH (tanpa input) — bayar seluruh kewajiban (pokok + bunga) sekaligus.
async function doPaylaterRepayFull() {
    if (!PL_HAS_DEBT || parseFloat(PL_DUE_RAW) <= 0) {
        uiAlert({ title: @json(__('Tidak ada kewajiban')), message: @json(__('Kamu belum punya utang paylater untuk dilunasi.')), type: 'info' });
        return;
    }
    const shown = (parseFloat(PL_DUE_RAW) || 0).toLocaleString('en-US', { maximumFractionDigits: 6 });
    const ok = await uiConfirm({ title: @json(__('paylater.repay_btn')), message: tr(@json(__('Lunasi seluruh kewajiban :amt (pokok + bunga) sekaligus?')), { amt: `<b>${shown} TLKM</b>` }), confirmText: @json(__('Ya, lunasi')) });
    if (!ok) return;
    txProgress.open(@json(__('Melunasi')), [@json(__('Approve TLKM')), @json(__('Mencatat'))]);
    try { txProgress.active(0); const h = await repayPaylater(PL_DUE_RAW); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('repay', PL_DUE_RAW, h); plOk(h); } catch (e) { plFail(e); }
}
async function doPaylaterWithdraw() {
    const amt = await uiPrompt({ title: @json(__('paylater.withdraw_btn')), label: @json(__('Jumlah tBNB agunan yang ditarik (kewajiban harus 0):')), type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: @json(__('Tarik')) });
    if (amt === null || +amt <= 0) return;
    txProgress.open(@json(__('Tarik agunan')), [@json(__('Menandatangani')), @json(__('Mencatat'))]);
    try { txProgress.active(0); const h = await withdrawCollateralPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('withdraw', amt, h); plOk(h); } catch (e) { plFail(e); }
}

// ===== Sisi PENYUPLAI (Danai / Earn) — dengan jangka =====
async function doPaylaterSupply() {
    let amt = document.getElementById('plSupAmt')?.value;
    const term = parseInt(document.getElementById('plSupTerm')?.value ?? '0', 10) || 0;
    if (!amt || +amt <= 0) amt = await uiPrompt({ title: @json(__('Danai Pool')), label: @json(__('Jumlah TLKM yang didanai:')), type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: @json(__('Lanjut')) });
    if (amt === null || +amt <= 0) return;
    const info = PL_SUPPLY_TERMS[term] || {};
    const lockNote = term == 0 ? @json(__('Bisa ditarik kapan saja.')) : @json(__('Dana dikunci hingga jatuh tempo. Tarik lebih awal = <b>pokok saja</b> (bagi hasil hangus).'));
    const ok = await uiConfirm({ title: @json(__('Danai')) + ' — ' + (info.label || @json(__('Pool'))), message: tr(@json(__('Setor :amt ke jangka :term.')), { amt: `<b>${(+amt).toLocaleString(UI_NUM_LOCALE)} TLKM</b>`, term: `<b>${info.label || ''}</b>` }) + ' ' + lockNote, confirmText: @json(__('Ya, danai')) });
    if (!ok) return;
    txProgress.open(@json(__('Danai pool')), [@json(__('Approve TLKM')), @json(__('Mencatat'))]);
    try { txProgress.active(0); const h = await supplyPaylater(term, amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('supply', amt, h); plOk(h); } catch (e) { plFail(e); }
}
// Tarik SELURUH dana penyuplai pada satu jangka (semua share) — pokok + yield (atau pokok saja bila belum jatuh tempo).
async function doPaylaterWithdrawSupply(term) {
    const info = PL_SUPPLY_TERMS[term] || {};
    if (!info.shares_raw || info.shares_raw === '0') {
        uiAlert({ title: @json(__('Belum mendanai')), message: @json(__('Kamu belum punya dana di jangka ini.')), type: 'info' });
        return;
    }
    const early = !info.matured;
    const msg = early
        ? tr(@json(__('Jangka :term belum jatuh tempo. Tarik sekarang hanya mengembalikan <b>pokok</b> (bagi hasil hangus). Lanjut?')), { term: `<b>${info.label}</b>` })
        : tr(@json(__('Tarik seluruh dana :term (± :amt, termasuk bagi hasil)?')), { term: `<b>${info.label}</b>`, amt: `<b>${(parseFloat(info.value)||0).toLocaleString('en-US', { maximumFractionDigits: 4 })} TLKM</b>` }) + '<br><span class="text-xs text-slate-400">' + @json(__('Gagal bila likuiditas pool sedang dipinjam habis.')) + '</span>';
    const ok = await uiConfirm({ title: @json(__('Tarik dana')), message: msg, confirmText: early ? @json(__('Ya, tarik pokok')) : @json(__('Ya, tarik')) });
    if (!ok) return;
    txProgress.open(@json(__('Tarik dana')), [@json(__('Menandatangani')), @json(__('Mencatat'))]);
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
        if (r.self) { el.textContent = @json(__('Itu wallet kamu sendiri.')); el.className = 'text-xs mb-3 px-1 text-amber-600'; }
        else if (r.found && r.name) { el.innerHTML = '→ <b class="text-slate-700">' + r.name + '</b> <span class="font-mono text-slate-400">' + r.wallet.slice(0,6) + '…' + r.wallet.slice(-4) + '</span>'; el.className = 'text-xs mb-3 px-1 text-green-600'; }
        else if (r.found) { el.textContent = '→ ' + @json(__('Wallet valid (bukan pengguna terdaftar)')); el.className = 'text-xs mb-3 px-1 text-slate-500'; }
        else { el.textContent = @json(__('Penerima tidak ditemukan.')); el.className = 'text-xs mb-3 px-1 text-red-500'; }
        el.classList.remove('hidden');
    }, 400);
}

async function doSend() {
    const input = (document.getElementById('sendTo').value || '').trim();
    const amt = parseFloat(document.getElementById('sendAmount').value);
    const note = (document.getElementById('sendNote').value || '').trim();
    if (!amt || amt <= 0) { showToast(@json(__('Masukkan nominal yang valid.')), 'warn'); return; }

    // Resolusi penerima: No HP → wallet+nama; atau langsung 0x.
    let to = input, name = null;
    if (!/^0x[a-fA-F0-9]{40}$/.test(input)) {
        const r = await lookupRecipient(input);
        if (r.self) { showToast(@json(__('Tidak bisa kirim ke wallet sendiri.')), 'warn'); return; }
        if (!r.found || !r.wallet) { showToast(@json(__('Penerima (No HP/wallet) tidak ditemukan.')), 'warn'); return; }
        to = r.wallet; name = r.name;
    } else {
        const r = await lookupRecipient(input); if (r.found) name = r.name;
    }

    const who = name ? `<b class="text-slate-900">${name}</b><br><span class="font-mono text-xs break-all text-slate-400">${to}</span>` : `<span class="font-mono text-xs break-all">${to}</span>`;
    const ok = await uiConfirm({ title: @json(__('Kirim TLKM')), message: tr(@json(__('Kirim :amt ke:')), { amt: `<b class="text-blue-600">${amt} TLKM</b>` }) + `<br>${who}`, confirmText: @json(__('Ya, kirim')) });
    if (!ok) return;
    let pin = null;
    if (IS_EMBEDDED) { pin = await askPin(@json(__('Kirim TLKM'))); if (!pin) return; }
    const btn = document.getElementById('sendBtn'); btn.disabled = true;
    txProgress.open(@json(__('Kirim TLKM')), [@json(__('Memeriksa jaringan')), IS_EMBEDDED ? @json(__('Tanda tangan dengan PIN')) : @json(__('Konfirmasi di MetaMask')), @json(__('Mencatat'))]);
    try {
        txProgress.active(0); if (!IS_EMBEDDED) await checkNetwork(); txProgress.done(0);
        txProgress.active(1, IS_EMBEDDED ? @json(__('Menandatangani & menyiarkan…')) : @json(__('Konfirmasi transfer di MetaMask…')));
        const hash = IS_EMBEDDED ? await pinTx('/pin/transfer', { pin, to, amount: amt }) : await sendTLKM(to, amt);
        txProgress.done(1);
        txProgress.active(2, @json(__('Verifikasi on-chain…')));
        await fetch('/wallet/send', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ tx_hash: hash, note }) });
        txProgress.done(2);
        setTimeout(() => { txProgress.close(); uiAlert({ title: @json(__('TLKM Terkirim')), message: tr(@json(__(':amt TLKM terkirim.')), { amt }) + `<br><a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">` + @json(__('Lihat transaksi')) + ` ↗</a>`, type: 'success' }).then(() => location.reload()); }, 400);
    } catch (e) {
        txProgress.close();
        uiAlert({ title: @json(__('Gagal mengirim')), message: niceError(e), type: 'error' });
        btn.disabled = false;
    }
}

const LINK_COPIED = @json(__('Link disalin'));
function shareReq(code, amountLabel) {
    const url = window.location.origin + '/pay/' + code;
    openModal(`
        <div class="p-6 text-center">
            <h3 class="text-lg font-bold text-slate-900 mb-1">{{ __('Minta Uang') }}</h3>
            <p class="text-sm text-slate-500 mb-4">{{ __('Nominal:') }} ${amountLabel}</p>
            <div id="qrBox" class="flex justify-center mb-4"></div>
            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                <input value="${url}" readonly class="flex-1 bg-transparent text-xs text-slate-600 outline-none" id="payLink">
                <button onclick="navigator.clipboard.writeText('${url}').then(()=>showToast(LINK_COPIED,'success'))" class="text-xs text-blue-600 font-medium shrink-0">{{ __('Salin') }}</button>
            </div>
            <button onclick="closeModal()" class="mt-4 w-full py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">{{ __('Tutup') }}</button>
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
            <h3 class="text-lg font-bold text-slate-900 mb-1">{{ __('Scan QRIS / GPN') }}</h3>
            <p class="text-xs text-slate-500 mb-3">{{ __('Arahkan kamera ke kode QRIS, unggah gambar/screenshot QR, tempel kodenya, atau pakai contoh demo.') }}</p>
            <div id="qrReader" class="rounded-xl overflow-hidden bg-slate-100 mb-3" style="min-height:200px"></div>
            <div id="qrFileReader" class="hidden"></div>
            <input type="file" id="qrFile" accept="image/*" class="hidden" onchange="qrisFromImage(this)">
            <button onclick="qrisPickImage()" class="w-full mb-2 py-2.5 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-slate-600 hover:border-blue-500 hover:text-blue-600 text-sm font-medium transition inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ __('Unggah gambar QRIS') }}
            </button>
            <textarea id="qrPaste" rows="2" placeholder="{{ __('…atau tempel payload QRIS di sini') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs outline-none focus:border-blue-500 resize-none mb-2"></textarea>
            <div class="flex gap-2">
                <button onclick="qrisUseText()" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold">{{ __('Gunakan kode') }}</button>
                <button onclick="qrisDemo()" class="flex-1 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">{{ __('Pakai contoh') }}</button>
            </div>
            <button onclick="stopScanner();closeModal()" class="mt-2 w-full py-2 text-slate-500 text-sm">{{ __('Batal') }}</button>
        </div>`);
    setTimeout(() => {
        try {
            qrScanner = new Html5Qrcode('qrReader');
            qrScanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 200 },
                (txt) => { stopScanner(); qrisFromRaw(txt); }, () => {});
        } catch (e) { document.getElementById('qrReader').innerHTML = '<p class="text-xs text-slate-400 p-4 text-center">' + @json(__('Kamera tak tersedia — tempel kode atau pakai contoh.')) + '</p>'; }
    }, 80);
}
function qrisUseText() { const v = (document.getElementById('qrPaste').value || '').trim(); if (!v) return showToast(@json(__('Tempel kode QRIS dulu.')), 'warn'); stopScanner(); qrisFromRaw(v); }
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
        showToast(@json(__('QR tidak terbaca dari gambar. Pastikan kode QRIS jelas & tidak terpotong.')), 'warn');
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
            <p class="text-xs text-slate-400">{{ __('Tinjau order') }}</p>
            <div class="flex items-baseline gap-2 mt-1 mb-4">
                <input id="qrisAmt" type="number" min="1" placeholder="0" class="text-3xl font-extrabold w-44 outline-none border-b border-slate-200 focus:border-blue-500">
                <span class="text-lg text-slate-400 font-semibold">IDR</span>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm space-y-2.5">
                <div class="flex justify-between gap-3"><span class="text-slate-500">{{ __('Bayar ke') }}</span><span class="font-semibold text-slate-800 text-right truncate">${merchant}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('Jumlah pembayaran') }}</span><span id="qrisUsdc" class="font-semibold text-blue-600">0 USDC</span></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('Saldo') }}</span><span class="text-slate-700">52.272277 USDC</span></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('Biaya jaringan') }}</span><span class="text-green-600 font-medium">{{ __('Gratis') }}</span></div>
            </div>
            <p class="text-[11px] text-amber-600 mt-3">{{ __('Prototipe — pembayaran ini') }} <b>{{ __('simulasi') }}</b>{{ __(', belum ada settlement nyata ke merchant.') }}</p>
            <button onclick="qrisConfirm()" class="mt-4 w-full py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold">{{ __('Konfirmasi pembayaran') }}</button>
            <button onclick="closeModal()" class="mt-2 w-full py-2 text-slate-500 text-sm">{{ __('Batal') }}</button>
        </div>`);
    const amt = document.getElementById('qrisAmt');
    amt.oninput = () => { const v = parseFloat(amt.value) || 0; document.getElementById('qrisUsdc').textContent = (v / QRIS_RATE).toFixed(4) + ' USDC'; };
    setTimeout(() => amt.focus(), 60);
}

async function qrisConfirm() {
    const amt = parseFloat(document.getElementById('qrisAmt').value);
    if (!amt || amt < 1) return showToast(@json(__('Masukkan nominal (IDR).')), 'warn');
    const pin = await askPin(@json(__('Bayar QRIS'))); if (!pin) return;
    txProgress.open(@json(__('Membayar QRIS')), [@json(__('Verifikasi PIN')), @json(__('Memproses (simulasi)'))]);
    try {
        txProgress.active(0);
        const res = await fetch('/qris/pay', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ pin, merchant: qrisMerchant, city: qrisCity, amount: amt }) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || @json(__('Pembayaran gagal')));
        txProgress.done(0); txProgress.active(1); txProgress.done(1);
        setTimeout(() => { txProgress.close(); qrisReceipt(data.receipt); }, 300);
    } catch (e) { txProgress.close(); uiAlert({ title: @json(__('Gagal')), message: niceError(e), type: 'error' }); }
}

function qrisReceipt(r) {
    openModal(`
        <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900">{{ __('Pembayaran Berhasil') }}</h3>
            <span class="inline-block text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-bold mt-1">{{ __('SIMULASI · PROTOTIPE') }}</span>
            <p class="text-3xl font-extrabold text-slate-900 mt-3">Rp ${Number(r.amount_idr).toLocaleString('id-ID')}</p>
            <p class="text-sm text-slate-500">${r.stablecoin_amount} ${r.stablecoin} · {{ __('kurs') }} Rp${Number(r.rate).toLocaleString('id-ID')}</p>
            <div class="text-left text-sm bg-slate-50 border border-slate-200 rounded-xl p-3 mt-4 space-y-1.5">
                <div class="flex justify-between gap-3"><span class="text-slate-500">{{ __('Merchant') }}</span><span class="font-medium text-right truncate">${r.merchant}</span></div>
                ${r.city ? `<div class="flex justify-between"><span class="text-slate-500">{{ __('Kota') }}</span><span>${r.city}</span></div>` : ''}
                <div class="flex justify-between"><span class="text-slate-500">{{ __('Waktu') }}</span><span>${r.time}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('No. Ref') }}</span><span class="font-mono text-xs">${r.ref}</span></div>
            </div>
            <p class="text-[11px] text-amber-600 mt-3">{{ __('Receipt simulasi untuk prototipe —') }} <b>{{ __('bukan') }}</b> {{ __('bukti pembayaran nyata.') }}</p>
            <button onclick="closeModal()" class="mt-4 w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">{{ __('Selesai.done') }}</button>
        </div>`);
}
</script>
@endsection

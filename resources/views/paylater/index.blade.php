@extends('layouts.app')

@section('content')
@php $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); @endphp

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-2 mb-1">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('paylater.title') }}</h1>
        <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-semibold">DEMO · belum diaudit</span>
    </div>
    <p class="text-sm text-slate-500 mb-6">{{ __('paylater.subtitle') }}</p>

    {{-- Demo disclaimer --}}
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 mb-6 text-sm flex items-start gap-3">
        <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <div>{{ __('paylater.demo_note') }}</div>
    </div>

    @unless($configured)
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center text-slate-500">
            {{ __('paylater.not_configured') }}
        </div>
    @else
        {{-- Ringkasan posisi (dibaca dari chain) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <p class="text-xs text-slate-500">{{ __('paylater.collateral') }}</p>
                <p class="text-lg font-bold text-slate-900">{{ $fmt($position['collateral']) }} <span class="text-xs text-slate-400">tBNB</span></p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <p class="text-xs text-slate-500">{{ __('paylater.limit') }}</p>
                <p class="text-lg font-bold text-blue-600">{{ $fmt($position['limit']) }} <span class="text-xs text-slate-400">TLKM</span></p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <p class="text-xs text-slate-500">{{ __('paylater.debt') }}</p>
                <p class="text-lg font-bold {{ $position['has_debt'] ? 'text-red-600' : 'text-slate-900' }}">{{ $fmt($position['debt']) }} <span class="text-xs text-slate-400">TLKM</span></p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <p class="text-xs text-slate-500">{{ __('paylater.available') }}</p>
                <p class="text-lg font-bold text-green-600">{{ $fmt($position['available']) }} <span class="text-xs text-slate-400">TLKM</span></p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 mb-6 text-xs text-slate-500">
            <span>{{ __('paylater.liquidity') }}: <b class="text-slate-700">{{ $liquidity !== null ? $fmt($liquidity).' TLKM' : '—' }}</b></span>
            @if($position['due_date'])
                <span>{{ __('paylater.due') }}: <b class="{{ $position['has_debt'] && $position['due_date']->isPast() ? 'text-red-600' : 'text-slate-700' }}">{{ $position['due_date']->format('d M Y H:i') }}</b>
                    @if($position['has_debt'])<span class="text-slate-400">({{ $position['due_date']->diffForHumans() }})</span>@endif
                </span>
            @endif
        </div>

        {{-- Aksi --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6">
            <h2 class="font-bold text-slate-900 mb-1">{{ __('paylater.deposit_title') }}</h2>
            <p class="text-xs text-slate-500 mb-3">{{ __('paylater.deposit_hint') }}</p>
            <div class="flex flex-col sm:flex-row gap-2 mb-4">
                <div class="relative flex-1">
                    <input id="depAmt" type="number" min="0" step="any" oninput="estLimit()" placeholder="0.00"
                        class="w-full px-4 py-2.5 pr-16 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                    <span class="absolute right-4 top-2.5 text-sm text-slate-400 font-medium">tBNB</span>
                </div>
                <button onclick="doPaylaterDeposit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.deposit_btn') }}</button>
            </div>
            <p id="estLimit" class="text-xs text-slate-400 mb-4">{{ __('paylater.est_hint') }}</p>

            <div class="flex flex-wrap gap-2">
                <button onclick="doPaylaterBorrow()" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.borrow_btn') }}</button>
                <button onclick="doPaylaterRepay()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.repay_btn') }}</button>
                <button onclick="doPaylaterWithdraw()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition">{{ __('paylater.withdraw_btn') }}</button>
            </div>
        </div>

        {{-- Riwayat --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">{{ __('paylater.history') }}</h2></div>
            @if($history->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-400">{{ __('paylater.history_empty') }}</p>
            @else
                @php $labels = ['deposit'=>__('paylater.act_deposit'),'borrow'=>__('paylater.act_borrow'),'repay'=>__('paylater.act_repay'),'withdraw'=>__('paylater.act_withdraw'),'seize'=>__('paylater.act_seize')];
                      $units  = ['deposit'=>'tBNB','borrow'=>'TLKM','repay'=>'TLKM','withdraw'=>'tBNB','seize'=>'tBNB']; @endphp
                @foreach($history as $h)
                    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-800">{{ $labels[$h->action] ?? $h->action }} <b>{{ $fmt($h->amount) }} {{ $units[$h->action] ?? '' }}</b></p>
                            <p class="text-[11px] text-slate-400">{{ $h->created_at->diffForHumans() }}</p>
                        </div>
                        <a href="{{ config('chain.explorer_url') }}/tx/{{ $h->tx_hash }}" target="_blank" rel="noopener" class="text-[11px] text-blue-600 hover:underline shrink-0">tx ↗</a>
                    </div>
                @endforeach
            @endif
        </div>
    @endunless
</div>
@endsection

@section('scripts')
<script>
const PL_RATE = {{ (int) $rate }}; // TLKM per 1 tBNB (estimasi UI)

function estLimit() {
    const v = parseFloat(document.getElementById('depAmt').value) || 0;
    const est = v * PL_RATE;
    document.getElementById('estLimit').textContent = v > 0
        ? `≈ ${est.toLocaleString('id-ID')} TLKM limit`
        : @json(__('paylater.est_hint'));
}

function plOk(hash) {
    txProgress.close();
    uiAlert({ title: 'Berhasil', message: hash ? `<a href="{{ config('chain.explorer_url') }}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">Lihat transaksi ↗</a>` : 'Tersimpan.', type: 'success' }).then(() => location.reload());
}
function plFail(e) { txProgress.close(); uiAlert({ title: 'Gagal', message: niceError(e), type: 'error' }); }

async function doPaylaterDeposit() {
    let amt = document.getElementById('depAmt').value;
    if (!amt || +amt <= 0) { amt = await uiPrompt({ title: @json(__('paylater.deposit_btn')), label: 'Jumlah tBNB agunan:', type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: 'Lanjut' }); }
    if (amt === null || +amt <= 0) return;
    txProgress.open('Deposit agunan', ['Menandatangani', 'Menyiarkan']);
    try { txProgress.active(0); const h = await depositCollateralPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('deposit', amt, h); plOk(h); } catch (e) { plFail(e); }
}

async function doPaylaterBorrow() {
    const amt = await uiPrompt({ title: @json(__('paylater.borrow_btn')), label: 'Jumlah TLKM yang dipinjam (≤ sisa limit):', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Pinjam' });
    if (amt === null || +amt <= 0) return;
    txProgress.open('Pinjam TLKM', ['Menandatangani', 'Menyiarkan']);
    try { txProgress.active(0); const h = await borrowPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('borrow', amt, h); plOk(h); } catch (e) { plFail(e); }
}

async function doPaylaterRepay() {
    const amt = await uiPrompt({ title: @json(__('paylater.repay_btn')), label: 'Jumlah TLKM yang dilunasi:', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Lunasi' });
    if (amt === null || +amt <= 0) return;
    txProgress.open('Melunasi', ['Approve TLKM', 'Menyiarkan']);
    try { txProgress.active(0); const h = await repayPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('repay', amt, h); plOk(h); } catch (e) { plFail(e); }
}

async function doPaylaterWithdraw() {
    const amt = await uiPrompt({ title: @json(__('paylater.withdraw_btn')), label: 'Jumlah tBNB agunan yang ditarik (utang harus 0):', type: 'number', min: 0, step: 'any', placeholder: '0.00', confirmText: 'Tarik' });
    if (amt === null || +amt <= 0) return;
    txProgress.open('Tarik agunan', ['Menandatangani', 'Menyiarkan']);
    try { txProgress.active(0); const h = await withdrawCollateralPaylater(amt); if (!h) return txProgress.close(); txProgress.done(0); txProgress.active(1); txProgress.done(1); await recordPaylater('withdraw', amt, h); plOk(h); } catch (e) { plFail(e); }
}
</script>
@endsection

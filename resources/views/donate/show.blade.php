@extends('layouts.app')

@section('content')
@php
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
    $goal = (float) $campaign->goal_amount;
    $pct  = $goal > 0 ? min(100, round($raised / $goal * 100)) : null;
    $isSup = auth()->check() && auth()->user()->isSupervisor();
@endphp

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/donate" class="hover:text-blue-600 transition">{{ __('Donasi') }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700 truncate">{{ $campaign->title }}</span>
</nav>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- KIRI: gambar + deskripsi + donatur --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 aspect-[16/9] flex items-center justify-center">
            @if($campaign->image)
                <img src="{{ $campaign->imageUrl() }}" alt="{{ $campaign->title }}" class="w-full h-full object-cover" referrerpolicy="no-referrer"
                     onerror="this.onerror=null; this.src='{{ $campaign->coverUrl() }}'; document.getElementById('photoCredit')?.remove()">
                @if($credit = $campaign->imageCredit())
                    <a id="photoCredit" href="{{ $credit['url'] }}" target="_blank" rel="noopener noreferrer"
                       class="absolute left-3 bottom-3 text-xs font-medium text-white bg-black/55 hover:bg-black/70 backdrop-blur-sm rounded-lg px-2.5 py-1 transition">{{ $credit['text'] }} ↗</a>
                @endif
            @else
                <svg class="w-14 h-14 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 000-7.78z"/></svg>
            @endif
        </div>

        <div>
        @if($campaign->isAi())
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12m-1 0a1 1 0 102 0 1 1 0 10-2 0M16.2 7.8a6 6 0 010 8.5M7.8 16.2a6 6 0 010-8.5M19.1 4.9a10 10 0 010 14.2M4.9 19.1a10 10 0 010-14.2"/></svg>
                    {{ __('Dibuka oleh Radar Bencana AI') }}
                </span>
                @if($campaign->disasterEvent)
                    <span class="text-xs text-slate-500">{{ __('Sumber') }} {{ __($campaign->disasterEvent->sourceName()) }}@if($campaign->disasterEvent->ai_severity !== null) · {{ __('skor AI') }} {{ $campaign->disasterEvent->ai_severity }}@endif</span>
                @endif
            </div>
        @endif
            <div class="flex items-start justify-between gap-3">
                <h1 class="text-2xl font-bold text-slate-900">{{ $campaign->title }}</h1>
                @if($isSup)
                    <a href="/donate/{{ $campaign->slug }}/edit" class="shrink-0 inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:border-blue-400 hover:text-blue-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11 18l-4 1 1-4 9.6-9.6z"/></svg>
                        {{ __('Edit') }}
                    </a>
                @endif
            </div>
            @if($campaign->isAi() && $campaign->description)
                @php
                    $sections = ['Kronologi', 'Dampak', 'Kebutuhan mendesak', 'Penyaluran dana', 'Sumber'];
                    $linkify = fn ($t) => preg_replace('~(https?://[^\s<]+)~', '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all">$1</a>', e($t));
                @endphp
                <div class="mt-3 space-y-1 text-sm text-slate-600 leading-relaxed">
                    @foreach(preg_split('/\R/', $campaign->description) as $line)
                        @if(in_array(trim($line), $sections, true))
                            <h3 class="font-semibold text-slate-900 pt-3">{{ __(trim($line)) }}</h3>
                        @elseif(trim($line) !== '')
                            <p class="{{ str_starts_with(trim($line), '•') ? 'pl-3 text-xs text-slate-500' : '' }}">{!! $linkify($line) !!}</p>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500 mt-2 whitespace-pre-line leading-relaxed">{{ $campaign->description ?: __('Tidak ada deskripsi.') }}</p>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">{{ __('Donatur') }}</h2>
                <span class="text-xs text-slate-400">{{ __(':n donatur', ['n' => $donors]) }}</span>
            </div>
            @if($recent->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-400">{{ __('Belum ada donasi. Jadilah yang pertama.') }}</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recent as $d)
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <a href="/explorer/{{ $d['wallet'] }}" class="text-sm font-medium text-slate-800 hover:text-blue-600 truncate">{{ $d['identity']['name'] }}</a>
                                <p class="text-[11px] text-slate-400">{{ $d['at']->format('d M Y H:i') }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold text-green-600">{{ $fmt($d['amount']) }} TLKM</p>
                                <a href="{{ config('chain.explorer_url') }}/tx/{{ $d['tx'] }}" target="_blank" rel="noopener" class="text-[11px] text-slate-400 hover:text-blue-600 font-mono">tx ↗</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- KANAN: total + form donasi --}}
    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-28 space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                {{-- Status donasi + batas waktu (E1/E3) --}}
                @php $closed = $campaign->isClosed(); @endphp
                <div class="flex items-center gap-2 mb-3">
                    @if($closed)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">● {{ __('Donasi ditutup') }}</span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-green-50 text-green-700 border border-green-200">● {{ __('Donasi dibuka') }}</span>
                    @endif
                </div>
                @if($campaign->closes_at)
                    <p class="text-xs {{ $closed ? 'text-slate-500' : 'text-slate-600' }} mb-3">
                        {{ $closed ? __('Donasi ditutup pada') : __('Batas donasi') }}: <b>{{ $campaign->closes_at->translatedFormat('d F Y') }}</b>
                        @unless($closed) <span class="text-slate-400">({{ $campaign->closes_at->diffForHumans() }})</span> @endunless
                    </p>
                @else
                    <p class="text-xs text-slate-400 mb-3">{{ __('Tanpa batas waktu.') }}</p>
                @endif

                {{-- Saldo saat ini = total masuk - sudah disalurkan (E2) --}}
                <p class="text-xs text-slate-400">{{ __('Saldo saat ini') }}</p>
                <p class="text-3xl font-extrabold text-green-600 mt-0.5">{{ $fmt($balance) }} <span class="text-base font-semibold">TLKM</span></p>
                @if($pct !== null)
                    <div class="mt-3 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">{{ __(':pct% dari target :goal TLKM', ['pct' => $pct, 'goal' => $fmt($goal)]) }}</p>
                @endif

                {{-- Rincian angka yang jelas --}}
                <div class="grid grid-cols-2 gap-2 mt-4 text-center">
                    <div class="rounded-xl bg-slate-50 border border-slate-100 py-2">
                        <p class="text-[11px] text-slate-400">{{ __('Total masuk') }}</p>
                        <p class="text-sm font-bold text-slate-800">{{ $fmt($raised) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 py-2">
                        <p class="text-[11px] text-slate-400">{{ __('Sudah disalurkan') }}</p>
                        <p class="text-sm font-bold text-slate-800">{{ $fmt($disbursed) }}</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500">
                    {{ __('Penerima:') }}
                    <a href="/explorer/{{ $campaign->recipient_wallet }}" class="font-mono text-blue-600 hover:underline">{{ substr($campaign->recipient_wallet, 0, 10) }}…{{ substr($campaign->recipient_wallet, -6) }}</a>
                </div>

                @if($configured && !$closed)
                    <div class="grid grid-cols-4 gap-2 mt-5 mb-3">
                        @foreach([10, 50, 100, 500] as $preset)
                            <button type="button" onclick="setDon({{ $preset }})" class="py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-700 hover:border-blue-500 hover:text-blue-600 transition">{{ $preset }}</button>
                        @endforeach
                    </div>
                    <input id="donAmount" type="number" min="0" step="any" placeholder="{{ __('Nominal TLKM') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm mb-3">
                    <button id="donBtn" onclick="doDonate()" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">{{ __('Donasi sekarang') }}</button>
                    <p class="text-[11px] text-slate-400 mt-3">{{ __('2 konfirmasi MetaMask:') }} <b>approve</b> {{ __('lalu') }} <b>donate</b>. {{ __('Tanpa biaya platform.') }}</p>
                @elseif($closed)
                    <p class="mt-5 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-xl p-3">{{ __('Donasi untuk campaign ini sudah') }} <b>{{ __('ditutup') }}</b>{{ $campaign->closes_at ? ' '.__('pada').' '.$campaign->closes_at->translatedFormat('d F Y') : '' }}. {{ __('Terima kasih atas dukungannya.') }}</p>
                @else
                    <p class="mt-5 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3">{{ __('Donasi belum bisa diproses — kontrak belum aktif.') }}</p>
                @endif
            </div>

            @if($isSup && $configured)
                <div class="bg-white border border-blue-200 rounded-2xl shadow-sm p-6">
                    <h2 class="font-bold text-slate-900 mb-1">{{ __('Salurkan dana') }}</h2>
                    <p class="text-xs text-slate-500 mb-4">{{ __('Kirim') }} <b>{{ __('seluruh saldo campaign') }}</b>{{ $poolBalance !== null ? ' ('.$fmt($poolBalance).' TLKM)' : '' }} {{ __('ke wallet penerima. Tercatat on-chain.') }}</p>
                    <button id="disbBtn" onclick="doDisburse()" class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition">{{ __('Salurkan ke penerima') }}</button>
                </div>
            @endif

            @if($disbursements->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                    <h2 class="font-bold text-slate-900 mb-3 text-sm">{{ __('Penyaluran') }}</h2>
                    <div class="space-y-2">
                        @foreach($disbursements as $d)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">{{ $d->created_at->format('d M Y') }}</span>
                                <span class="font-semibold text-slate-700">{{ $fmt($d->amount) }} TLKM
                                    <a href="{{ config('chain.explorer_url') }}/tx/{{ $d->tx_hash }}" target="_blank" class="text-blue-600 hover:underline font-mono ml-1">↗</a>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const CAMPAIGN_ID   = @json($campaign->chainId());
    const CAMPAIGN_SLUG = @json($campaign->slug);
    const RECIPIENT     = @json(strtolower($campaign->recipient_wallet));

    function setDon(v) { document.getElementById('donAmount').value = v; }

    async function doDonate() {
        const amt = parseFloat(document.getElementById('donAmount').value);
        if (!amt || amt <= 0) { showToast(@json(__('Masukkan nominal donasi yang valid.')), 'warn'); return; }
        let pin = null;
        if (IS_EMBEDDED) { pin = await askPin(@json(__('Donasi'))); if (!pin) return; }
        const btn = document.getElementById('donBtn'); btn.disabled = true;
        txProgress.open(@json(__('Donasi TLKM')), [@json(__('Memeriksa jaringan')), IS_EMBEDDED ? @json(__('Tanda tangan dengan PIN')) : @json(__('Approve & donasi di MetaMask')), @json(__('Mencatat donasi'))]);
        try {
            txProgress.active(0); if (!IS_EMBEDDED) await checkNetwork(); txProgress.done(0);
            txProgress.active(1, IS_EMBEDDED ? @json(__('Menandatangani (approve + donate)…')) : @json(__('Konfirmasi 2x di MetaMask (approve lalu donate)…')));
            const hash = IS_EMBEDDED ? await pinTx('/pin/donate', { pin, slug: CAMPAIGN_SLUG, amount: amt }) : await donateCampaign(CAMPAIGN_ID, amt);
            txProgress.done(1);
            txProgress.active(2, @json(__('Verifikasi on-chain…')));
            await fetch('/donation/donate', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ slug: CAMPAIGN_SLUG, tx_hash: hash })
            });
            txProgress.done(2);
            setTimeout(() => { txProgress.close(); uiAlert({ title: @json(__('Terima kasih atas donasimu')), message: @json(__('Donasi :amt TLKM tercatat on-chain.')).replace(':amt', amt) + `<br><a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">` + @json(__('Lihat transaksi')) + ` ↗</a>`, type: 'success' }).then(() => location.reload()); }, 400);
        } catch (e) {
            txProgress.close();
            uiAlert({ title: @json(__('Donasi gagal')), message: niceError(e), type: 'error' });
            btn.disabled = false;
        }
    }

    async function doDisburse() {
        const ok = await uiConfirm({
            title: @json(__('Salurkan Dana')),
            message: @json(__('Kirim <b>seluruh saldo campaign</b> ke penerima:')) + `<br><span class="font-mono text-xs break-all">${RECIPIENT}</span><br>` + uiEsc(@json(__('Aksi ini tereksekusi on-chain & tidak bisa dibatalkan.'))),
            confirmText: @json(__('Ya, salurkan')), danger: true
        });
        if (!ok) return;
        const btn = document.getElementById('disbBtn'); btn.disabled = true;
        txProgress.open(@json(__('Menyalurkan Dana')), [@json(__('Memeriksa jaringan')), @json(__('Eksekusi di blockchain (wallet validator)')), @json(__('Mencatat'))]);
        try {
            txProgress.active(0); await checkNetwork(); txProgress.done(0);
            txProgress.active(1, @json(__('Konfirmasi di MetaMask…')));
            const hash = await disburseCampaign(CAMPAIGN_ID, RECIPIENT);
            txProgress.done(1);
            txProgress.active(2, @json(__('Verifikasi on-chain…')));
            await fetch('/donation/disburse', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ slug: CAMPAIGN_SLUG, tx_hash: hash })
            });
            txProgress.done(2);
            setTimeout(() => { txProgress.close(); uiAlert({ title: @json(__('Dana Disalurkan')), message: @json(__('Seluruh saldo campaign dikirim ke penerima.')) + `<br><a href="${EXPLORER_URL}/tx/${hash}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">` + @json(__('Lihat transaksi')) + ` ↗</a>`, type: 'success' }).then(() => location.reload()); }, 400);
        } catch (e) {
            txProgress.close();
            uiAlert({ title: @json(__('Penyaluran gagal')), message: niceError(e), type: 'error' });
            btn.disabled = false;
        }
    }
</script>
@endsection

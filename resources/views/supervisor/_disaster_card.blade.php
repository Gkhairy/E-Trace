{{-- Kartu satu kejadian: penilaian AI + tombol putusan pengawas. --}}
@php
    $sev = $e->ai_severity;
    $bar = $sev === null ? 'bg-slate-300' : ($sev >= 70 ? 'bg-red-500' : ($sev >= 45 ? 'bg-amber-500' : 'bg-slate-400'));
    $decidable = in_array($e->status, ['pending_review', 'rejected'], true);
    $recipient = config('disaster.recipient_wallet');
@endphp
<div class="bg-white border {{ $highlight ? 'border-blue-300 ring-4 ring-blue-50' : 'border-slate-200' }} rounded-2xl shadow-sm p-5">
    @if($e->image_url)
        <div class="relative -mx-5 -mt-5 mb-4 aspect-[16/9] bg-slate-100 overflow-hidden rounded-t-2xl">
            <img src="{{ $e->image_url }}" alt="" class="w-full h-full object-cover" loading="lazy" referrerpolicy="no-referrer" onerror="this.parentElement.remove()">
            @if($e->image_credit)
                <span class="absolute left-2 bottom-2 text-[10px] font-medium text-white bg-black/55 rounded-md px-2 py-0.5">{{ $e->image_credit }}</span>
            @endif
        </div>
    @endif
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide {{ $e->ai_is_disaster ? 'text-blue-600' : 'text-slate-400' }}">
                {{ $highlight ? __('Hasil penilaian AI · ') : '' }}{{ __($e->sourceLabel()) }}{{ $e->type ? ' · ' . __($e->typeLabel()) : '' }}
            </p>
            <p class="font-bold text-slate-900 mt-1 leading-snug">{{ $e->ai_title ?: $e->title }}</p>
            @if($e->location)<p class="text-xs text-slate-500 mt-0.5">{{ $e->location }}</p>@endif
        </div>
        <div class="text-right shrink-0">
            <p class="text-2xl font-extrabold tabular-nums {{ $sev >= 70 ? 'text-red-600' : ($sev >= 45 ? 'text-amber-600' : 'text-slate-500') }}">{{ $sev ?? '—' }}</p>
            <p class="text-[10px] text-slate-400 -mt-0.5">{{ __('skor AI') }}</p>
        </div>
    </div>
    <div class="mt-3 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full {{ $bar }}" style="width: {{ $sev ?? 0 }}%"></div></div>

    <p class="text-sm mt-3 {{ $e->ai_is_disaster ? 'text-slate-700' : 'text-slate-500' }}">
        <b>{{ $e->ai_is_disaster ? __('Bencana terverifikasi AI.') : __('Bukan bencana / dampak kecil.') }}</b> {{ $e->ai_reason }}
    </p>
    @if($e->ai_description)
        <p class="text-xs text-slate-500 mt-2 bg-slate-50 border border-slate-100 rounded-lg p-3">{{ $e->ai_description }}</p>
    @endif
    @if($e->url)
        <a href="{{ $e->url }}" target="_blank" rel="noopener noreferrer" class="inline-block text-xs text-blue-600 hover:underline mt-2 break-all">{{ __('Sumber') }} ↗</a>
    @endif

    @if($e->status === 'duplicate')
        <p class="mt-4 text-sm text-violet-700 bg-violet-50 border border-violet-200 rounded-xl px-3 py-2.5">{{ __('Kejadian yang sama sudah tercatat (#:id), jadi tidak dibuka donasi baru.', ['id' => $e->duplicate_of]) }}</p>
    @elseif($e->status === 'opened' && $e->campaign)
        <a href="/donate/{{ $e->campaign->slug }}" class="mt-4 w-full inline-flex justify-center py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm font-semibold transition">{{ __('Donasi sudah dibuka — lihat') }} ↗</a>
    @elseif($decidable)
        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="/supervisor/disasters/approve" class="flex-1 min-w-[200px] space-y-2">
                @csrf
                <input type="hidden" name="id" value="{{ $e->id }}">
                @if(!$recipient)
                    <input name="recipient" required pattern="0x[a-fA-F0-9]{40}" placeholder="{{ __('Wallet penerima 0x…') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-mono outline-none focus:border-blue-500">
                @endif
                <button class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
                    {{ $e->status === 'rejected' ? __('Tetap buka donasi') : __('Buka donasi') }}
                </button>
            </form>
            <form method="POST" action="/supervisor/disasters/dismiss" class="{{ $recipient ? '' : 'self-end' }}">
                @csrf
                <input type="hidden" name="id" value="{{ $e->id }}">
                <button class="py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">{{ __('Abaikan') }}</button>
            </form>
        </div>
    @endif
</div>

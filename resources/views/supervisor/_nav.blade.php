{{-- Kepala + tab bersama semua halaman pengawas. Angka di tab = yang menunggu putusan. --}}
@php
    $navDisputes = \App\Models\OrderItem::where('status', 'disputed')->count();
    $navHeld = \App\Models\Order::where('settlement_status', 'held')
        ->orWhere(fn ($q) => $q->where('is_insured', true)->where('insurance_status', 'active'))->count();
    $tabs = [
        ['/supervisor',          'Ringkasan',        null,         'M4 5h6v6H4zM14 5h6v4h-6zM14 13h6v6h-6zM4 15h6v4H4z'],
        ['/supervisor/disputes', 'Sengketa',         $navDisputes, 'M12 3v18M5 7h14M7 7l-3 7a3 3 0 006 0L7 7zm10 0l-3 7a3 3 0 006 0l-3-7z'],
        ['/supervisor/held',     'Ditahan AI & Klaim', $navHeld,   'M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['/supervisor/labels',   'Label Entitas',    null,         'M7 7h.01M3 11.6V5a2 2 0 012-2h6.6a2 2 0 011.4.6l7.4 7.4a2 2 0 010 2.8l-6.6 6.6a2 2 0 01-2.8 0L3.6 13a2 2 0 01-.6-1.4z'],
        ['/admin/banners',       'Iklan',            null,         'M11 5.9V19a1 1 0 01-1.8.6L6.6 16H4a1 1 0 01-1-1V9a1 1 0 011-1h2.6l2.6-3.6A1 1 0 0111 5.9zM15.5 8.5a5 5 0 010 7M18.4 5.6a9 9 0 010 12.8'],
    ];
    $current = '/' . trim(request()->path(), '/');
@endphp

<div class="mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.6-4A12 12 0 0112 2.9 12 12 0 013.4 6 12 12 0 003 9c0 5.6 3.8 10.3 9 11.6 5.2-1.3 9-6 9-11.6 0-1-.1-2-.4-3z"/></svg>
        </div>
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-slate-900 leading-tight">Panel Pengawas</h1>
            <p class="text-sm text-slate-500">Putuskan sengketa, tinjau keputusan AI, dan jaga dompet platform tetap sehat.</p>
        </div>
    </div>

    <nav class="mt-5 border-b border-slate-200 -mx-4 px-4 md:mx-0 md:px-0 overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Menu pengawas">
        <ul class="flex gap-1 min-w-max">
            @foreach($tabs as [$href, $label, $badge, $icon])
                @php $on = $current === $href; @endphp
                <li>
                    <a href="{{ $href }}" @if($on) aria-current="page" @endif
                       class="relative flex items-center gap-2 px-3.5 py-2.5 text-sm font-medium rounded-t-lg transition-colors
                              {{ $on ? 'text-slate-900' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/70' }}
                              focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <svg class="w-4 h-4 {{ $on ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                        {{ $label }}
                        @if($badge)
                            <span class="min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center tabular-nums
                                         {{ $on ? 'bg-slate-900 text-white' : 'bg-red-100 text-red-700' }}">{{ $badge > 99 ? '99+' : $badge }}</span>
                        @endif
                        @if($on)<span class="absolute left-2 right-2 -bottom-px h-0.5 rounded-full bg-blue-600"></span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>

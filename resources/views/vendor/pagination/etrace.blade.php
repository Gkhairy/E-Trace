@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between flex-wrap gap-3">
        {{-- Ringkasan jumlah --}}
        <p class="text-xs text-slate-500">
            {{ __('common.showing') }} <span class="font-medium text-slate-700">{{ $paginator->firstItem() ?? 0 }}</span>–<span class="font-medium text-slate-700">{{ $paginator->lastItem() ?? 0 }}</span>
            {{ __('common.of') }} <span class="font-medium text-slate-700">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
        </p>

        <div class="flex items-center gap-1">
            @php
                $base = 'inline-flex items-center justify-center min-w-[36px] h-9 px-3 rounded-lg text-sm font-medium transition select-none';
                $idle = 'bg-white border border-slate-200 text-slate-600 hover:border-blue-400 hover:text-blue-600';
                $active = 'bg-blue-600 border border-blue-600 text-white shadow-sm';
                $disabled = 'bg-slate-50 border border-slate-100 text-slate-300 cursor-not-allowed';
                $dots = 'inline-flex items-center justify-center min-w-[36px] h-9 px-2 text-slate-400 text-sm';
            @endphp

            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $base }} {{ $disabled }}" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} {{ $idle }}" aria-label="Sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @endif

            {{-- Nomor halaman (sembunyikan sebagian di layar kecil) --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $dots }}">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $base }} {{ $active }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $base }} {{ $idle }} {{ (abs($page - $paginator->currentPage()) > 1 && $page != 1 && $page != $paginator->lastPage()) ? 'hidden sm:inline-flex' : '' }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} {{ $idle }}" aria-label="Berikutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="{{ $base }} {{ $disabled }}" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif

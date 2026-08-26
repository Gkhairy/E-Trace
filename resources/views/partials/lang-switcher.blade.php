{{-- Pemilih bahasa (id/en). Menyimpan pilihan di session via /lang/{locale}. --}}
@php $cur = app()->getLocale(); @endphp
<div class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white p-0.5 {{ ($variant ?? '') === 'dark' ? '!bg-white/10 !border-white/20' : '' }}">
    <svg class="w-3.5 h-3.5 ml-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>
    <a href="{{ route('lang.switch', 'id') }}"
       class="px-2 py-1 rounded-full text-[11px] font-semibold transition {{ $cur === 'id' ? 'bg-blue-600 text-white' : 'text-slate-500 hover:text-blue-600' }}">ID</a>
    <a href="{{ route('lang.switch', 'en') }}"
       class="px-2 py-1 rounded-full text-[11px] font-semibold transition {{ $cur === 'en' ? 'bg-blue-600 text-white' : 'text-slate-500 hover:text-blue-600' }}">EN</a>
</div>

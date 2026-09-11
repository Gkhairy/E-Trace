@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Dompet Komunitas</h1>
        <p class="text-sm text-slate-500 mt-1 max-w-2xl">Kelola dana TLKM bersama teman: <b>Jatah Bulanan</b> (limit/anggota) atau <b>Multisig</b> (kirim butuh persetujuan). Dikelola app — konfirmasi tiap aksi dengan PIN.</p>
    </div>
    <div class="flex gap-2 shrink-0">
        <a href="/friends" class="bg-white border border-slate-200 text-slate-700 hover:border-blue-400 px-4 py-2.5 rounded-xl text-sm font-semibold transition">Teman</a>
        <a href="/community/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">+ Buat Dompet</a>
    </div>
</div>

@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm">{{ session('success') }}</div>@endif

@if($wallets->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-3xl p-16 text-center text-slate-500">Belum ada dompet komunitas. Tambah teman lalu buat satu.</div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($wallets as $w)
            <a href="/community/{{ $w->id }}" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 hover:border-blue-500 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-slate-900 truncate">{{ $w->name }}</h3>
                    <span class="text-[11px] px-2 py-0.5 rounded-full {{ $w->isMultisig() ? 'bg-violet-50 text-violet-700 border border-violet-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">{{ $w->modeLabel() }}</span>
                </div>
                @if($w->description)<p class="text-sm text-slate-500 line-clamp-2 mb-2">{{ $w->description }}</p>@endif
                <p class="text-xs text-slate-400 font-mono truncate">{{ $w->address }}</p>
            </a>
        @endforeach
    </div>
@endif
@endsection

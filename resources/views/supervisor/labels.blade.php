@extends('layouts.app')

@section('content')

<div class="flex items-center justify-between mb-2">
    <h1 class="text-2xl font-bold text-slate-900">Label Entitas Terverifikasi</h1>
    <a href="/supervisor/disputes" class="text-sm text-blue-600 hover:underline">← Sengketa</a>
</div>
<p class="text-sm text-slate-500 mb-6 max-w-3xl">Beri label resmi pada wallet (mis. <b>US GOV</b>, <b>Bank Indonesia</b>) agar tampil <b>terverifikasi</b> di Explorer. Hanya pengawas yang bisa memberi label — mencegah klaim identitas palsu.</p>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- FORM --}}
    <div class="lg:col-span-1">
        <form action="/supervisor/labels" method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Wallet</label>
                <input name="address" value="{{ old('address') }}" placeholder="0x…" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Label</label>
                <input name="label" value="{{ old('label') }}" placeholder="US GOV" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kategori</label>
                <select name="category" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
                    <option value="">—</option>
                    @foreach(['government'=>'Pemerintah','institution'=>'Lembaga','exchange'=>'Exchange','individual'=>'Individu'] as $v=>$t)
                        <option value="{{ $v }}" @selected(old('category')===$v)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Catatan (opsional)</label>
                <input name="notes" value="{{ old('notes') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
            </div>
            <button class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Simpan Label</button>
        </form>
    </div>

    {{-- LIST --}}
    <div class="lg:col-span-2">
        @if($labels->isEmpty())
            <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center text-slate-500">Belum ada label.</div>
        @else
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm divide-y divide-slate-100">
                @foreach($labels as $l)
                    <div class="p-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="font-semibold text-slate-900">{{ $l->label }}</span>
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                                @if($l->category)<span class="text-[11px] text-slate-400 capitalize">· {{ $l->category }}</span>@endif
                            </div>
                            <a href="/explorer/{{ $l->address }}" class="text-xs text-blue-600 hover:underline font-mono">{{ substr($l->address,0,12) }}…{{ substr($l->address,-6) }}</a>
                        </div>
                        <form action="/supervisor/labels/delete" method="POST" onsubmit="return confirmSubmit(event, {title: 'Hapus label?', message: 'Label identitas wallet ini akan hilang dari Explorer.', confirmText: 'Hapus', danger: true})">
                            @csrf<input type="hidden" name="id" value="{{ $l->id }}">
                            <button class="text-xs text-slate-400 hover:text-red-600">Hapus</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection

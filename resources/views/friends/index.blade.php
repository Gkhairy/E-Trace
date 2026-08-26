@extends('layouts.app')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Teman</h1>
    <p class="text-sm text-slate-500 mb-6">Tambah teman via No HP / email untuk kirim cepat &amp; undang ke dompet komunitas.</p>

    @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('error') }}</div>@endif

    <form action="/friends" method="POST" class="flex gap-2 mb-6">
        @csrf
        <input name="q" required placeholder="No HP atau email teman" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold">Tambah</button>
    </form>

    @if($friends->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-3xl p-12 text-center text-slate-500">Belum ada teman.</div>
    @else
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm divide-y divide-slate-100">
            @foreach($friends as $f)
                <div class="px-5 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-800">{{ $f->friend->public_name ?: $f->friend->name }}</p>
                        <a href="/explorer/{{ $f->friend->wallet_address }}" class="text-xs text-blue-600 hover:underline font-mono">{{ substr($f->friend->wallet_address,0,8) }}…{{ substr($f->friend->wallet_address,-6) }}</a>
                    </div>
                    <form action="/friends/delete" method="POST" onsubmit="return confirm('Hapus teman ini?')">
                        @csrf<input type="hidden" name="id" value="{{ $f->friend_id }}">
                        <button class="text-xs text-slate-400 hover:text-red-600">Hapus</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Buku Alamat</h1>
    <a href="/products" class="text-sm text-blue-600 hover:underline">← Belanja</a>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
@endif

@if($addresses->isEmpty())
    <div class="bg-white border border-dashed border-slate-300 rounded-3xl p-16 text-center text-slate-500">
        Belum ada alamat tersimpan. Alamat yang kamu isi saat checkout akan muncul di sini.
    </div>
@else
    <div class="space-y-3 max-w-2xl">
        @foreach($addresses as $a)
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">
                        {{ $a->recipient_name }}
                        <span class="text-slate-400 font-normal text-sm">· {{ $a->phone }}</span>
                        @if($a->label)<span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">{{ $a->label }}</span>@endif
                        @if($a->is_default)<span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-medium">Utama</span>@endif
                    </p>
                    <p class="text-sm text-slate-500 mt-1">{{ $a->address }}, {{ $a->city }} {{ $a->postal_code }}</p>
                    @if($a->notes)<p class="text-xs text-slate-400 mt-0.5">{{ $a->notes }}</p>@endif
                </div>
                <div class="flex flex-col items-end gap-2 shrink-0">
                    @unless($a->is_default)
                        <form action="/addresses/default" method="POST">
                            @csrf<input type="hidden" name="id" value="{{ $a->id }}">
                            <button class="text-xs text-blue-600 hover:underline">Jadikan utama</button>
                        </form>
                    @endunless
                    <form action="/addresses/delete" method="POST" onsubmit="return confirmSubmit(event, {title: 'Hapus alamat?', message: 'Alamat ini akan dihapus dari daftar alamat pengirimanmu.', confirmText: 'Hapus', danger: true})">
                        @csrf<input type="hidden" name="id" value="{{ $a->id }}">
                        <button class="text-xs text-slate-400 hover:text-red-600">Hapus</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection

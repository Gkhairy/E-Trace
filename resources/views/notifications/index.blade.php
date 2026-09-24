@extends('layouts.app')

@section('content')

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Notifikasi</h1>
                <p class="text-sm text-slate-500">{{ $unread }} belum dibaca</p>
            </div>
        </div>
        @if($unread > 0)
            <form method="POST" action="/notifications/read-all">
                @csrf
                <button class="text-sm font-medium px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-blue-400 hover:text-blue-600 transition">Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm">{{ session('success') }}</div>
    @endif

    @if($notifications->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center bg-white border border-dashed border-slate-300 rounded-3xl">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4 text-2xl">🔔</div>
            <p class="text-slate-600 font-medium">Belum ada notifikasi</p>
            <p class="text-sm text-slate-400 mt-1">Aktivitas pesanan, pertemanan, donasi, dan komunitas akan muncul di sini.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100">
            @foreach($notifications as $n)
                <a href="/notifications/{{ $n->id }}/read"
                   class="flex items-start gap-3 px-5 py-4 hover:bg-slate-50 transition {{ $n->read_at ? '' : 'bg-blue-50/40' }}">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-lg shrink-0">{{ $n->icon ?: '🔔' }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $n->title }}</p>
                            @unless($n->read_at)<span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>@endunless
                        </div>
                        @if($n->body)<p class="text-sm text-slate-500 mt-0.5 leading-relaxed">{{ $n->body }}</p>@endif
                        <p class="text-[11px] text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>

@endsection

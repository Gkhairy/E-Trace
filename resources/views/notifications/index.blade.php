@extends('layouts.app')

@section('content')
@php
    // Emoji yang tersimpan di notifikasi dipetakan ke ikon Lucide + warnanya, jadi
    // notifikasi lama dan baru sama-sama tampil sebagai ikon (bukan emoji).
    $iconMap = [
        '📝' => ['clipboard-pen', 'amber'],   '💸' => ['banknote', 'emerald'],
        '✅' => ['circle-check', 'emerald'],   '📦' => ['package-check', 'emerald'],
        '🚚' => ['truck', 'blue'],             '👥' => ['users', 'blue'],
        '👋' => ['user-minus', 'slate'],       '🔑' => ['key-round', 'violet'],
        '↩️' => ['undo-2', 'slate'],           '❌' => ['circle-x', 'red'],
        '⚖️' => ['scale', 'red'],              '⚠️' => ['triangle-alert', 'amber'],
        '⛔' => ['octagon-x', 'red'],          '🛡️' => ['shield-check', 'emerald'],
        '🕵️' => ['search-check', 'violet'],    '🤖' => ['bot', 'violet'],
        '⚙️' => ['settings', 'slate'],         '💬' => ['message-circle', 'blue'],
        '🏷️' => ['tag', 'blue'],               '🗳️' => ['vote', 'violet'],
        '💝' => ['heart-handshake', 'red'],    '🤝' => ['handshake', 'blue'],
        '🛒' => ['shopping-cart', 'blue'],
    ];
    $tones = [
        'amber'   => 'bg-amber-100 text-amber-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'blue'    => 'bg-blue-100 text-blue-700',
        'violet'  => 'bg-violet-100 text-violet-700',
        'red'     => 'bg-red-100 text-red-700',
        'slate'   => 'bg-slate-100 text-slate-600',
    ];

    // Keadaan dibaca dari judul: "perlu persetujuanmu" dan "sudah dibayar/selesai" dibuat
    // berbeda jelas (ikon, warna, label), termasuk notifikasi lama yang dulu sama-sama 🛒.
    $view = function ($n) use ($iconMap) {
        $t = mb_strtolower((string) $n->title);
        [$icon, $tone] = $iconMap[$n->icon] ?? ['bell', 'slate'];
        $chip = null;
        if (str_contains($t, 'butuh persetujuan') || str_starts_with($t, 'usulan transfer')) {
            [$icon, $tone, $chip] = ['clipboard-pen', 'amber', ['Perlu persetujuanmu', 'bg-amber-100 text-amber-800']];
        } elseif (str_contains($t, 'belanja komunitas dibayar') || str_contains($t, 'dana komunitas dikirim')) {
            [$icon, $tone, $chip] = ['banknote', 'emerald', ['Sudah dibayar', 'bg-emerald-100 text-emerald-800']];
        } elseif (str_contains($t, 'disetujui')) {
            [$icon, $tone, $chip] = ['circle-check', 'emerald', ['Disetujui', 'bg-emerald-100 text-emerald-800']];
        }
        return [$icon, $tone, $chip];
    };
@endphp

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="bell" class="w-5 h-5" aria-hidden="true"></i>
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
            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4">
                <i data-lucide="bell" class="w-7 h-7" aria-hidden="true"></i>
            </div>
            <p class="text-slate-600 font-medium">Belum ada notifikasi</p>
            <p class="text-sm text-slate-400 mt-1">Aktivitas pesanan, pertemanan, donasi, dan komunitas akan muncul di sini.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100">
            @foreach($notifications as $n)
                @php [$icon, $tone, $chip] = $view($n); @endphp
                <a href="/notifications/{{ $n->id }}/read"
                   class="flex items-start gap-3 px-5 py-4 hover:bg-slate-50 transition {{ $n->read_at ? '' : 'bg-blue-50/40' }}">
                    <div class="w-10 h-10 rounded-xl {{ $tones[$tone] }} flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $icon }}" class="w-5 h-5" aria-hidden="true"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $n->title }}</p>
                            @if($chip)<span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $chip[1] }}">{{ $chip[0] }}</span>@endif
                            @unless($n->read_at)<span class="w-2 h-2 rounded-full bg-blue-500 shrink-0" title="Belum dibaca"></span>@endunless
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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<script>
    if (window.lucide) lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
</script>
@endsection

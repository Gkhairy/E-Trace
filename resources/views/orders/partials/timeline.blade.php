@php
    // Tahapan: Dibuat -> Dikirim -> Selesai, dengan cabang Sengketa & Refund.
    $refunded = $item->status === 'refunded';
    $disputed = $item->status === 'disputed';
    $completed = $item->status === 'completed';
    $shipped  = in_array($item->fulfillment_status, ['shipped', 'delivered'], true) || $completed;

    // Status tiap langkah: done | active | todo
    $s1 = 'done'; // Pesanan Dibuat (selalu, karena sudah dibayar/escrow)
    $s2 = $shipped ? 'done' : ($refunded || $disputed ? 'todo' : 'active'); // Dikirim
    $s3 = $completed ? 'done' : 'todo'; // Selesai

    $dot = fn ($st) => $st === 'done' ? 'bg-green-500 border-green-500'
        : ($st === 'active' ? 'bg-white border-blue-500 ring-2 ring-blue-100' : 'bg-white border-slate-300');
    $line = fn ($st) => $st === 'done' ? 'bg-green-500' : 'bg-slate-200';
    $lbl = fn ($st) => $st === 'todo' ? 'text-slate-400' : 'text-slate-600';
@endphp

<div class="mt-3 pl-[60px] pr-2">
    @if($refunded)
        <div class="inline-flex items-center gap-1.5 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-full px-3 py-1">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Pesanan Dibuat → <b>Refund</b> (dana dikembalikan ke pembeli)
        </div>
    @elseif($disputed)
        <div class="inline-flex items-center gap-1.5 text-xs text-red-600 bg-red-50 border border-red-200 rounded-full px-3 py-1">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Pesanan Dibuat → <b>Sengketa</b> (menunggu keputusan pengawas)
        </div>
    @else
        <div class="flex items-center">
            {{-- Dibuat --}}
            <div class="flex flex-col items-center">
                <span class="w-3 h-3 rounded-full border-2 {{ $dot($s1) }}"></span>
                <span class="text-[10px] mt-1 {{ $lbl($s1) }}">Dibuat</span>
            </div>
            <div class="flex-1 h-0.5 mx-1 -mt-4 {{ $line($s2 === 'done' ? 'done' : 'todo') }}"></div>
            {{-- Dikirim --}}
            <div class="flex flex-col items-center">
                <span class="w-3 h-3 rounded-full border-2 {{ $dot($s2) }}"></span>
                <span class="text-[10px] mt-1 {{ $lbl($s2) }}">Dikirim</span>
            </div>
            <div class="flex-1 h-0.5 mx-1 -mt-4 {{ $line($s3 === 'done' ? 'done' : 'todo') }}"></div>
            {{-- Selesai --}}
            <div class="flex flex-col items-center">
                <span class="w-3 h-3 rounded-full border-2 {{ $dot($s3) }}"></span>
                <span class="text-[10px] mt-1 {{ $lbl($s3) }}">Selesai</span>
            </div>
        </div>
    @endif
</div>

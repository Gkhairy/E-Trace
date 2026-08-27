@extends('layouts.app')

@section('content')
<div class="max-w-2xl">
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
        <a href="/community" class="hover:text-blue-600 transition">Dompet Komunitas</a>
        <span class="text-slate-300">/</span><span class="text-slate-700">Buat</span>
    </nav>
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Buat Dompet Komunitas</h1>
    <p class="text-sm text-slate-500 mb-6">Undang teman, pilih aturan. Dompet &amp; dananya dikelola app (prototipe) — anggota konfirmasi tiap aksi dengan PIN.</p>

    @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form action="/community" method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama dompet</label>
            <input name="name" value="{{ old('name') }}" required placeholder="mis. Kas Kelas 3A" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mode</label>
            <select name="mode" id="modeSel" onchange="onMode()" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
                <option value="A">Jatah Bulanan — tiap anggota punya limit/bulan</option>
                <option value="B">Multisig — kirim butuh persetujuan M dari N</option>
            </select>
        </div>

        <div id="fieldA">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Jatah per anggota / bulan (TLKM)</label>
            <input name="monthly_limit" type="number" min="0" step="any" value="{{ old('monthly_limit', 100000) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
        </div>
        <div id="fieldB" class="hidden">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Ambang persetujuan (M)</label>
            <input name="threshold" type="number" min="1" value="{{ old('threshold', 2) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
            <p class="text-[11px] text-slate-400 mt-1">Butuh M dari penanda tangan yang ditunjuk menyetujui sebelum dana terkirim.</p>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-sm font-medium text-slate-700">Undang teman jadi anggota</label>
                <a href="/friends" class="text-xs text-blue-600 hover:underline">+ Tambah teman</a>
            </div>
            @if($friends->isEmpty())
                <p class="text-sm text-slate-400 bg-slate-50 border border-slate-200 rounded-xl p-3">Belum ada teman. <a href="/friends" class="text-blue-600 hover:underline">Tambah teman</a> dulu (kamu tetap jadi anggota otomatis).</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($friends as $f)
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:border-blue-400 text-sm">
                            <input type="checkbox" name="members[]" value="{{ $f['id'] }}" data-member="{{ $f['id'] }}" onchange="syncSigners()" class="accent-blue-600">
                            {{ $f['name'] }}
                        </label>
                    @endforeach
                </div>
            @endif
            <p class="text-[11px] text-slate-400 mt-1.5">Kamu otomatis jadi anggota.</p>
        </div>

        {{-- PENANDA TANGAN WAJIB (khusus Multisig) — pilih orang spesifik (mis. boss, wakil, sekre) --}}
        <div id="fieldSigners" class="hidden">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Penanda tangan wajib</label>
            <p class="text-[11px] text-slate-400 mb-2">Pilih orang spesifik yang persetujuannya dihitung (mis. boss, wakil, sekre). Hanya mereka yang bisa menyetujui usulan.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {{-- Kamu (pembuat) selalu jadi anggota; default penanda tangan --}}
                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-violet-200 bg-violet-50/40 cursor-pointer text-sm">
                    <input type="checkbox" name="signers[]" value="{{ auth()->id() }}" checked class="accent-violet-600">
                    {{ auth()->user()->public_name ?: auth()->user()->name }} <span class="text-[10px] text-slate-400">(kamu)</span>
                </label>
                @foreach($friends as $f)
                    <label data-signer="{{ $f['id'] }}" class="hidden items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:border-violet-400 text-sm">
                        <input type="checkbox" name="signers[]" value="{{ $f['id'] }}" class="accent-violet-600">
                        {{ $f['name'] }}
                    </label>
                @endforeach
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">Kosong = semua anggota jadi penanda tangan. Ambang M di atas dihitung dari jumlah penanda tangan.</p>
        </div>

        <div class="flex gap-3">
            <a href="/community" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm">Buat Dompet</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function onMode() {
    const b = document.getElementById('modeSel').value === 'B';
    document.getElementById('fieldA').classList.toggle('hidden', b);
    document.getElementById('fieldB').classList.toggle('hidden', !b);
    document.getElementById('fieldSigners').classList.toggle('hidden', !b);
    if (b) syncSigners();
}
// Baris penanda tangan hanya muncul untuk teman yang dipilih jadi anggota.
function syncSigners() {
    document.querySelectorAll('[data-member]').forEach(cb => {
        const row = document.querySelector('[data-signer="' + cb.value + '"]');
        if (!row) return;
        const on = cb.checked;
        row.classList.toggle('hidden', !on);
        row.classList.toggle('flex', on);
        if (!on) { const s = row.querySelector('input'); if (s) s.checked = false; }
    });
}
onMode();
</script>
@endsection

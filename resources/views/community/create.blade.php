@extends('layouts.app')

@section('content')
@php $tlkm = config('chain.tlkm'); @endphp
<div class="max-w-2xl">
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
        <a href="/community" class="hover:text-blue-600 transition">Dompet Komunitas</a>
        <span class="text-slate-300">/</span><span class="text-slate-700">Buat / Daftarkan</span>
    </nav>
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Buat / Daftarkan Dompet Komunitas</h1>
    <p class="text-sm text-slate-500 mb-6">Deploy kontraknya di Remix (panduan di bawah), lalu daftarkan alamatnya di sini.</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- PANDUAN DEPLOY --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6 text-sm text-slate-600">
        <h2 class="font-bold text-slate-900 mb-2">Panduan deploy (Remix)</h2>
        <ol class="list-decimal list-inside space-y-1.5">
            <li>Buka <a href="https://remix.ethereum.org" target="_blank" class="text-blue-600 hover:underline">remix.ethereum.org</a>, buat file dari <code class="bg-slate-100 px-1 rounded">contracts/CommunityAllowanceWallet.sol</code> (Mode A) atau <code class="bg-slate-100 px-1 rounded">CommunityMultisigWallet.sol</code> (Mode B).</li>
            <li>Compile <b>0.8.20</b> (impor OpenZeppelin otomatis di Remix).</li>
            <li>Deploy tab → Environment <b>Injected Provider - MetaMask</b> (Sepolia).</li>
            <li>Konstruktor:
                <ul class="list-disc list-inside ml-4 mt-1">
                    <li><b>Mode A</b>: <code class="bg-slate-100 px-1 rounded">tlkm</code> = <span class="font-mono text-xs">{{ $tlkm }}</span>, <code class="bg-slate-100 px-1 rounded">_owner</code> = wallet penanggung jawab.</li>
                    <li><b>Mode B</b>: <code class="bg-slate-100 px-1 rounded">tlkm</code> = <span class="font-mono text-xs">{{ $tlkm }}</span>, <code class="bg-slate-100 px-1 rounded">_members</code> = ["0x..","0x.."], <code class="bg-slate-100 px-1 rounded">_threshold</code> = M (mis. 3).</li>
                </ul>
            </li>
            <li>Salin alamat kontrak hasil deploy → tempel di form ini.</li>
        </ol>
    </div>

    <form action="/community" method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama dompet</label>
            <input name="name" value="{{ old('name') }}" required placeholder="mis. Kas RT 05" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mode</label>
            <select name="mode" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm">
                <option value="A" @selected(old('mode')==='A')>A — Jatah Bulanan (limit per anggota)</option>
                <option value="B" @selected(old('mode')==='B')>B — Multisig (M-dari-N)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat kontrak (hasil deploy)</label>
            <input name="address" value="{{ old('address') }}" required placeholder="0x…" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm font-mono">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi (opsional)</label>
            <textarea name="description" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 outline-none text-sm resize-none">{{ old('description') }}</textarea>
        </div>
        <div class="flex gap-3">
            <a href="/community" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm">Daftarkan</button>
        </div>
    </form>
</div>
@endsection

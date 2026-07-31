@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/products" class="hover:text-blue-600 transition">Produk</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Tambah Produk</span>
</nav>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Tambah Produk Baru</h1>
    <p class="text-sm text-slate-500 mb-6">Produk akan dijual dengan token TLKM. Wallet penjual otomatis memakai wallet akun kamu.</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="/products/store" method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Produk</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Sepatu Sneakers"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi</label>
            <textarea name="description" rows="4" placeholder="Jelaskan produkmu…"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition resize-none">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Harga (TLKM)</label>
            <div class="relative">
                <input type="number" step="any" min="0" name="price_usdc" value="{{ old('price_usdc') }}" required placeholder="100"
                    class="w-full px-4 py-2.5 pr-16 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition">
                <span class="absolute right-4 top-2.5 text-sm text-slate-400 font-medium">TLKM</span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Stok</label>
            <input type="number" min="0" name="stock" value="{{ old('stock') }}" placeholder="Kosongkan = tak dibatasi"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition">
            <p class="text-xs text-slate-400 mt-1">Biarkan kosong kalau stok tidak dibatasi.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Gambar Produk</label>
            <div class="flex items-center gap-4">
                <div id="preview" class="w-24 h-24 rounded-xl bg-slate-50 border border-dashed border-slate-300 flex items-center justify-center text-slate-400 overflow-hidden shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <input type="file" name="image" accept="image/*" onchange="previewImg(event)"
                    class="text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-sm file:font-medium hover:file:bg-blue-700 file:cursor-pointer">
            </div>
            <p class="text-xs text-slate-400 mt-2">JPG, PNG, atau WEBP. Maksimal 2MB.</p>
        </div>

        <div class="flex gap-3 pt-2">
            <a href="/products" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Simpan Produk</button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
function previewImg(e) {
    const file = e.target.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    document.getElementById('preview').innerHTML = `<img src="${url}" class="w-full h-full object-contain">`;
}
</script>
@endsection

@extends('layouts.app')

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="/seller" class="hover:text-blue-600 transition">Toko Saya</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700">Edit Produk</span>
</nav>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Edit Produk</h1>
    <p class="text-sm text-slate-500 mb-6">Perbarui detail produk. Harga dalam token TLKM.</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="/products/{{ $product->id }}/update" method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Produk</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi</label>
            <textarea name="description" rows="4"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition resize-none">{{ old('description', $product->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Kategori</label>
            <select name="category_id" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition">
                <option value="">— Pilih kategori —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->icon }} {{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Harga (TLKM)</label>
            <div class="relative">
                <input type="number" step="any" min="0" name="price_usdc" value="{{ old('price_usdc', $product->price_usdc) }}" required
                    class="w-full px-4 py-2.5 pr-16 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition">
                <span class="absolute right-4 top-2.5 text-sm text-slate-400 font-medium">TLKM</span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Stok</label>
            <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}" placeholder="Kosongkan = tak dibatasi"
                class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-sm text-slate-900 transition">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Foto Produk</label>

            @php $imgs = $product->images(); @endphp
            @if(count($imgs))
                <p class="text-xs text-slate-500 mb-2">Foto saat ini:</p>
                <div class="flex flex-wrap gap-3 mb-3">
                    @foreach($imgs as $i => $u)
                        <div class="relative w-24 h-24 rounded-xl bg-slate-50 border {{ $i === 0 ? 'border-blue-500' : 'border-slate-200' }} overflow-hidden">
                            <img src="{{ $u }}" onerror="this.src='https://placehold.co/200x200/f1f5f9/94a3b8?text=—'" class="w-full h-full object-contain">
                            @if($i === 0)<span class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[10px] font-semibold text-center py-0.5">Thumbnail</span>@endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div id="preview" class="flex flex-wrap gap-3 mb-3"></div>
            <input type="file" name="images[]" accept="image/*" multiple onchange="previewImgs(event)"
                class="text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-sm file:font-medium hover:file:bg-blue-700 file:cursor-pointer">
            <div class="mt-2 flex items-start gap-2 text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Unggah foto baru untuk <b>mengganti semua foto</b> (maks 6). <b>Foto pertama</b> jadi thumbnail. Biarkan kosong untuk mempertahankan foto saat ini.</span>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <a href="/seller" class="flex-1 text-center py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">Batal</a>
            <button class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm">Simpan Perubahan</button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
function previewImgs(e) {
    const files = Array.from(e.target.files || []).slice(0, 6);
    const box = document.getElementById('preview');
    if (!files.length) { box.innerHTML = ''; return; }
    box.innerHTML = `<p class="w-full text-xs text-blue-600 font-medium">Foto baru (akan menggantikan yang lama):</p>` + files.map((f, i) => `
        <div class="relative w-24 h-24 rounded-xl bg-slate-50 border ${i === 0 ? 'border-blue-500' : 'border-slate-200'} overflow-hidden">
            <img src="${URL.createObjectURL(f)}" class="w-full h-full object-contain">
            ${i === 0 ? '<span class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[10px] font-semibold text-center py-0.5">Thumbnail</span>' : ''}
        </div>`).join('');
}
</script>
@endsection

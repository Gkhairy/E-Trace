{{--
    Form produk bersama untuk tambah & ubah. Nama field TIDAK diubah (name, description,
    category_id, price_usdc, stock, images[]) karena dipakai validasi ProductController.
    Variabel: $product (null saat tambah), $categories, $action, $submitLabel.
--}}
@php
    $isEdit = (bool) $product;
    $imgs = $isEdit ? $product->images() : [];
    $stockVal = old('stock', $product->stock ?? null);
    $unlimited = old('stock_mode', ($stockVal === null || $stockVal === '') ? 'unlimited' : 'limited') === 'unlimited';
    $feePct = (int) config('chain.platform_fee_bps') / 100;
    $rp = (int) config('chain.rp_per_tlkm', 1000);
    $field = 'w-full h-11 px-3.5 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 transition';
    $labels = ['name' => 'Nama produk', 'price_usdc' => 'Harga', 'stock' => 'Stok', 'category_id' => 'Kategori', 'images' => 'Foto', 'images.*' => 'Foto', 'description' => 'Deskripsi'];
@endphp

<div class="flex items-center gap-2 mb-5">
    <a href="/seller/products" class="w-9 h-9 rounded-full ring-1 ring-slate-200 bg-white hover:ring-slate-300 flex items-center justify-center text-slate-600 transition" aria-label="Kembali ke daftar produk">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h2 class="text-xl font-bold text-slate-900">{{ $isEdit ? 'Ubah produk' : 'Tambah produk' }}</h2>
</div>

@if($errors->any())
    <div id="errSummary" role="alert" tabindex="-1" class="mb-6 px-4 py-3 rounded-xl bg-red-50 ring-1 ring-red-200 text-sm text-red-800 focus:outline-none">
        <p class="font-semibold">Ada {{ $errors->count() }} hal yang perlu diperbaiki:</p>
        <ul class="mt-1 list-disc list-inside space-y-0.5">
            @foreach($errors->getMessages() as $key => $msgs)
                @php $anchor = str_starts_with($key, 'images') ? 'images' : $key; @endphp
                <li><a href="#{{ $anchor }}" class="underline">{{ $labels[$key] ?? ucfirst($key) }}</a>: {{ $msgs[0] }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="productForm" novalidate
      class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_20rem] gap-6 items-start">
    @csrf

    <div class="space-y-6 min-w-0">
        {{-- ===== FOTO ===== --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6" aria-labelledby="f-photo">
            <h3 id="f-photo" class="font-bold text-slate-900">Foto produk</h3>
            <p class="text-sm text-slate-500">Foto pertama menjadi sampul. Maksimal 6 foto, masing-masing paling besar 2 MB (JPG, PNG, atau WebP).</p>

            @if(count($imgs))
                <div id="currentPhotos" class="mt-4">
                    <p class="text-xs font-medium text-slate-600 mb-2">Foto sekarang</p>
                    <ul class="flex flex-wrap gap-3">
                        @foreach($imgs as $i => $u)
                            <li class="relative w-20 h-20 rounded-xl bg-slate-50 ring-1 {{ $i === 0 ? 'ring-2 ring-blue-600' : 'ring-slate-200' }} overflow-hidden">
                                <img src="{{ $u }}" alt="Foto {{ $i + 1 }}" onerror="this.src='https://placehold.co/200x200/f1f5f9/94a3b8?text=-'" class="w-full h-full object-contain">
                                @if($i === 0)<span class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[10px] font-semibold text-center py-0.5">Sampul</span>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <label for="images" class="mt-4 flex flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/40 px-4 py-7 text-center cursor-pointer transition focus-within:ring-2 focus-within:ring-blue-500">
                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-sm font-semibold text-blue-700">{{ $isEdit ? 'Ganti foto' : 'Pilih foto' }}</span>
                <span class="text-xs text-slate-500">{{ $isEdit ? 'Foto yang dipilih akan menggantikan semua foto sekarang.' : 'Bisa pilih beberapa sekaligus.' }}</span>
                <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
            </label>
            <p id="imgError" class="mt-2 text-sm text-red-700 {{ $errors->has('images') || $errors->has('images.*') ? '' : 'hidden' }}">{{ $errors->first('images') ?: $errors->first('images.*') }}</p>
            <ul id="newPhotos" class="mt-3 flex flex-wrap gap-3"></ul>
        </section>

        {{-- ===== INFO ===== --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6 space-y-5" aria-labelledby="f-info">
            <div>
                <h3 id="f-info" class="font-bold text-slate-900">Info produk</h3>
                <p class="text-sm text-slate-500">Nama dan deskripsi yang jelas membantu pembeli menemukan produkmu.</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-800 mb-1.5">Nama produk</label>
                <input id="name" type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required maxlength="255" placeholder="Contoh: Kaos katun oversize hitam"
                       aria-describedby="name-help" @error('name') aria-invalid="true" @enderror class="{{ $field }} @error('name') ring-2 ring-red-500 @enderror">
                <p id="name-help" class="mt-1.5 text-xs text-slate-500">Sebutkan jenis barang, merek, dan ciri utama.</p>
                @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-800 mb-1.5">Kategori <span class="font-normal text-slate-500">(opsional)</span></label>
                <select id="category_id" name="category_id" class="{{ $field }}">
                    <option value="">Pilih kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id ?? null) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-baseline justify-between mb-1.5">
                    <label for="description" class="block text-sm font-medium text-slate-800">Deskripsi <span class="font-normal text-slate-500">(opsional)</span></label>
                    <span id="descCount" class="text-xs text-slate-500 tabular-nums"></span>
                </div>
                <textarea id="description" name="description" rows="5" placeholder="Ukuran, bahan, kondisi, isi paket, dan cara perawatan."
                          class="w-full px-3.5 py-2.5 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 transition resize-y">{{ old('description', $product->description ?? '') }}</textarea>
            </div>
        </section>

        {{-- ===== HARGA & STOK ===== --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6 space-y-5" aria-labelledby="f-price">
            <div>
                <h3 id="f-price" class="font-bold text-slate-900">Harga & stok</h3>
                <p class="text-sm text-slate-500">Harga dibayar pembeli dalam token TLKM.</p>
            </div>

            <div>
                <label for="price_usdc" class="block text-sm font-medium text-slate-800 mb-1.5">Harga</label>
                <div class="relative max-w-xs">
                    <input id="price_usdc" type="number" step="any" min="0" name="price_usdc" value="{{ old('price_usdc', $product->price_usdc ?? '') }}" required inputmode="decimal" placeholder="0"
                           aria-describedby="price-help" @error('price_usdc') aria-invalid="true" @enderror class="{{ $field }} pr-16 tabular-nums @error('price_usdc') ring-2 ring-red-500 @enderror">
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500 pointer-events-none">TLKM</span>
                </div>
                <p id="price-help" class="mt-1.5 text-xs text-slate-600">Sekitar <b id="priceRp" class="tabular-nums">Rp 0</b>. Kamu terima <b id="priceNet" class="tabular-nums">0</b> TLKM setelah fee {{ rtrim(rtrim(number_format($feePct, 2), '0'), '.') }}%.</p>
                @error('price_usdc')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <fieldset>
                <legend class="block text-sm font-medium text-slate-800 mb-2">Stok</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach(['unlimited' => 'Tidak dibatasi', 'limited' => 'Jumlah tertentu'] as $val => $lbl)
                        <label class="inline-flex items-center gap-2 h-10 px-3.5 rounded-xl ring-1 cursor-pointer text-sm transition has-[:checked]:ring-2 has-[:checked]:ring-blue-600 has-[:checked]:bg-blue-50 ring-slate-300 hover:ring-slate-400">
                            <input type="radio" name="stock_mode" value="{{ $val }}" class="accent-blue-600" @checked(($val === 'unlimited') === $unlimited)>
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
                <div id="stockWrap" class="mt-3 max-w-[10rem] {{ $unlimited ? 'hidden' : '' }}">
                    <label for="stock" class="sr-only">Jumlah stok</label>
                    <input id="stock" type="number" min="0" step="1" name="stock" value="{{ $unlimited ? '' : $stockVal }}" inputmode="numeric" placeholder="Jumlah"
                           @disabled($unlimited) @error('stock') aria-invalid="true" @enderror class="{{ $field }} tabular-nums @error('stock') ring-2 ring-red-500 @enderror">
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Saat stok 0, produk tetap tampil tapi tidak bisa dibeli.</p>
                @error('stock')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </fieldset>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="/seller/products" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-white ring-1 ring-slate-300 hover:ring-slate-400 text-sm font-semibold text-slate-700 transition">Batal</a>
            <button id="submitBtn" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white text-sm font-semibold shadow-sm transition disabled:opacity-60 disabled:cursor-wait focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">{{ $submitLabel }}</button>
        </div>
    </div>

    {{-- ===== PRATINJAU ===== --}}
    <aside class="lg:sticky lg:top-24" aria-label="Pratinjau produk">
        <p class="text-sm font-semibold text-slate-800 mb-2">Tampilan di pembeli</p>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="aspect-square bg-slate-50 p-5 flex items-center justify-center">
                <img id="pvImg" src="{{ $imgs[0] ?? 'https://placehold.co/400x400/f1f5f9/94a3b8?text=Foto' }}" alt="" class="max-w-full max-h-full object-contain"
                     onerror="this.src='https://placehold.co/400x400/f1f5f9/94a3b8?text=Foto'">
            </div>
            <div class="p-4">
                <p id="pvCat" class="text-xs text-slate-500 min-h-[1rem]"></p>
                <p id="pvName" class="font-semibold text-slate-900 line-clamp-2 leading-snug">Nama produk</p>
                <p class="mt-1 font-bold text-slate-900 tabular-nums"><span id="pvPrice">0</span> <span class="text-xs font-semibold text-blue-700">TLKM</span></p>
                <p id="pvStock" class="mt-1 text-xs text-slate-500"></p>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500 leading-relaxed">Pembeli membayar lewat escrow. Dana masuk ke wallet tokomu setelah pembeli menerima barang.</p>
    </aside>
</form>

@push('form-scripts')
<script>
(function () {
    var RP = {{ $rp }}, FEE = {{ $feePct }};
    var $ = function (id) { return document.getElementById(id); };
    var fmt = function (n, d) { return new Intl.NumberFormat('en-US', { maximumFractionDigits: d == null ? 2 : d }).format(n); };
    var hadPhotos = {{ count($imgs) ? 'true' : 'false' }};

    function price() {
        var v = parseFloat($('price_usdc').value) || 0;
        $('priceRp').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v * RP));
        $('priceNet').textContent = fmt(v * (1 - FEE / 100));
        $('pvPrice').textContent = fmt(v);
    }
    function name() { $('pvName').textContent = $('name').value.trim() || 'Nama produk'; }
    function cat() { var s = $('category_id'); $('pvCat').textContent = s.value ? s.options[s.selectedIndex].text : ''; }
    function desc() { var n = $('description').value.length; $('descCount').textContent = n ? n + ' karakter' : ''; }
    function stock() {
        var unl = document.querySelector('input[name=stock_mode]:checked').value === 'unlimited';
        $('stockWrap').classList.toggle('hidden', unl);
        $('stock').disabled = unl;           // input nonaktif tidak terkirim -> stok kosong = tak dibatasi
        var s = $('stock').value;
        $('pvStock').textContent = unl ? '' : (s === '' ? '' : (+s === 0 ? 'Stok habis' : 'Sisa ' + s));
        if (!unl && document.activeElement && document.activeElement.name === 'stock_mode') $('stock').focus();
    }

    $('images').addEventListener('change', function (e) {
        var files = Array.from(e.target.files || []), err = $('imgError'), box = $('newPhotos');
        var problem = files.length > 6 ? 'Pilih paling banyak 6 foto.'
            : (files.find(function (f) { return f.size > 2 * 1024 * 1024; }) ? 'Ada foto yang lebih besar dari 2 MB. Kecilkan dulu, lalu pilih lagi.' : '');
        err.textContent = problem; err.classList.toggle('hidden', !problem);
        if (problem) { e.target.value = ''; box.innerHTML = ''; return; }
        box.innerHTML = files.map(function (f, i) {
            return '<li class="relative w-20 h-20 rounded-xl bg-slate-50 ring-1 ' + (i === 0 ? 'ring-2 ring-blue-600' : 'ring-slate-200') + ' overflow-hidden">'
                 + '<img src="' + URL.createObjectURL(f) + '" alt="Foto baru ' + (i + 1) + '" class="w-full h-full object-contain">'
                 + (i === 0 ? '<span class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[10px] font-semibold text-center py-0.5">Sampul</span>' : '')
                 + '</li>';
        }).join('');
        if (files[0]) $('pvImg').src = URL.createObjectURL(files[0]);
        if (hadPhotos && $('currentPhotos')) $('currentPhotos').classList.toggle('opacity-40', files.length > 0);
    });

    ['input', 'change'].forEach(function (ev) {
        $('price_usdc').addEventListener(ev, price);
        $('name').addEventListener(ev, name);
        $('category_id').addEventListener(ev, cat);
        $('description').addEventListener(ev, desc);
        $('stock').addEventListener(ev, stock);
    });
    document.querySelectorAll('input[name=stock_mode]').forEach(function (r) { r.addEventListener('change', stock); });

    $('productForm').addEventListener('submit', function (e) {
        // Validasi ringan di browser; server tetap memvalidasi ulang.
        var bad = null;
        if (!$('name').value.trim()) bad = $('name');
        else if ($('price_usdc').value === '' || +$('price_usdc').value < 0) bad = $('price_usdc');
        if (bad) { e.preventDefault(); bad.focus(); bad.reportValidity && bad.setCustomValidity(bad.id === 'name' ? 'Isi nama produk.' : 'Isi harga (0 atau lebih).'); bad.reportValidity(); bad.setCustomValidity(''); return; }
        $('submitBtn').disabled = true;
        $('submitBtn').textContent = 'Menyimpan...';
    });

    price(); name(); cat(); desc(); stock();
    var sum = $('errSummary'); if (sum) sum.focus();
})();
</script>
@endpush

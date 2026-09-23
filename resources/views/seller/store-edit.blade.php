@extends('layouts.app')

@section('content')
@php
    $field = 'w-full h-11 px-3.5 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 transition';
    $labels = ['name' => 'Nama toko', 'description' => 'Deskripsi', 'origin_address' => 'Alamat asal', 'contact_email' => 'Email notifikasi', 'logo' => 'Logo', 'banner' => 'Banner'];
    $logoUrl = $store->logo ? '/store_images/' . $store->logo : null;
    $bannerUrl = $store->banner ? '/store_images/' . $store->banner : null;
@endphp

@include('seller._nav')

@if($errors->any())
    <div id="errSummary" role="alert" tabindex="-1" class="mb-6 px-4 py-3 rounded-xl bg-red-50 ring-1 ring-red-200 text-sm text-red-800 focus:outline-none">
        <p class="font-semibold">Ada {{ $errors->count() }} hal yang perlu diperbaiki:</p>
        <ul class="mt-1 list-disc list-inside space-y-0.5">
            @foreach($errors->getMessages() as $key => $msgs)
                <li><a href="#{{ $key }}" class="underline">{{ $labels[$key] ?? ucfirst($key) }}</a>: {{ $msgs[0] }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="/seller/store" method="POST" enctype="multipart/form-data" id="storeForm"
      class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem] gap-6 items-start">
    @csrf

    <div class="space-y-6 min-w-0">
        {{-- ===== TAMPILAN ===== --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6 space-y-5" aria-labelledby="s-look">
            <div>
                <h2 id="s-look" class="font-bold text-slate-900">Tampilan toko</h2>
                <p class="text-sm text-slate-500">Yang dilihat pembeli saat membuka halaman tokomu.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-[8rem_minmax(0,1fr)] gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-800 mb-1.5">Logo</p>
                    <label for="logo" class="relative block w-32 h-32 rounded-2xl bg-slate-50 ring-1 ring-slate-300 hover:ring-blue-400 overflow-hidden cursor-pointer transition focus-within:ring-2 focus-within:ring-blue-500">
                        <img id="logoPrev" src="{{ $logoUrl ?? '' }}" alt="" class="w-full h-full object-cover {{ $logoUrl ? '' : 'hidden' }}" onerror="this.classList.add('hidden')">
                        <span class="absolute inset-x-0 bottom-0 bg-slate-900/70 text-white text-xs font-semibold text-center py-1.5">Ganti logo</span>
                        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp" class="sr-only" data-preview="logoPrev" data-err="logoErr">
                    </label>
                    <p class="mt-1.5 text-xs text-slate-500">Persegi, maks 2 MB.</p>
                    <p id="logoErr" class="mt-1 text-sm text-red-700 {{ $errors->has('logo') ? '' : 'hidden' }}">{{ $errors->first('logo') }}</p>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800 mb-1.5">Banner</p>
                    <label for="banner" class="relative block h-32 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 ring-1 ring-slate-300 hover:ring-blue-400 overflow-hidden cursor-pointer transition focus-within:ring-2 focus-within:ring-blue-500">
                        <img id="bannerPrev" src="{{ $bannerUrl ?? '' }}" alt="" class="w-full h-full object-cover {{ $bannerUrl ? '' : 'hidden' }}" onerror="this.classList.add('hidden')">
                        <span class="absolute right-2 bottom-2 bg-slate-900/70 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg">Ganti banner</span>
                        <input type="file" id="banner" name="banner" accept="image/jpeg,image/png,image/webp" class="sr-only" data-preview="bannerPrev" data-err="bannerErr">
                    </label>
                    <p class="mt-1.5 text-xs text-slate-500">Lebar, misalnya 1500 x 400 piksel, maks 2 MB.</p>
                    <p id="bannerErr" class="mt-1 text-sm text-red-700 {{ $errors->has('banner') ? '' : 'hidden' }}">{{ $errors->first('banner') }}</p>
                </div>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-800 mb-1.5">Nama toko</label>
                <input id="name" type="text" name="name" value="{{ old('name', $store->name) }}" required maxlength="255"
                       @error('name') aria-invalid="true" @enderror class="{{ $field }} @error('name') ring-2 ring-red-500 @enderror">
                @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-baseline justify-between mb-1.5">
                    <label for="description" class="block text-sm font-medium text-slate-800">Deskripsi <span class="font-normal text-slate-500">(opsional)</span></label>
                    <span id="descCount" class="text-xs text-slate-500 tabular-nums"></span>
                </div>
                <textarea id="description" name="description" rows="3" maxlength="1000" placeholder="Apa yang kamu jual, dan kenapa pembeli perlu belanja di sini?"
                          class="w-full px-3.5 py-2.5 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 transition resize-y">{{ old('description', $store->description) }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- ===== PENGIRIMAN ===== --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6" aria-labelledby="s-ship">
            <h2 id="s-ship" class="font-bold text-slate-900">Pengiriman</h2>
            <p class="text-sm text-slate-500">Dipakai untuk menghitung ongkir ke pembeli.</p>

            <div class="mt-4">
                <div class="flex items-center justify-between gap-3 mb-1.5">
                    <label for="origin_address" class="block text-sm font-medium text-slate-800">Alamat asal pengiriman</label>
                    <button type="button" onclick="pilihOriginPeta()" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-700 hover:text-blue-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10zM12 11a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        Pilih dari peta
                    </button>
                </div>
                <textarea name="origin_address" id="origin_address" rows="2" required maxlength="500" placeholder="Contoh: Jl. Merdeka No. 1, Bandung"
                          aria-describedby="origin-help" @error('origin_address') aria-invalid="true" @enderror
                          class="w-full px-3.5 py-2.5 rounded-xl bg-white ring-1 ring-slate-300 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 transition resize-y @error('origin_address') ring-2 ring-red-500 @enderror">{{ old('origin_address', $store->origin_address) }}</textarea>
                <p id="origin-help" class="mt-1.5 text-xs text-slate-600">Wajib ada <b>nama kota</b>, misalnya Bandung atau Jakarta. Tanpa kota, ongkir tidak bisa dihitung.</p>
                @error('origin_address')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- ===== NOTIFIKASI ===== --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6" aria-labelledby="s-notif">
            <h2 id="s-notif" class="font-bold text-slate-900">Notifikasi pesanan</h2>
            <p class="text-sm text-slate-500">Ke mana kabar "ada pesanan baru" dikirim.</p>
            <div class="mt-4">
                <label for="contact_email" class="block text-sm font-medium text-slate-800 mb-1.5">Email <span class="font-normal text-slate-500">(opsional)</span></label>
                <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $store->contact_email) }}" placeholder="{{ auth()->user()->email }}" autocomplete="email"
                       aria-describedby="email-help" @error('contact_email') aria-invalid="true" @enderror class="{{ $field }} max-w-md @error('contact_email') ring-2 ring-red-500 @enderror">
                <p id="email-help" class="mt-1.5 text-xs text-slate-500">Kosongkan untuk memakai email akunmu. Hanya kamu yang melihat alamat ini.</p>
                @error('contact_email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- ===== PEMBAYARAN (baca saja) ===== --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm p-5 md:p-6" aria-labelledby="s-pay">
            <h2 id="s-pay" class="font-bold text-slate-900">Wallet penerima dana</h2>
            <p class="text-sm text-slate-500">Semua penjualan cair ke wallet ini. Tidak bisa diubah karena tercatat di kontrak pembayaran.</p>
            <div class="mt-4 flex items-center gap-2 max-w-xl">
                <code id="payout" class="flex-1 min-w-0 truncate h-11 leading-[2.75rem] px-3.5 rounded-xl bg-slate-50 ring-1 ring-slate-200 text-sm text-slate-700">{{ $store->payout_wallet }}</code>
                <button type="button" id="copyPayout" class="h-11 px-4 rounded-xl bg-white ring-1 ring-slate-300 hover:ring-slate-400 text-sm font-semibold text-slate-700 transition shrink-0">Salin</button>
            </div>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="/seller" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-white ring-1 ring-slate-300 hover:ring-slate-400 text-sm font-semibold text-slate-700 transition">Batal</a>
            <button id="saveBtn" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white text-sm font-semibold shadow-sm transition disabled:opacity-60 disabled:cursor-wait focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">Simpan pengaturan</button>
        </div>
    </div>

    {{-- ===== PRATINJAU ===== --}}
    <aside class="lg:sticky lg:top-24" aria-label="Pratinjau halaman toko">
        <p class="text-sm font-semibold text-slate-800 mb-2">Tampilan di pembeli</p>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-blue-600 to-blue-500">
                <img id="pvBanner" src="{{ $bannerUrl ?? '' }}" alt="" class="w-full h-full object-cover {{ $bannerUrl ? '' : 'hidden' }}" onerror="this.classList.add('hidden')">
            </div>
            <div class="px-4 pb-4">
                <div class="-mt-8 relative w-16 h-16 rounded-2xl bg-blue-50 ring-4 ring-white overflow-hidden flex items-center justify-center">
                    <span id="pvInitial" class="text-xl font-bold text-blue-600">{{ mb_strtoupper(mb_substr($store->name, 0, 1)) }}</span>
                    <img id="pvLogo" src="{{ $logoUrl ?? '' }}" alt="" class="absolute inset-0 w-full h-full object-cover {{ $logoUrl ? '' : 'hidden' }}" onerror="this.classList.add('hidden')">
                </div>
                <p id="pvName" class="mt-2 font-bold text-slate-900 truncate">{{ $store->name }}</p>
                <p id="pvDesc" class="mt-0.5 text-sm text-slate-600 line-clamp-3"></p>
            </div>
        </div>
    </aside>
</form>

@include('partials.map-picker')
@endsection

@section('scripts')
<script>
function pilihOriginPeta() {
    openMapPicker(function (loc) {
        if (loc.address) {
            document.getElementById('origin_address').value = loc.address;
            showToast('Alamat asal terisi dari peta.', 'success');
        }
    });
}

(function () {
    var $ = function (id) { return document.getElementById(id); };

    function sync() {
        var n = $('name').value.trim();
        $('pvName').textContent = n || 'Nama toko';
        $('pvInitial').textContent = (n || '?').charAt(0).toUpperCase();
        var d = $('description').value;
        $('pvDesc').textContent = d;
        $('descCount').textContent = d.length + ' / 1000';
    }
    ['name', 'description'].forEach(function (id) { $(id).addEventListener('input', sync); });

    // Pratinjau logo/banner + cek ukuran sebelum unggah (server tetap memvalidasi).
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var f = inp.files[0], err = $(inp.dataset.err);
            if (!f) return;
            if (f.size > 2 * 1024 * 1024) {
                err.textContent = 'File lebih besar dari 2 MB. Kecilkan dulu, lalu pilih lagi.';
                err.classList.remove('hidden');
                inp.value = '';
                return;
            }
            err.classList.add('hidden');
            var url = URL.createObjectURL(f);
            [inp.dataset.preview, inp.id === 'logo' ? 'pvLogo' : 'pvBanner'].forEach(function (id) {
                $(id).src = url; $(id).classList.remove('hidden');
            });
        });
    });

    $('copyPayout').addEventListener('click', function () {
        var b = this;
        navigator.clipboard.writeText($('payout').textContent.trim()).then(function () {
            b.textContent = 'Tersalin'; setTimeout(function () { b.textContent = 'Salin'; }, 1500);
        });
    });

    $('storeForm').addEventListener('submit', function () {
        $('saveBtn').disabled = true;
        $('saveBtn').textContent = 'Menyimpan...';
    });

    sync();
    var sum = $('errSummary'); if (sum) sum.focus();
})();
</script>
@endsection

@extends('layouts.app')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3M17 13l2.3 2.3M9 20a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Keranjang</h1>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
@endif

@if($items->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center bg-white border border-dashed border-slate-300 rounded-3xl">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5"/></svg>
        </div>
        <p class="text-slate-600 font-medium">Keranjang kosong</p>
        <a href="/products" class="text-sm text-blue-600 hover:underline mt-2">Belanja sekarang →</a>
    </div>
@else
    @php $total = $items->sum(fn($it) => (float)$it->product->price_usdc * $it->quantity); @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- LIST ITEM --}}
        <div class="lg:col-span-2 space-y-3">
            @foreach($items as $it)
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 flex gap-4 items-center">
                    <a href="/products/{{ $it->product->id }}" class="w-20 h-20 rounded-xl bg-white border border-slate-100 flex items-center justify-center p-1 shrink-0">
                        <img src="{{ $it->product->image ? '/product_images/'.$it->product->image : 'https://placehold.co/100x100/f1f5f9/94a3b8?text=—' }}"
                             onerror="this.src='https://placehold.co/100x100/f1f5f9/94a3b8?text=—'"
                             class="max-w-full max-h-full object-contain">
                    </a>
                    <div class="flex-1 min-w-0">
                        <a href="/products/{{ $it->product->id }}" class="font-semibold text-slate-800 hover:text-blue-600 line-clamp-1">{{ $it->product->name }}</a>
                        <p class="text-sm text-blue-600 font-bold mt-0.5">{{ rtrim(rtrim(number_format($it->product->price_usdc, 2), '0'), '.') }} TLKM</p>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5 truncate">Seller {{ substr($it->product->seller_wallet, 0, 6) }}…{{ substr($it->product->seller_wallet, -4) }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden">
                            <button onclick="cartUpdate({{ $it->id }}, {{ $it->quantity - 1 }})" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100">−</button>
                            <span class="w-9 text-center text-sm">{{ $it->quantity }}</span>
                            <button onclick="cartUpdate({{ $it->id }}, {{ $it->quantity + 1 }})" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100">+</button>
                        </div>
                        <button onclick="cartRemove({{ $it->id }})" class="text-xs text-slate-400 hover:text-red-600 transition">Hapus</button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- RINGKASAN --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 lg:sticky lg:top-24">
                <h3 class="font-semibold text-slate-900 mb-4">Ringkasan</h3>
                <div class="flex justify-between text-sm text-slate-600 mb-2">
                    <span>Item</span><span>{{ $items->sum('quantity') }}</span>
                </div>
                <div class="flex justify-between items-end border-t border-slate-100 pt-3 mt-3">
                    <span class="text-sm text-slate-500">Total</span>
                    <span class="text-2xl font-extrabold text-slate-900">{{ rtrim(rtrim(number_format($total, 2), '0'), '.') }} <span class="text-sm text-blue-600 font-semibold">TLKM</span></span>
                </div>
                <a href="/checkout" class="mt-5 w-full inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-sm font-bold transition shadow-sm">
                    Lanjut Checkout →
                </a>
                <a href="/products" class="mt-2 w-full inline-flex items-center justify-center text-slate-500 hover:text-blue-600 py-2 text-sm transition">Lanjut belanja</a>
            </div>
        </div>
    </div>
@endif

@endsection

@section('scripts')
<script>
async function cartUpdate(id, quantity) {
    if (quantity < 1) return cartRemove(id);
    await fetch('/cart/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ id, quantity })
    });
    location.reload();
}
async function cartRemove(id) {
    const ok = await uiConfirm({ title: 'Hapus item', message: 'Hapus item ini dari keranjang?', confirmText: 'Hapus', danger: true });
    if (!ok) return;
    await fetch('/cart/remove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ id })
    });
    location.reload();
}
</script>
@endsection

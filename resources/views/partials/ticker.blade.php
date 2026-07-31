{{-- ============================================================
     TICKER HARGA CRYPTO (marquee). Data: GET /api/ticker (publik, cache 3 mnt).
     Aman bila API gagal (strip tetap tersembunyi). Animasi CSS transform saja.

     Varian (opsional, default 'bar'):
       - 'bar'  : strip gelap full-width (dipakai di welcome, fixed top)
       - 'card' : kartu PUTIH rounded, selebar container pemanggil (katalog/toko)
============================================================ --}}
@php
    $variant = $variant ?? 'bar';
    $wrapClass = $variant === 'card'
        ? 'hidden h-11 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden select-none'
        : 'hidden w-full h-10 bg-slate-900 border-b border-slate-800 overflow-hidden select-none';
    $trackH = $variant === 'card' ? 'h-11' : 'h-10';
@endphp

<div id="cryptoTicker" data-variant="{{ $variant }}" class="{{ $wrapClass }}">
    <div id="tickerTrack" class="ticker-track flex items-center {{ $trackH }} whitespace-nowrap"></div>
</div>

<style>
    .ticker-track { will-change: transform; animation: tickerScroll 45s linear infinite; }
    #cryptoTicker:hover .ticker-track { animation-play-state: paused; } /* pause on hover */
    @keyframes tickerScroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    @media (prefers-reduced-motion: reduce) {
        .ticker-track { animation: none; transform: none; }
        #cryptoTicker { overflow-x: auto; } /* tetap bisa digeser manual */
    }
</style>

<script>
(function () {
    const wrap  = document.getElementById('cryptoTicker');
    const track = document.getElementById('tickerTrack');
    if (!wrap || !track) return;

    // Warna item menyesuaikan varian (kartu putih vs strip gelap).
    const isCard = wrap.dataset.variant === 'card';
    const col = isCard
        ? { sym: 'text-slate-900', price: 'text-slate-600', up: 'text-emerald-600', down: 'text-red-600', sep: 'border-slate-200' }
        : { sym: 'text-white',     price: 'text-slate-300', up: 'text-emerald-400', down: 'text-red-400', sep: 'border-white/10' };

    const fmtPrice = (p) => p == null ? '—'
        : '$' + Number(p).toLocaleString('en-US', { maximumFractionDigits: p < 1 ? 6 : 2 });

    const itemHtml = (c) => {
        const up  = (c.change ?? 0) >= 0;
        const chg = c.change == null ? '' : (up ? '▲ ' : '▼ ') + Math.abs(c.change).toFixed(2) + '%';
        const logo = c.id ? `https://s2.coinmarketcap.com/static/img/coins/32x32/${c.id}.png` : '';
        return `<span class="inline-flex items-center gap-2 px-5 border-r ${col.sep} text-sm">
                    ${logo ? `<img src="${logo}" class="w-4 h-4 rounded-full" loading="lazy" onerror="this.style.display='none'">` : ''}
                    <span class="font-semibold ${col.sym}">${c.symbol}</span>
                    <span class="${col.price} tabular-nums">${fmtPrice(c.price)}</span>
                    <span class="${up ? col.up : col.down} tabular-nums font-medium">${chg}</span>
                </span>`;
    };

    fetch('/api/ticker', { headers: { 'Accept': 'application/json' } })
        .then(r => r.ok ? r.json() : { data: [] })
        .then(({ data }) => {
            if (!Array.isArray(data) || data.length === 0) return; // API gagal -> strip tetap hidden, halaman aman
            const html = data.map(itemHtml).join('');
            track.innerHTML = html + html;   // digandakan -> loop mulus (translateX 0 -> -50%)
            wrap.classList.remove('hidden');
        })
        .catch(() => { /* diam: strip tetap tersembunyi, halaman tidak error */ });
})();
</script>

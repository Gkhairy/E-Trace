{{-- ============ PEMILIH LOKASI DARI PETA (Leaflet + OpenStreetMap, gratis) ============ --}}
{{-- Include sekali per halaman. Panggil: openMapPicker(cb, {lat,lng}) --}}
<div id="mapPickerModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col" style="max-height:90vh">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">Pilih Lokasi dari Peta</h3>
            <button type="button" onclick="closeMapPicker()" class="text-slate-400 hover:text-slate-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-3">
            <p class="text-xs text-slate-500 mb-2">Geser peta atau klik untuk memindahkan pin ke lokasimu. Tekan "Gunakan lokasiku" untuk memakai GPS.</p>
            <div id="mapPickerMap" class="w-full rounded-xl overflow-hidden border border-slate-200" style="height:340px"></div>
            <p id="mapPickerAddr" class="text-xs text-slate-500 mt-2 min-h-[2.5em]">Memuat peta…</p>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 flex gap-3">
            <button type="button" onclick="mapPickerLocate()" class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm font-medium transition">📍 Gunakan lokasiku</button>
            <button type="button" onclick="mapPickerConfirm()" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">Pakai lokasi ini</button>
        </div>
    </div>
</div>

<script>
(function () {
    let map = null, marker = null, cb = null, current = null, loading = false;

    // Muat Leaflet (CSS+JS) sekali, dari CDN.
    function ensureLeaflet() {
        return new Promise((resolve, reject) => {
            if (window.L) return resolve();
            if (!document.getElementById('leaflet-css')) {
                const l = document.createElement('link');
                l.id = 'leaflet-css'; l.rel = 'stylesheet';
                l.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(l);
            }
            const s = document.createElement('script');
            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.onload = () => resolve();
            s.onerror = () => reject(new Error('Gagal memuat peta.'));
            document.head.appendChild(s);
        });
    }

    async function reverseGeocode(lat, lng) {
        const el = document.getElementById('mapPickerAddr');
        el.textContent = 'Mencari alamat…';
        try {
            const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=id`, { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            const a = d.address || {};
            const city = a.city || a.town || a.municipality || a.county || a.city_district || a.village || '';
            const postcode = a.postcode || '';
            const display = d.display_name || '';
            current = { lat, lng, address: display, city, postcode };
            el.textContent = display || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        } catch (_) {
            current = { lat, lng, address: '', city: '', postcode: '' };
            el.textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)} (alamat tak terbaca)`;
        }
    }

    function setPin(lat, lng) {
        if (marker) marker.setLatLng([lat, lng]);
        current = { lat, lng, address: '', city: '', postcode: '' };
        reverseGeocode(lat, lng);
    }

    window.openMapPicker = async function (callback, opts) {
        cb = callback;
        document.getElementById('mapPickerModal').classList.remove('hidden');
        if (loading) return;
        try {
            loading = true;
            await ensureLeaflet();
            const startLat = (opts && opts.lat) || -6.2088, startLng = (opts && opts.lng) || 106.8456; // default Jakarta
            if (!map) {
                map = L.map('mapPickerMap').setView([startLat, startLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
                marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
                marker.on('dragend', () => { const p = marker.getLatLng(); setPin(p.lat, p.lng); });
                map.on('click', (e) => setPin(e.latlng.lat, e.latlng.lng));
            } else {
                map.setView([startLat, startLng], 13);
                marker.setLatLng([startLat, startLng]);
            }
            setTimeout(() => map.invalidateSize(), 200);
            setPin(startLat, startLng);
        } catch (e) {
            document.getElementById('mapPickerAddr').textContent = 'Gagal memuat peta. Cek koneksi internet.';
        } finally { loading = false; }
    };

    window.closeMapPicker = function () { document.getElementById('mapPickerModal').classList.add('hidden'); };

    window.mapPickerLocate = function () {
        if (!navigator.geolocation) { showToast && showToast('GPS tidak tersedia.', 'warn'); return; }
        navigator.geolocation.getCurrentPosition(
            (pos) => { const { latitude, longitude } = pos.coords; if (map) { map.setView([latitude, longitude], 16); } setPin(latitude, longitude); },
            () => { showToast && showToast('Tidak bisa mengambil lokasi GPS.', 'warn'); },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    };

    window.mapPickerConfirm = function () {
        if (cb && current) cb(current);
        closeMapPicker();
    };
})();
</script>

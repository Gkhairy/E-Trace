{{-- ============ WIDGET CHAT (penjual ↔ pembeli, + nawar) ============ --}}
<div id="chatLauncher" onclick="chatToggle()" title="Chat"
     class="fixed bottom-5 right-24 z-50 w-14 h-14 rounded-full bg-slate-900 hover:bg-slate-800 text-white shadow-lg flex items-center justify-center cursor-pointer transition">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5M21 12a8 8 0 01-11.6 7.1L4 20l1-4.3A8 8 0 1121 12z"/></svg>
    <span id="chatLauncherBadge" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center hidden">0</span>
</div>

<div id="chatPanelWrap" class="hidden fixed bottom-5 right-5 z-50 w-[92vw] max-w-sm h-[70vh] max-h-[560px] bg-white border border-slate-200 rounded-2xl shadow-2xl flex flex-col overflow-hidden">
    {{-- Header --}}
    <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2 min-w-0">
            <button id="chatBackBtn" onclick="chatShowList()" class="hidden text-white/80 hover:text-white shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span id="chatTitle" class="font-semibold text-sm truncate">Chat</span>
        </div>
        <button onclick="chatToggle()" class="text-white/80 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>

    {{-- Daftar percakapan --}}
    <div id="chatList" class="flex-1 overflow-y-auto bg-slate-50"></div>

    {{-- Thread pesan --}}
    <div id="chatThread" class="hidden flex-1 overflow-y-auto p-3 space-y-2 bg-slate-50 text-sm"></div>
    <div id="chatInputBar" class="hidden p-2.5 border-t border-slate-100 flex items-center gap-2">
        <input id="chatMsgInput" type="text" placeholder="Tulis pesan…" onkeydown="if(event.key==='Enter')chatSend()"
               class="flex-1 bg-slate-100 focus:bg-white text-sm rounded-full px-4 py-2 outline-none border border-slate-200 focus:border-blue-500">
        <button onclick="chatSend()" class="w-9 h-9 rounded-full bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </div>
</div>

<script>
(function () {
    let openPartner = null;   // id lawan bicara aktif
    let openName = '';
    let pollTimer = null;

    window.chatToggle = function () {
        const wrap = document.getElementById('chatPanelWrap');
        const show = wrap.classList.contains('hidden');
        // Hanya satu panel di pojok kanan bawah: tutup EVA bila terbuka, lalu
        // sembunyikan kedua tombol bulat selama panel chat ini terbuka.
        const eva = document.getElementById('chatPanel');
        if (show && eva && !eva.classList.contains('hidden')) eva.classList.add('hidden');
        document.getElementById('chatLauncher').classList.toggle('hidden', show);
        const evaFab = document.getElementById('chatFab');
        if (evaFab) evaFab.classList.toggle('hidden', show);
        wrap.classList.toggle('hidden', !show);
        if (show) { chatShowList(); chatLoadConversations(); }
        else if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    };

    window.chatShowList = function () {
        openPartner = null;
        document.getElementById('chatList').classList.remove('hidden');
        document.getElementById('chatThread').classList.add('hidden');
        document.getElementById('chatInputBar').classList.add('hidden');
        document.getElementById('chatBackBtn').classList.add('hidden');
        document.getElementById('chatTitle').textContent = 'Chat';
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    };

    async function chatLoadConversations() {
        const box = document.getElementById('chatList');
        try {
            const d = await (await fetch('/chat/conversations', { headers: { 'Accept': 'application/json' } })).json();
            if (!d.conversations.length) { box.innerHTML = '<p class="text-center text-sm text-slate-400 py-10">Belum ada percakapan.<br>Mulai chat dari halaman produk.</p>'; return; }
            box.innerHTML = d.conversations.map(c => `
                <button onclick="chatOpen(${c.partner_id}, ${JSON.stringify(c.name).replace(/"/g,'&quot;')})" class="w-full text-left px-4 py-3 border-b border-slate-100 hover:bg-white transition flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold shrink-0">${(c.name||'?').slice(0,1).toUpperCase()}</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-slate-800 truncate">${c.name}</span>
                            <span class="text-[10px] text-slate-400 shrink-0">${c.at}</span>
                        </div>
                        <p class="text-xs text-slate-500 truncate">${c.last||''}</p>
                    </div>
                    ${c.unread ? `<span class="w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center shrink-0">${c.unread}</span>` : ''}
                </button>`).join('');
        } catch (_) { box.innerHTML = '<p class="text-center text-sm text-red-400 py-10">Gagal memuat.</p>'; }
    }

    window.chatOpen = async function (partnerId, name) {
        openPartner = partnerId; openName = name || 'Pengguna';
        document.getElementById('chatList').classList.add('hidden');
        document.getElementById('chatThread').classList.remove('hidden');
        document.getElementById('chatInputBar').classList.remove('hidden');
        document.getElementById('chatBackBtn').classList.remove('hidden');
        document.getElementById('chatTitle').textContent = openName;
        await chatLoadThread();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(chatLoadThread, 8000);
    };

    async function chatLoadThread() {
        if (!openPartner) return;
        const box = document.getElementById('chatThread');
        try {
            const d = await (await fetch('/chat/thread?with=' + openPartner, { headers: { 'Accept': 'application/json' } })).json();
            box.innerHTML = d.messages.map(m => {
                if (m.type === 'offer') {
                    const badge = m.offer_status === 'accepted' ? '<span class="text-green-600">✓ Diterima</span>'
                        : m.offer_status === 'rejected' ? '<span class="text-red-500">✗ Ditolak</span>'
                        : (!m.mine ? `<div class="flex gap-2 mt-2"><button onclick="chatRespond(${m.id},'accept')" class="px-2 py-1 rounded-lg bg-green-600 text-white text-[11px] font-semibold">Terima</button><button onclick="chatRespond(${m.id},'reject')" class="px-2 py-1 rounded-lg bg-white border border-slate-300 text-slate-600 text-[11px]">Tolak</button></div>` : '<span class="text-amber-600">Menunggu…</span>');
                    return `<div class="flex ${m.mine?'justify-end':'justify-start'}"><div class="max-w-[80%] rounded-2xl px-3 py-2 border ${m.mine?'bg-amber-50 border-amber-200':'bg-white border-slate-200'}">
                        <p class="text-[11px] text-amber-700 font-semibold">🏷️ Tawaran${m.product?(' · '+m.product.name):''}</p>
                        <p class="text-sm font-bold text-slate-800">${m.offer} TLKM</p>
                        <p class="text-[11px] mt-1">${badge}</p>
                        <p class="text-[10px] text-slate-400 mt-1">${m.at}</p>
                    </div></div>`;
                }
                return `<div class="flex ${m.mine?'justify-end':'justify-start'}"><div class="max-w-[80%] rounded-2xl px-3 py-2 ${m.mine?'bg-blue-600 text-white':'bg-white border border-slate-200 text-slate-700'}">
                    <p>${(m.body||'').replace(/</g,'&lt;')}</p>
                    <p class="text-[10px] ${m.mine?'text-blue-100':'text-slate-400'} mt-0.5 text-right">${m.at}</p>
                </div></div>`;
            }).join('');
            box.scrollTop = box.scrollHeight;
        } catch (_) {}
    }

    window.chatSend = async function () {
        const inp = document.getElementById('chatMsgInput');
        const body = inp.value.trim();
        if (!body || !openPartner) return;
        inp.value = '';
        try {
            await fetch('/chat/send', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ receiver_id: openPartner, body }) });
            chatLoadThread();
        } catch (_) { showToast('Gagal mengirim pesan.', 'error'); }
    };

    window.chatRespond = async function (id, action) {
        try {
            await fetch('/chat/respond', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ message_id: id, action }) });
            chatLoadThread();
        } catch (_) { showToast('Gagal.', 'error'); }
    };

    // Dipanggil dari halaman produk: mulai chat dengan penjual.
    window.startChat = function (partnerId, name) {
        if (!partnerId) return;
        const wrap = document.getElementById('chatPanelWrap');
        if (wrap.classList.contains('hidden')) chatToggle(); // tutup EVA & sembunyikan tombol bulat
        chatOpen(partnerId, name);
    };

    // Nawar produk: kirim tawaran lalu buka thread dengan penjual.
    window.nawarProduct = async function (productId, productName) {
        const amt = await uiPrompt({ title: 'Nawar Harga', label: 'Tawaranmu untuk "' + productName + '" (TLKM):', type: 'number', min: 0, step: 'any', placeholder: '0', confirmText: 'Kirim Tawaran' });
        if (amt === null || +amt <= 0) return;
        try {
            const d = await (await fetch('/chat/offer', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ product_id: productId, amount: amt }) })).json();
            if (d.success) { showToast('Tawaran terkirim ke penjual.', 'success'); startChat(d.seller_id, 'Penjual'); }
        } catch (e) { showToast('Gagal mengirim tawaran.', 'error'); }
    };

    // Badge unread (polling ringan).
    async function chatPollBadge() {
        try {
            const d = await (await fetch('/chat/unread-count', { headers: { 'Accept': 'application/json' } })).json();
            const b = document.getElementById('chatLauncherBadge');
            if (d.count > 0) { b.textContent = d.count > 9 ? '9+' : d.count; b.classList.remove('hidden'); }
            else b.classList.add('hidden');
        } catch (_) {}
    }
    chatPollBadge();
    setInterval(chatPollBadge, 30000);
})();
</script>

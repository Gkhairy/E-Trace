<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\WalletLabel;
use App\Support\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:500',
            'history' => 'nullable|array|max:8',   // riwayat singkat {role, content}
        ]);

        // ===== Pertanyaan transparansi Explorer (pengeluaran/pemasukan sebuah entitas) =====
        // Dijawab DETERMINISTIK dari database (akurat & tanpa token OpenAI). Kalau cocok,
        // langsung balas + tautan ke halaman Explorer wallet terkait.
        if ($insight = $this->explorerInsight($data['message'])) {
            return response()->json($insight, 200);
        }

        $key = config('services.openai.key');
        if (!$key) {
            return response()->json([
                'reply' => 'Chatbot belum dikonfigurasi. Set OPENAI_API_KEY di .env dulu ya.',
                'products' => [],
            ], 200);
        }

        // ===== Cari produk dari kata kunci (LIKE, maks 5) untuk konteks faktual =====
        $products = $this->searchProducts($data['message']);
        $productContext = '';
        if ($products->isNotEmpty()) {
            $productContext = "\n\nProduk relevan di E-Trace saat ini (pakai ini untuk jawaban faktual, sertakan nama & harga):\n"
                . $products->map(fn ($p) => "- {$p['name']} — {$p['price']} TLKM ({$p['url']})")->implode("\n");
        }

        $system = <<<'SYS'
Kamu "EVA", asisten resmi marketplace blockchain E-Trace. Tugasmu menjelaskan cara kerja
aplikasi DAN konsep blockchain/crypto yang terkait, dengan bahasa sederhana untuk orang awam.

# TENTANG E-TRACE
Marketplace e-commerce di jaringan BNB Smart Chain Testnet (testnet — token uji coba, BUKAN uang
asli). Bayar pakai token TLKM (BEP-20). Inti idenya: dana pembeli ditahan smart contract "escrow"
dan baru lepas ke penjual setelah pembeli mengonfirmasi barang diterima — jadi tak perlu saling
percaya, cukup percaya kode. Semua pembayaran tercatat di blockchain & bisa dicek siapa saja.

# FITUR UTAMA
1. Escrow trustless — dana ditahan kontrak; lepas ke penjual saat pembeli "Konfirmasi Terima";
   bisa refund bila penjual tak mengirim.
2. Keranjang multi-penjual — satu keranjang banyak penjual; escrow dipisah per item, konfirmasi
   satu item tak melepas dana penjual lain.
3. Wallet — embedded wallet (daftar pakai email/HP + PIN, tanpa seed phrase) atau MetaMask.
   Login tanpa password; transaksi berikutnya cukup PIN.
4. Paylater (Pinjam & Danai) — fitur kredit/pinjaman on-chain dua sisi:
   • Pinjam: belanja sekarang, bayar nanti — jaminkan aset untuk dapat limit TLKM, lunasi sesuai jangka.
   • Danai: setor TLKM ke pool likuiditas, dapat bagi hasil (nisbah) dari bunga peminjam; jangka
     fleksibel / 30 / 90 hari, bagi hasil naik seiring jangka.
   Bunga, pool, dan jangka semua tercatat di smart contract.
5. AI Auto-Settlement & Garansi Tepat Waktu — sistem otomatis membaca status pengiriman:
   penjual tak mengirim dalam 3 hari → dana otomatis dikembalikan (refund); barang sudah diterima
   tapi lupa dikonfirmasi beberapa hari → otomatis diselesaikan ke penjual. Ada opsi asuransi ongkir:
   bila telat karena penjual/kurir, ongkir diganti dari pool. Estimasi tiba dihitung dari jarak.
6. Explorer transparansi — halaman publik untuk melihat transaksi, transfer TLKM on-chain, toko
   teratas, dan entitas terverifikasi.
7. Donasi & dompet komunitas — donasi tercatat on-chain (0% fee); dompet komunitas butuh persetujuan
   beberapa orang (multisig) sebelum dana cair.
8. Keamanan — OTP email saat daftar, opsi 2FA, gerbang PIN untuk aksi sensitif, proteksi anti-bot.
9. Peran: pembeli, penjual, pengawas (penengah sengketa). Dwibahasa (Indonesia/Inggris).

# BIAYA
Platform ambil fee 1% dari nilai transaksi, ditanggung penjual (tidak menambah harga ke pembeli).
Donasi & dompet komunitas gratis (0%).

# ISTILAH UNTUK AWAM (jelaskan sederhana bila ditanya)
- Blockchain: buku besar digital publik yang catatannya tak bisa diubah atau dihapus.
- Smart contract: program otomatis di blockchain; di E-Trace ia yang menahan & melepas dana escrow tanpa perantara.
- Token TLKM: alat bayar di E-Trace (seperti saldo), standar BEP-20.
- Wallet/dompet: tempat menyimpan token; PIN menggantikan password.
- Gas (tBNB): biaya kecil agar jaringan memproses transaksi (di testnet sifatnya uji coba).
- On-chain: tercatat di blockchain, bisa diverifikasi siapa saja di block explorer (BscScan).
- Escrow: dana "ditahan di tengah" sampai syarat terpenuhi.

# ATURAN
- Fokus menjawab tentang E-Trace dan konsep blockchain/crypto yang relevan dengan pemakaian aplikasi.
  Boleh dan dianjurkan menjelaskan dasar blockchain/escrow/wallet/Paylater secara sederhana untuk awam.
- Untuk pertanyaan yang benar-benar di luar topik (mis. resep masakan, PR sekolah), tolak dengan sopan
  dan arahkan kembali ke E-Trace.
- JANGAN memberi nasihat investasi/finansial atau prediksi harga token.
- Jawab ringkas, ramah, Bahasa Indonesia; pakai langkah bernomor saat menjelaskan alur. JANGAN mengarang
  fitur atau angka yang tidak ada.
- Ini testnet & smart contract-nya belum diaudit — ingatkan bila relevan (mis. ditanya soal keamanan dana asli).
- Kalau ada daftar produk di konteks, sebutkan nama & harganya.
- Untuk pertanyaan pengeluaran/pemasukan/transaksi sebuah toko atau entitas terverifikasi, sebutkan nama
  entitas & rentang waktunya lalu arahkan ke halaman Explorer wallet tersebut. Jangan mengarang angka.
SYS;

        $messages = [['role' => 'system', 'content' => $system . $productContext]];
        foreach (($data['history'] ?? []) as $h) {
            if (in_array(($h['role'] ?? ''), ['user', 'assistant'], true) && !empty($h['content'])) {
                $messages[] = ['role' => $h['role'], 'content' => mb_substr((string) $h['content'], 0, 800)];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $data['message']];

        try {
            $res = Http::withToken($key)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => config('services.openai.model', 'gpt-4o-mini'),
                'messages'    => $messages,
                'temperature' => 0.3,
                'max_tokens'  => 550,
            ]);
            if (!$res->ok()) {
                return response()->json(['reply' => 'Maaf, asisten sedang sibuk. Coba lagi sebentar.', 'products' => $products], 200);
            }
            $reply = $res->json('choices.0.message.content') ?: 'Maaf, aku belum bisa menjawab itu.';
        } catch (\Throwable $e) {
            return response()->json(['reply' => 'Maaf, terjadi kendala menghubungi asisten.', 'products' => $products], 200);
        }

        return response()->json(['reply' => $reply, 'products' => $products], 200);
    }

    /**
     * Jawaban transparansi Explorer: deteksi pertanyaan pengeluaran/pemasukan sebuah
     * entitas, resolusi ke wallet, hitung total untuk rentang waktu, balas + tautan
     * ke /explorer/{address}. Kembalikan array respons JSON, atau null bila bukan
     * pertanyaan seperti ini (biar diteruskan ke alur chatbot biasa).
     */
    private function explorerInsight(string $message): ?array
    {
        $lc = mb_strtolower($message);

        // Harus terlihat seperti pertanyaan finansial/transaksi.
        $financeHint = preg_match('/\b(pengeluaran|pemasukan|pendapatan|belanja|habis|transaksi|penjualan|terjual|spending|income|keluar|beli|dibelanjakan|dana)\b/u', $lc);
        if (!$financeHint) {
            return null;
        }

        // 1) Resolusi entitas -> alamat wallet.
        $entity = $this->resolveEntity($message);
        if (!$entity) {
            return null; // biar chatbot biasa yang menjawab / minta klarifikasi
        }
        $addr = $entity['address'];

        // 2) Rentang waktu.
        [$rangeKey, $rangeLabel, $since] = $this->parseRange($lc);

        // 3) Jenis angka yang diminta.
        $wantsIncome  = (bool) preg_match('/\b(pemasukan|pendapatan|penjualan|terjual|income|masuk|laku)\b/u', $lc);
        $wantsSpend   = (bool) preg_match('/\b(pengeluaran|belanja|habis|keluar|beli|dibelanjakan|spending)\b/u', $lc);
        if (!$wantsIncome && !$wantsSpend) {
            $wantsSpend = true; // default: pengeluaran
        }

        // 4) Hitung dari DB.
        $user = User::where('wallet_address', $addr)->first();
        $spent = $spentCount = 0;
        if ($user) {
            $q = Order::where('user_id', $user->id)->when($since, fn ($x) => $x->where('created_at', '>=', $since));
            $spent = (float) $q->sum('total');
            $spentCount = (clone $q)->count();
        }

        $feeBps = (int) config('chain.platform_fee_bps', 100); // 1% default
        $net = 1 - $feeBps / 10000;
        $earnQ = OrderItem::where('seller_wallet', $addr)->where('status', 'completed')
            ->when($since, fn ($x) => $x->where('created_at', '>=', $since));
        $earned = (float) $earnQ->sum('amount') * $net;
        $earnedCount = (clone $earnQ)->count();

        // 5) Susun balasan.
        $name = $entity['name'];
        $badge = $entity['verified'] ? ' (terverifikasi ✓)' : '';
        $lines = [];
        if ($wantsSpend) {
            $lines[] = "Total pengeluaran (belanja) **{$name}**{$badge} {$rangeLabel}: **" . $this->fmt($spent) . " TLKM** dari {$spentCount} transaksi.";
        }
        if ($wantsIncome) {
            $lines[] = "Total pemasukan (penjualan, setelah biaya) **{$name}**{$badge} {$rangeLabel}: **" . $this->fmt($earned) . " TLKM** dari {$earnedCount} transaksi.";
        }
        $reply = implode("\n", $lines) . "\n\nLihat rincian lengkapnya di Explorer.";

        return [
            'reply'    => $reply,
            'products' => [],
            'explorer' => [
                'address' => $addr,
                'name'    => $name,
                'verified'=> (bool) $entity['verified'],
                'range'   => $rangeKey,
                'url'     => url('/explorer/' . $addr),
            ],
        ];
    }

    /**
     * Cocokkan teks pesan ke sebuah entitas publik (label terverifikasi > toko >
     * user pseudonim publik), atau alamat 0x… langsung. Kembalikan [address,name,verified]
     * atau null.
     */
    private function resolveEntity(string $message): ?array
    {
        // Alamat wallet eksplisit.
        if (preg_match('/0x[a-fA-F0-9]{40}/', $message, $m)) {
            $addr = strtolower($m[0]);
            $id = Identity::resolve($addr);
            return ['address' => $addr, 'name' => $id['name'], 'verified' => (bool) $id['verified']];
        }

        $lc = mb_strtolower($message);
        $best = null; // ['address','name','verified','score','len']
        $consider = function (?string $name, ?string $address, bool $verified, float $priority) use ($lc, &$best) {
            $name = trim((string) $name);
            $address = strtolower((string) $address);
            if (mb_strlen($name) < 2 || !preg_match('/^0x[a-f0-9]{40}$/', $address)) {
                return;
            }
            if (mb_strpos($lc, mb_strtolower($name)) === false) {
                return;
            }
            $score = $priority + mb_strlen($name) / 100; // nama lebih spesifik menang
            if (!$best || $score > $best['score']) {
                $best = ['address' => $address, 'name' => $name, 'verified' => $verified, 'score' => $score];
            }
        };

        foreach (WalletLabel::all() as $l) {
            $consider($l->label, $l->address, (bool) $l->verified, $l->verified ? 3.0 : 2.0);
        }
        foreach (Store::all(['name', 'payout_wallet']) as $s) {
            $consider($s->name, $s->payout_wallet, false, 1.5);
        }
        foreach (User::where('explorer_public', true)->whereNotNull('public_name')->get(['public_name', 'wallet_address']) as $u) {
            $consider($u->public_name, $u->wallet_address, false, 1.0);
        }

        if (!$best) {
            return null;
        }
        return ['address' => $best['address'], 'name' => $best['name'], 'verified' => $best['verified']];
    }

    /** Deteksi rentang waktu -> [key, label Indonesia, Carbon sejak|null]. */
    private function parseRange(string $lc): array
    {
        if (preg_match('/\b(hari ini|hari|today|24 jam)\b/u', $lc)) {
            return ['day', 'hari ini', now()->subDay()];
        }
        if (preg_match('/\b(minggu|pekan|week|7 hari)\b/u', $lc)) {
            return ['week', 'minggu ini', now()->subWeek()];
        }
        if (preg_match('/\b(bulan|month|30 hari)\b/u', $lc)) {
            return ['month', 'bulan ini', now()->subMonth()];
        }
        return ['all', 'sepanjang waktu', null];
    }

    /** Format angka TLKM: buang nol/desimal tak perlu. */
    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ','), '0'), '.');
    }

    /** Cari produk berdasar kata kunci bermakna (>3 huruf), maks 5. */
    private function searchProducts(string $message)
    {
        $words = collect(preg_split('/\s+/', mb_strtolower($message)))
            ->filter(fn ($w) => mb_strlen($w) > 3)
            ->take(5);

        if ($words->isEmpty()) {
            return collect();
        }

        $q = Product::query()->with('store');
        $q->where(function ($sub) use ($words) {
            foreach ($words as $w) {
                $sub->orWhere('name', 'like', "%{$w}%")->orWhere('description', 'like', "%{$w}%");
            }
        });

        return $q->limit(5)->get()->map(fn ($p) => [
            'name'  => $p->name,
            'price' => rtrim(rtrim(number_format($p->price_usdc, 2), '0'), '.'),
            'url'   => url('/products/' . $p->id),
            'store' => $p->store->name ?? null,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
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

        $system = <<<SYS
Kamu "Asisten E-Trace", chatbot untuk marketplace blockchain bernama E-Trace.
Konteks E-Trace: marketplace di jaringan Ethereum Sepolia (testnet), pembayaran pakai token TLKM (ERC-20),
dana pembeli ditahan escrow smart contract sampai barang diterima, ada peran pembeli/penjual/pengawas,
login & bayar bisa pakai PIN (embedded wallet) atau MetaMask, ada fitur donasi (campaign) dan dompet komunitas.
ATURAN:
- Jawab HANYA seputar E-Trace dan crypto yang relevan (wallet, PIN, escrow, pembayaran TLKM, donasi, cara belanja, keamanan akun).
- Tolak dengan sopan pertanyaan di luar topik itu, arahkan kembali ke E-Trace.
- JANGAN memberi nasihat investasi/finansial atau prediksi harga.
- Jawab ringkas, ramah, dalam Bahasa Indonesia. Jangan mengarang fitur yang tidak ada.
- Kalau ada daftar produk di konteks, tampilkan nama dan harganya.
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
                'max_tokens'  => 400,
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

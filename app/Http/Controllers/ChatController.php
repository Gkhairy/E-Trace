<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Product;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /** Nama tampilan aman (nama publik / nama, tanpa PII). */
    private function displayName(?User $u): string
    {
        if (!$u) return 'Pengguna';
        return $u->public_name ?: $u->name;
    }

    /** Daftar percakapan: lawan bicara + pesan terakhir + jumlah belum dibaca. */
    public function conversations()
    {
        $me = auth()->id();
        $msgs = ChatMessage::where('sender_id', $me)->orWhere('receiver_id', $me)
            ->orderByDesc('id')->limit(500)->get();

        $convos = [];
        foreach ($msgs as $m) {
            $partnerId = $m->sender_id === $me ? $m->receiver_id : $m->sender_id;
            if (!isset($convos[$partnerId])) {
                $convos[$partnerId] = [
                    'partner_id' => $partnerId,
                    'name'       => $this->displayName(User::find($partnerId)),
                    'last'       => $m->type === 'offer' ? ('💬 Tawaran ' . rtrim(rtrim(number_format((float)$m->offer_amount, 2), '0'), '.') . ' TLKM') : ($m->body ?? ''),
                    'at'         => $m->created_at->diffForHumans(),
                    'unread'     => 0,
                ];
            }
            if ($m->receiver_id === $me && $m->read_at === null) {
                $convos[$partnerId]['unread']++;
            }
        }

        return response()->json(['conversations' => array_values($convos)]);
    }

    /** Pesan dalam satu percakapan; tandai pesan dari lawan sebagai dibaca. */
    public function thread(Request $req)
    {
        $me = auth()->id();
        $partnerId = (int) $req->query('with');
        abort_if($partnerId === $me || $partnerId <= 0, 422, 'Percakapan tidak valid.');

        ChatMessage::where('sender_id', $partnerId)->where('receiver_id', $me)->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = ChatMessage::with('product')
            ->where(fn ($q) => $q->where('sender_id', $me)->where('receiver_id', $partnerId))
            ->orWhere(fn ($q) => $q->where('sender_id', $partnerId)->where('receiver_id', $me))
            ->orderBy('id')->limit(300)->get()
            ->map(fn ($m) => [
                'id'        => $m->id,
                'mine'      => $m->sender_id === $me,
                'type'      => $m->type,
                'body'      => $m->body,
                'offer'     => $m->offer_amount !== null ? rtrim(rtrim(number_format((float)$m->offer_amount, 2), '0'), '.') : null,
                'offer_status' => $m->offer_status,
                'product'   => $m->product ? ['id' => $m->product->id, 'name' => $m->product->name] : null,
                'at'        => $m->created_at->format('d M H:i'),
            ]);

        return response()->json([
            'partner' => ['id' => $partnerId, 'name' => $this->displayName(User::find($partnerId))],
            'messages' => $messages,
        ]);
    }

    /** Kirim pesan teks. */
    public function send(Request $req)
    {
        $data = $req->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'body'        => 'required|string|max:2000',
            'product_id'  => 'nullable|integer|exists:products,id',
        ]);
        abort_if((int) $data['receiver_id'] === auth()->id(), 422, 'Tidak bisa mengirim ke diri sendiri.');

        $m = ChatMessage::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $data['receiver_id'],
            'product_id'  => $data['product_id'] ?? null,
            'type'        => 'text',
            'body'        => $data['body'],
        ]);

        Notify::send($data['receiver_id'], 'chat', 'Pesan baru',
            $this->displayName(auth()->user()) . ': ' . \Illuminate\Support\Str::limit($data['body'], 60),
            '/chat?with=' . auth()->id(), '💬');

        return response()->json(['success' => true, 'id' => $m->id]);
    }

    /** Kirim tawaran harga (nawar) untuk sebuah produk ke penjualnya. */
    public function offer(Request $req)
    {
        $data = $req->validate([
            'product_id' => 'required|integer|exists:products,id',
            'amount'     => 'required|numeric|min:0.000001',
            'body'       => 'nullable|string|max:500',
        ]);

        $product = Product::with('store')->findOrFail($data['product_id']);
        $sellerId = optional($product->store)->user_id;
        abort_unless($sellerId, 422, 'Penjual produk tidak ditemukan.');
        abort_if($sellerId === auth()->id(), 422, 'Tidak bisa menawar produk sendiri.');

        $amt = rtrim(rtrim(number_format((float) $data['amount'], 2), '0'), '.');
        ChatMessage::create([
            'sender_id'    => auth()->id(),
            'receiver_id'  => $sellerId,
            'product_id'   => $product->id,
            'type'         => 'offer',
            'body'         => ($data['body'] ?? null) ?: ('Nawar "' . $product->name . '" jadi ' . $amt . ' TLKM'),
            'offer_amount' => $data['amount'],
            'offer_status' => 'pending',
        ]);

        Notify::send($sellerId, 'chat', 'Tawaran harga baru',
            $this->displayName(auth()->user()) . " menawar \"{$product->name}\" jadi {$amt} TLKM.",
            '/chat?with=' . auth()->id(), '🏷️');

        return response()->json(['success' => true, 'seller_id' => $sellerId]);
    }

    /** Penjual menerima / menolak sebuah tawaran. */
    public function respond(Request $req)
    {
        $data = $req->validate([
            'message_id' => 'required|integer|exists:chat_messages,id',
            'action'     => 'required|in:accept,reject',
        ]);

        $m = ChatMessage::findOrFail($data['message_id']);
        abort_unless($m->type === 'offer' && $m->receiver_id === auth()->id(), 403, 'Tidak boleh.');
        abort_unless($m->offer_status === 'pending', 422, 'Tawaran sudah diproses.');

        $m->offer_status = $data['action'] === 'accept' ? 'accepted' : 'rejected';
        $m->save();

        $amt = rtrim(rtrim(number_format((float) $m->offer_amount, 2), '0'), '.');
        $verb = $data['action'] === 'accept' ? 'menerima' : 'menolak';
        // Balasan otomatis ke pembeli + notifikasi.
        ChatMessage::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $m->sender_id,
            'product_id'  => $m->product_id,
            'type'        => 'text',
            'body'        => "Penjual {$verb} tawaranmu ({$amt} TLKM).",
        ]);
        Notify::send($m->sender_id, 'chat', 'Tawaran ' . ($data['action'] === 'accept' ? 'diterima' : 'ditolak'),
            "Penjual {$verb} tawaranmu {$amt} TLKM.", '/chat?with=' . auth()->id(), $data['action'] === 'accept' ? '✅' : '❌');

        return response()->json(['success' => true, 'status' => $m->offer_status]);
    }

    /** Jumlah pesan belum dibaca (badge). */
    public function unreadCount()
    {
        return response()->json([
            'count' => ChatMessage::where('receiver_id', auth()->id())->whereNull('read_at')->count(),
        ]);
    }
}

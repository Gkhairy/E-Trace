<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index()
    {
        $me = auth()->id();

        // Teman (sudah diterima).
        $friends = Friendship::where('user_id', $me)->where('status', 'accepted')
            ->with('friend')->latest()->get();

        // Permintaan MASUK (menunggu persetujuanku).
        $incoming = Friendship::where('friend_id', $me)->where('status', 'pending')
            ->with('user')->latest()->get();

        // Permintaan yang KUKIRIM (menunggu diterima).
        $outgoing = Friendship::where('user_id', $me)->where('status', 'pending')
            ->with('friend')->latest()->get();

        return view('friends.index', compact('friends', 'incoming', 'outgoing'));
    }

    /** Kirim permintaan pertemanan via No HP / email (harus diterima dulu). */
    public function store(Request $req)
    {
        $data = $req->validate(['q' => 'required|string|max:120']);
        $q = trim($data['q']);
        $me = auth()->id();

        $friend = User::where('phone_hash', User::hashPhone($q))->orWhere('email', $q)->first();
        if (!$friend) {
            return back()->with('error', 'Pengguna dengan No HP/email itu tidak ditemukan.');
        }
        if ($friend->id === $me) {
            return back()->with('error', 'Tidak bisa menambah diri sendiri.');
        }

        // Sudah berteman?
        if (Friendship::where('user_id', $me)->where('friend_id', $friend->id)->where('status', 'accepted')->exists()) {
            return back()->with('error', 'Kalian sudah berteman.');
        }
        // Ada permintaan masuk dari dia? Langsung terima saja.
        $reverse = Friendship::where('user_id', $friend->id)->where('friend_id', $me)->where('status', 'pending')->first();
        if ($reverse) {
            $this->acceptPair($reverse);
            return back()->with('success', 'Kalian sekarang berteman dengan ' . $this->name($friend) . '.');
        }

        $fr = Friendship::firstOrCreate(
            ['user_id' => $me, 'friend_id' => $friend->id],
            ['status' => 'pending']
        );

        $meName = $this->name(auth()->user());
        Notify::send($friend->id, 'friend', 'Permintaan pertemanan', "{$meName} ingin berteman denganmu.", '/friends', '👋');

        return back()->with('success', 'Permintaan pertemanan dikirim ke ' . $this->name($friend) . '.');
    }

    /** Terima permintaan masuk. */
    public function accept(Request $req)
    {
        $fr = Friendship::where('id', $req->id)->where('friend_id', auth()->id())->where('status', 'pending')->first();
        if (!$fr) {
            return back()->with('error', 'Permintaan tidak ditemukan.');
        }
        $this->acceptPair($fr);
        return back()->with('success', 'Permintaan pertemanan diterima.');
    }

    /** Tolak permintaan masuk. */
    public function reject(Request $req)
    {
        Friendship::where('id', $req->id)->where('friend_id', auth()->id())->where('status', 'pending')->delete();
        return back()->with('success', 'Permintaan pertemanan ditolak.');
    }

    public function destroy(Request $req)
    {
        $me = auth()->id();
        // Hapus dua arah.
        Friendship::where(fn ($q) => $q->where('user_id', $me)->where('friend_id', $req->id))
            ->orWhere(fn ($q) => $q->where('user_id', $req->id)->where('friend_id', $me))
            ->delete();
        return back()->with('success', 'Teman dihapus.');
    }

    /** Setujui sepasang: tandai accepted + buat baris kebalikan + notifikasi. */
    private function acceptPair(Friendship $fr): void
    {
        $fr->status = 'accepted';
        $fr->save();
        Friendship::updateOrCreate(
            ['user_id' => $fr->friend_id, 'friend_id' => $fr->user_id],
            ['status' => 'accepted']
        );
        $accepter = User::find($fr->friend_id);
        Notify::send($fr->user_id, 'friend', 'Pertemanan diterima', $this->name($accepter) . ' menerima permintaan pertemananmu.', '/friends', '🤝');
    }

    private function name(User $u): string
    {
        return $u->public_name ?: $u->name;
    }
}

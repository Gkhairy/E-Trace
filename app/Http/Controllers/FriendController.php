<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index()
    {
        $friends = Friendship::where('user_id', auth()->id())->with('friend')->latest()->get();
        return view('friends.index', compact('friends'));
    }

    /** Tambah teman via No HP / email. */
    public function store(Request $req)
    {
        $data = $req->validate(['q' => 'required|string|max:120']);
        $q = trim($data['q']);

        $friend = User::where('phone', $q)->orWhere('email', $q)->first();
        if (!$friend) {
            return back()->with('error', 'Pengguna dengan No HP/email itu tidak ditemukan.');
        }
        if ($friend->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menambah diri sendiri.');
        }

        Friendship::firstOrCreate(['user_id' => auth()->id(), 'friend_id' => $friend->id]);
        return back()->with('success', 'Teman ditambahkan: ' . ($friend->public_name ?: $friend->name) . '.');
    }

    public function destroy(Request $req)
    {
        Friendship::where('user_id', auth()->id())->where('friend_id', $req->id)->delete();
        return back()->with('success', 'Teman dihapus.');
    }
}

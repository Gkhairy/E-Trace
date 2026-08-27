<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Daftar notifikasi user. */
    public function index()
    {
        $notifications = AppNotification::where('user_id', auth()->id())
            ->latest()->paginate(20);
        $unread = AppNotification::where('user_id', auth()->id())->unread()->count();
        return view('notifications.index', compact('notifications', 'unread'));
    }

    /** Jumlah belum dibaca (untuk badge & polling). */
    public function unreadCount()
    {
        return response()->json([
            'count' => AppNotification::where('user_id', auth()->id())->unread()->count(),
        ]);
    }

    /** Tandai satu dibaca lalu arahkan ke tautannya. */
    public function read(int $id)
    {
        $n = AppNotification::where('user_id', auth()->id())->find($id);
        if ($n) {
            if (!$n->read_at) {
                $n->forceFill(['read_at' => now()])->save();
            }
            return redirect($n->url ?: '/notifications');
        }
        return redirect('/notifications');
    }

    /** Tandai semua dibaca. */
    public function readAll(Request $request)
    {
        AppNotification::where('user_id', auth()->id())->unread()->update(['read_at' => now()]);
        return $request->wantsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }
}

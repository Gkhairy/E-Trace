<?php

namespace App\Http\Controllers;

use App\Models\DisasterEvent;
use App\Services\Disaster\DisasterRadar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Radar Bencana AI: panel pengawas (scan, uji berita, antrean) + sampul campaign. */
class DisasterController extends Controller
{
    private function ensure(): void
    {
        abort_unless(auth()->user()->isSupervisor(), 403, 'Khusus pengawas platform.');
    }

    public function index()
    {
        $this->ensure();
        $week = now()->subDays(7);
        $stats = [
            'assessed' => DisasterEvent::where('created_at', '>=', $week)->whereNotNull('ai_severity')->count(),
            'pending'  => DisasterEvent::where('status', 'pending_review')->count(),
            'auto'     => DisasterEvent::where('status', 'opened')->whereNull('reviewed_by')->where('created_at', '>=', $week)->count(),
            'rejected' => DisasterEvent::where('status', 'rejected')->where('created_at', '>=', $week)->count(),
        ];
        $pending  = DisasterEvent::where('status', 'pending_review')->orderByDesc('ai_severity')->limit(12)->get();
        $recent   = DisasterEvent::with('campaign')->latest()->limit(40)->get();
        $assessed = session('assessed') ? DisasterEvent::with('campaign')->find(session('assessed')) : null;

        return view('supervisor.disasters', [
            'stats'     => $stats,
            'pending'   => $pending,
            'recent'    => $recent,
            'assessed'  => $assessed,
            'recipient' => config('disaster.recipient_wallet'),
            'lastScan'  => Cache::get('disaster:last_scan'),
        ]);
    }

    public function scan(DisasterRadar $radar)
    {
        $this->ensure();
        @set_time_limit(180);
        $s = $radar->scan();
        Cache::put('disaster:last_scan', ['at' => now()->toIso8601String()] + $s, now()->addDays(7));
        return back()->with('success', "Scan selesai: {$s['new']} kejadian baru — {$s['opened']} donasi dibuka, {$s['pending_review']} masuk antrean, {$s['rejected']} ditolak, {$s['duplicate']} duplikat.");
    }

    public function assess(Request $req, DisasterRadar $radar)
    {
        $this->ensure();
        $data = $req->validate([
            'text' => 'required|string|min:30|max:3000',
            'url'  => 'nullable|url|starts_with:http://,https://|max:500',
        ]);
        @set_time_limit(60);
        $event = $radar->assessManual($data['text'], $data['url'] ?? null);
        return back()->with('assessed', $event->id);
    }

    public function approve(Request $req, DisasterRadar $radar)
    {
        $this->ensure();
        $data = $req->validate([
            'id'        => 'required|integer|exists:disaster_events,id',
            'recipient' => ['nullable', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);
        $event = DisasterEvent::findOrFail($data['id']);
        abort_unless(in_array($event->status, ['pending_review', 'rejected'], true), 422, 'Kejadian ini sudah diputuskan.');

        $campaign = $radar->openCampaign($event, auth()->id(), $data['recipient'] ?? null);
        return redirect('/donate/' . $campaign->slug)->with('success', 'Donasi dibuka untuk ' . $campaign->title . '.');
    }

    public function dismiss(Request $req)
    {
        $this->ensure();
        $data = $req->validate(['id' => 'required|integer|exists:disaster_events,id']);
        $event = DisasterEvent::findOrFail($data['id']);
        abort_unless(in_array($event->status, ['pending_review', 'rejected'], true), 422, 'Kejadian ini sudah diputuskan.');
        $event->update(['status' => 'dismissed', 'reviewed_by' => auth()->id()]);
        return back()->with('success', 'Usulan diabaikan.');
    }

    /** Sampul ilustrasi per jenis bencana (SVG), dipakai campaign dari Radar Bencana. */
    public function cover(string $type)
    {
        abort_unless(array_key_exists($type, DisasterEvent::TYPES), 404);
        return response()->view('donate.cover', ['type' => $type, 'label' => DisasterEvent::TYPES[$type]])
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=604800');
    }
}

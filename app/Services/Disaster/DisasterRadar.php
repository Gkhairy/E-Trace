<?php

namespace App\Services\Disaster;

use App\Models\Campaign;
use App\Models\DisasterEvent;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Str;

/**
 * Otak Radar Bencana: ambil kejadian → saring → nilai AI → putuskan.
 *
 * Aturan buka:
 * - Skor AI ≥ auto_min_severity (70), dari sumber mana pun → campaign dibuka otomatis
 *   ke wallet Lembaga Donasi E-Trace (dibatasi per hari).
 * - Skor sedang (review_min_severity..69) → antrean pengawas, dibuka dengan satu klik.
 * - Bukan bencana / terlalu kecil / kejadian yang sama → ditolak / ditandai duplikat.
 */
class DisasterRadar
{
    public function __construct(private DisasterSources $sources, private DisasterAI $ai, private ArticleFetcher $articles) {}

    /** Satu putaran scan. Return ringkasan hitungan per status. */
    public function scan(): array
    {
        $cutoff = now()->subDays((int) config('disaster.lookback_days', 7));
        $budget = (int) config('disaster.max_ai_per_scan', 12);
        $stats  = ['fetched' => 0, 'new' => 0, 'opened' => 0, 'pending_review' => 0, 'rejected' => 0, 'duplicate' => 0];

        foreach ($this->sources->all() as $e) {
            $stats['fetched']++;
            if ($e['occurred_at']->lt($cutoff)
                || DisasterEvent::where('source', $e['source'])->where('external_id', $e['external_id'])->exists()) {
                continue;
            }

            // Saring tanpa AI: gempa kecil & level GDACS Green tidak perlu dinilai.
            $skip = $this->preFilter($e);
            if ($skip === null && $budget <= 0) {
                continue; // jatah AI habis → dinilai di scan berikutnya
            }
            $event = DisasterEvent::create(collect($e)->only([
                'source', 'external_id', 'type', 'title', 'location', 'magnitude', 'alert_level', 'occurred_at',
                'url', 'image_url', 'image_credit', 'summary',
            ])->all());
            $stats['new']++;

            if ($skip !== null) {
                $event->update(['status' => 'rejected', 'ai_is_disaster' => false, 'ai_reason' => $skip]);
                $stats['rejected']++;
                continue;
            }
            $budget--;
            $e = $this->enrich($event, $e);
            $stats[$this->process($event, $e)]++;
        }
        return $stats;
    }

    /** Input pengawas (teks berita + link opsional): dinilai AI dengan aturan yang sama. */
    public function assessManual(string $text, ?string $url): DisasterEvent
    {
        $text = trim($text);
        $firstLine = trim(strtok($text, "\n")) ?: 'Laporan bencana';
        $e = [
            'source'      => 'manual',
            'external_id' => sha1($text . '|' . $url . '|' . now()->timestamp),
            'type'        => null,
            'title'       => mb_substr($firstLine, 0, 250),
            'location'    => null,
            'magnitude'   => null,
            'alert_level' => null,
            'occurred_at' => now(),
            'url'         => $url ?: null,
            'summary'     => mb_substr($text, 0, 3000),
        ];
        $event = DisasterEvent::create($e);
        $e = $this->enrich($event, $e);
        $this->process($event, $e);
        return $event->fresh();
    }

    /**
     * Baca artikel sumber (bila link-nya langsung ke artikel): isi teksnya ikut dinilai AI
     * supaya deskripsi campaign lengkap, plus foto utama & nama media bila belum ada.
     */
    private function enrich(DisasterEvent $event, array $e): array
    {
        if (!in_array($event->source, ['news', 'manual'], true) || !$event->url) {
            return $e;
        }
        $a = $this->articles->fetch($event->url);
        if ($a['text']) {
            $e['summary'] = trim(($e['summary'] ?? '') . "\n\nIsi artikel:\n" . $a['text']);
        }
        if (!$event->image_url && $a['image']) {
            $event->update(['image_url' => $a['image'], 'image_credit' => 'Foto: ' . ($a['site'] ?: parse_url($event->url, PHP_URL_HOST))]);
        }
        return $e;
    }

    /** Nilai + putuskan satu kejadian. Return status akhirnya. */
    public function process(DisasterEvent $event, array $e): string
    {
        $recent = DisasterEvent::whereIn('status', ['opened', 'pending_review'])
            ->where('id', '!=', $event->id)
            ->where('created_at', '>=', now()->subDays(14))
            ->latest()->limit(20)->get()
            ->map(fn ($r) => [
                'id' => $r->id, 'title' => $r->ai_title ?: $r->title, 'location' => $r->location ?: '-',
                'occurred_at' => optional($r->occurred_at)->format('Y-m-d') ?? '-',
            ])->all();

        $a = $this->ai->assess($e, $recent);
        $event->update([
            'type'           => $a['type'],
            'location'       => $a['location'] ?? $event->location,
            'ai_is_disaster' => $a['is_disaster'],
            'ai_severity'    => $a['severity'],
            'ai_reason'      => $a['reason'],
            'ai_title'       => $a['title'],
            'ai_description' => $a['description'],
        ]);

        if ($a['same_as']) {
            $event->update(['status' => 'duplicate', 'duplicate_of' => $a['same_as']]);
            // Laporan lain untuk kejadian yang sama: pinjam fotonya bila belum ada, dan
            // tambahkan sebagai sumber campaign yang sudah dibuka.
            $original = DisasterEvent::find($a['same_as']);
            if ($original && !$original->image_url && $event->image_url) {
                $original->update(['image_url' => $event->image_url, 'image_credit' => $event->image_credit]);
            }
            if ($original?->campaign) {
                $this->appendSource($original->campaign, $event);
            }
            return 'duplicate';
        }
        if (!$a['is_disaster'] || $a['severity'] < (int) config('disaster.review_min_severity', 45)) {
            $event->update(['status' => 'rejected']);
            return 'rejected';
        }

        if ($this->autoEligible($event)) {
            $this->openCampaign($event, null);
            $this->notifySupervisors('Donasi dibuka otomatis oleh AI', [':title — sumber :source.', ['title' => $event->ai_title ?: $event->title, 'source' => $event->sourceLabel()]], '/supervisor/disasters');
            return 'opened';
        }

        $event->update(['status' => 'pending_review']);
        if ($event->source !== 'manual') {
            $this->notifySupervisors('Usulan donasi dari Radar Bencana', [':title — skor AI :score. Tinjau untuk membuka donasi.', ['title' => $event->ai_title ?: $event->title, 'score' => $event->ai_severity]], '/supervisor/disasters');
        }
        return 'pending_review';
    }

    /** Buka campaign donasi untuk kejadian ini. $reviewerId null = dibuka otomatis. */
    public function openCampaign(DisasterEvent $event, ?int $reviewerId, ?string $recipient = null): Campaign
    {
        $recipient = strtolower($recipient ?: (string) config('disaster.recipient_wallet'));
        abort_unless(preg_match('/^0x[a-f0-9]{40}$/', $recipient), 422, __('Wallet Lembaga Donasi belum diisi (DISASTER_RECIPIENT_WALLET).'));

        $title = $event->ai_title ?: $event->title;
        // Bagian penyaluran & sumber ditulis sistem (bukan AI) supaya link-nya pasti benar.
        $desc = trim($event->ai_description ?: $event->summary ?: '') . "\n\n"
            . "Penyaluran dana\n"
            . 'Donasi ditampung di kontrak DonationPool di BNB Smart Chain, lalu disalurkan pengawas ke wallet Lembaga Donasi E-Trace. '
            . 'Setiap donasi dan penyaluran tercatat on-chain dan bisa diaudit siapa pun lewat Explorer. '
            . ($reviewerId
                ? "Campaign ini diusulkan Radar Bencana AI (skor dampak {$event->ai_severity}/100) dan disetujui pengawas."
                : "Campaign ini dibuka otomatis oleh Radar Bencana AI (skor dampak {$event->ai_severity}/100).")
            . "\n\nSumber\n" . $this->sourceLine($event);

        $campaign = Campaign::create([
            'title'             => mb_substr($title, 0, 120),
            'slug'              => Str::slug(mb_substr($title, 0, 60)) . '-' . Str::lower(Str::random(6)),
            'description'       => mb_substr($desc, 0, 6000),
            'image'             => 'cover:' . ($event->type ?: 'lainnya'),
            'recipient_wallet'  => $recipient,
            'goal_amount'       => null,
            'closes_at'         => now()->addDays((int) config('disaster.campaign_days', 30)),
            'status'            => 'active',
            'origin'            => 'ai',
            'disaster_event_id' => $event->id,
            'created_by'        => $reviewerId ?? (int) (User::where('role', 'supervisor')->orderBy('id')->value('id') ?? 0),
        ]);
        $event->update(['status' => 'opened', 'campaign_id' => $campaign->id, 'reviewed_by' => $reviewerId ?? $event->reviewed_by]);
        return $campaign;
    }

    /** Satu baris sumber: "• ANTARA: judul (27 Sep 2026 00:12 WIB) — link". */
    private function sourceLine(DisasterEvent $e): string
    {
        $who = ($e->url ? $this->articles->mediaName($e->url) : null) ?? $e->sourceLabel();
        $when = $e->occurred_at ? ' (' . $e->occurred_at->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB)' : '';
        return '• ' . $who . ': ' . $e->title . $when . ($e->url ? ' — ' . $e->url : '');
    }

    /** Laporan lain untuk kejadian yang sama → tambahkan ke daftar sumber campaign. */
    private function appendSource(Campaign $campaign, DisasterEvent $e): void
    {
        $desc = (string) $campaign->description;
        if (($e->url && str_contains($desc, $e->url)) || mb_strlen($desc) > 5500) {
            return;
        }
        $campaign->update(['description' => rtrim($desc) . "\n" . $this->sourceLine($e)]);
    }

    /** Syarat buka otomatis: skor AI ≥ ambang + wallet lembaga ada + kuota harian belum habis. */
    private function autoEligible(DisasterEvent $event): bool
    {
        if ($event->ai_severity < (int) config('disaster.auto_min_severity', 70)
            || !preg_match('/^0x[a-fA-F0-9]{40}$/', (string) config('disaster.recipient_wallet'))) {
            return false;
        }
        $today = Campaign::where('origin', 'ai')->whereDate('created_at', today())
            ->whereHas('disasterEvent', fn ($q) => $q->whereNull('reviewed_by'))->count();
        return $today < (int) config('disaster.max_auto_per_day', 3);
    }

    /** Alasan menolak tanpa AI, atau null bila perlu dinilai. */
    private function preFilter(array $e): ?string
    {
        if ($e['source'] === 'bmkg' && (float) $e['magnitude'] < (float) config('disaster.bmkg_scan_magnitude', 5.0)) {
            return 'Gempa di bawah M' . config('disaster.bmkg_scan_magnitude', 5.0) . ' — tidak dinilai.';
        }
        if ($e['source'] === 'gdacs' && ($e['alert_level'] ?? '') === 'Green') {
            return 'Level GDACS Green (dampak kemanusiaan rendah) — tidak dinilai.';
        }
        return null;
    }

    private function notifySupervisors(string $title, string|array $body, string $url): void
    {
        foreach (User::where('role', 'supervisor')->pluck('id') as $id) {
            Notify::send($id, 'disaster', $title, $body, $url, '🛰️');
        }
    }
}

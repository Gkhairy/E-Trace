<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use kornrunner\Keccak;

class Campaign extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'image',
        'recipient_wallet', 'goal_amount', 'closes_at', 'status', 'created_by',
        'origin', 'disaster_event_id',
    ];

    protected $casts = [
        'closes_at' => 'datetime',
    ];

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    /** Donasi ditutup? (status manual "closed" ATAU sudah lewat batas waktu) */
    public function isClosed(): bool
    {
        return $this->status === 'closed'
            || ($this->closes_at !== null && now()->greaterThan($this->closes_at));
    }

    /** campaignId on-chain = keccak256(slug), format 0x… (bytes32). */
    public function chainId(): string
    {
        return '0x' . Keccak::hash($this->slug, 256);
    }

    public function disasterEvent()
    {
        return $this->belongsTo(DisasterEvent::class);
    }

    /** Dibuka oleh Radar Bencana AI (otomatis atau usulan AI yang disetujui). */
    public function isAi(): bool
    {
        return $this->origin === 'ai';
    }

    /**
     * URL gambar card. Campaign dari Radar Bencana ("cover:<jenis>") memakai foto dari
     * sumber kejadiannya (artikel berita / peta guncangan BMKG) yang ditampilkan langsung
     * dari situs sumber beserta kreditnya; tanpa foto → sampul ilustrasi per jenis bencana.
     */
    public function imageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }
        if (!str_starts_with($this->image, 'cover:')) {
            return '/campaign_images/' . $this->image; // diunggah pengawas
        }
        return $this->disasterEvent?->image_url ?: $this->coverUrl();
    }

    /** Sampul ilustrasi per jenis bencana (juga cadangan bila foto sumber gagal dimuat). */
    public function coverUrl(): string
    {
        $type = str_starts_with((string) $this->image, 'cover:') ? substr($this->image, 6) : 'lainnya';
        return '/donate/cover/' . $type;
    }

    /** Kredit foto sumber ("Foto: ANTARA") + link artikelnya, atau null untuk gambar sendiri. */
    public function imageCredit(): ?array
    {
        $e = $this->disasterEvent;
        if (!str_starts_with((string) $this->image, 'cover:') || !$e?->image_url || !$e->image_credit) {
            return null;
        }
        return ['text' => $e->image_credit, 'url' => $e->url ?: $e->image_url];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

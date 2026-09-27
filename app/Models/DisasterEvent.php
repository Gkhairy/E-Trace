<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisasterEvent extends Model
{
    protected $fillable = [
        'source', 'external_id', 'type', 'title', 'location', 'magnitude', 'alert_level', 'occurred_at',
        'url', 'image_url', 'image_credit', 'summary', 'ai_is_disaster', 'ai_severity', 'ai_reason', 'ai_title', 'ai_description',
        'status', 'campaign_id', 'duplicate_of', 'reviewed_by',
    ];

    protected $casts = [
        'occurred_at'    => 'datetime',
        'ai_is_disaster' => 'boolean',
        'magnitude'      => 'float',
    ];

    public const TYPES = [
        'gempa'     => 'Gempa bumi',
        'tsunami'   => 'Tsunami',
        'banjir'    => 'Banjir',
        'longsor'   => 'Tanah longsor',
        'kebakaran' => 'Kebakaran',
        'erupsi'    => 'Erupsi gunung api',
        'angin'     => 'Angin kencang / siklon',
        'kekeringan'=> 'Kekeringan',
        'lainnya'   => 'Bencana lain',
    ];

    public const SOURCES = ['bmkg' => 'BMKG', 'gdacs' => 'GDACS', 'news' => 'Berita', 'manual' => 'Input pengawas'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Bencana';
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /** Nama sumber untuk ditampilkan: nama media dari link (ANTARA, CNN Indonesia) bila dikenali. */
    public function sourceName(): string
    {
        $media = $this->url ? app(\App\Services\Disaster\ArticleFetcher::class)->mediaName($this->url) : null;
        return $media ?? $this->sourceLabel();
    }
}

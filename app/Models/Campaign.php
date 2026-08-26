<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use kornrunner\Keccak;

class Campaign extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'image',
        'recipient_wallet', 'goal_amount', 'closes_at', 'status', 'created_by',
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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

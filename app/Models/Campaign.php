<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use kornrunner\Keccak;

class Campaign extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'image',
        'recipient_wallet', 'goal_amount', 'status', 'created_by',
    ];

    public function donations()
    {
        return $this->hasMany(Donation::class);
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

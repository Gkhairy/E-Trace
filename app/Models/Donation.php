<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = ['campaign_id', 'donor_wallet', 'amount', 'tx_hash', 'block_number'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}

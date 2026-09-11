<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityDeposit extends Model
{
    protected $fillable = ['community_wallet_id', 'user_id', 'from_wallet', 'amount', 'tx_hash'];
    protected $casts = ['amount' => 'decimal:6'];

    public function user() { return $this->belongsTo(User::class); }
}

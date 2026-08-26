<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityMember extends Model
{
    protected $fillable = ['community_wallet_id', 'user_id', 'monthly_limit', 'spent', 'period_start'];
    protected $casts = ['period_start' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}

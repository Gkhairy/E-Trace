<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityMemberNickname extends Model
{
    protected $fillable = ['community_wallet_id', 'viewer_id', 'target_user_id', 'nickname'];
}

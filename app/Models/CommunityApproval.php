<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityApproval extends Model
{
    protected $fillable = ['proposal_id', 'user_id'];
}

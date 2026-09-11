<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityProposal extends Model
{
    protected $fillable = [
        'community_wallet_id', 'proposer_id', 'type', 'to_wallet', 'to_name', 'target_user_id',
        'amount', 'note', 'meta', 'status', 'tx_hash',
    ];

    protected $casts = ['meta' => 'array'];

    public function approvals()  { return $this->hasMany(CommunityApproval::class, 'proposal_id'); }
    public function targetUser() { return $this->belongsTo(User::class, 'target_user_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityProposal extends Model
{
    protected $fillable = ['community_wallet_id', 'proposer_id', 'to_wallet', 'to_name', 'amount', 'note', 'status', 'tx_hash'];

    public function approvals() { return $this->hasMany(CommunityApproval::class, 'proposal_id'); }
}

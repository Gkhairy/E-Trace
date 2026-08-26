<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityWallet extends Model
{
    protected $fillable = [
        'name', 'mode', 'managed', 'address', 'description', 'created_by',
        'wallet_enc', 'wallet_salt', 'wallet_iv', 'wallet_tag', 'threshold', 'gas_dripped_at',
    ];

    protected $casts = ['managed' => 'boolean', 'gas_dripped_at' => 'datetime'];

    public function isMultisig(): bool { return $this->mode === 'B'; }
    public function modeLabel(): string { return $this->mode === 'B' ? 'Multisig (M-dari-N)' : 'Jatah Bulanan'; }

    public function members()   { return $this->hasMany(CommunityMember::class); }
    public function proposals() { return $this->hasMany(CommunityProposal::class)->latest(); }
}

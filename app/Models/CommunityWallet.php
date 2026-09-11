<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityWallet extends Model
{
    protected $fillable = [
        'name', 'mode', 'managed', 'address', 'description', 'created_by', 'owner_id',
        'wallet_enc', 'wallet_salt', 'wallet_iv', 'wallet_tag', 'threshold', 'gas_dripped_at',
    ];

    protected $casts = ['managed' => 'boolean', 'gas_dripped_at' => 'datetime'];

    public function isMultisig(): bool { return $this->mode === 'B'; }
    public function modeLabel(): string { return $this->mode === 'B' ? 'Multisig (bulat)' : 'Jatah Bulanan'; }

    /** Pemilik dompet (default = pembuat bila owner_id belum diisi). */
    public function ownerId(): int { return (int) ($this->owner_id ?: $this->created_by); }
    public function isOwner(?int $userId): bool { return $userId !== null && $this->ownerId() === $userId; }

    public function members()   { return $this->hasMany(CommunityMember::class); }
    public function proposals() { return $this->hasMany(CommunityProposal::class)->latest(); }
    public function deposits()  { return $this->hasMany(CommunityDeposit::class)->latest(); }

    /** Semua aksi BULAT: butuh persetujuan setiap penanda tangan. */
    public function signerCount(): int { return (int) $this->members()->where('is_signer', true)->count(); }
    public function requiredApprovals(): int { return max(1, $this->signerCount()); }
}

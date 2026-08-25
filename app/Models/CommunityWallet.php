<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityWallet extends Model
{
    protected $fillable = ['name', 'mode', 'address', 'description', 'created_by'];

    public function isMultisig(): bool
    {
        return $this->mode === 'B';
    }

    public function modeLabel(): string
    {
        return $this->mode === 'B' ? 'Multisig (M-dari-N)' : 'Jatah Bulanan';
    }
}

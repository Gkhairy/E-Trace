<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaylaterLoan extends Model
{
    protected $fillable = ['user_id', 'wallet_address', 'action', 'amount', 'tx_hash'];

    protected $casts = ['amount' => 'decimal:6'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

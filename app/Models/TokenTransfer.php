<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu event Transfer TLKM on-chain yang sudah diindeks. */
class TokenTransfer extends Model
{
    protected $fillable = [
        'tx_hash', 'log_index', 'block_number', 'block_time',
        'from_address', 'to_address', 'amount',
    ];

    protected $casts = [
        'block_time'   => 'datetime',
        'block_number' => 'integer',
        'log_index'    => 'integer',
    ];

    /** Transfer yang menyangkut sebuah alamat (masuk maupun keluar), terbaru dulu. */
    public function scopeForAddress($q, string $addr)
    {
        $a = strtolower($addr);
        return $q->where(fn ($w) => $w->where('from_address', $a)->orWhere('to_address', $a))
                 ->orderByDesc('block_number')->orderByDesc('log_index');
    }
}

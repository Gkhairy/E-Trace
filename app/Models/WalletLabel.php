<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLabel extends Model
{
    protected $fillable = ['address', 'label', 'category', 'verified', 'notes'];

    protected $casts = ['verified' => 'boolean'];
}

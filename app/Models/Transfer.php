<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = ['from_wallet', 'to_wallet', 'amount', 'note', 'request_id', 'tx_hash', 'block_number'];
}

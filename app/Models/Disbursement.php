<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model
{
    protected $fillable = ['campaign_id', 'to_address', 'amount', 'by_wallet', 'tx_hash', 'block_number'];
}

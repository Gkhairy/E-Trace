<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    protected $fillable = ['code', 'user_id', 'amount', 'note', 'status'];

    protected $casts = ['amount' => 'decimal:6'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

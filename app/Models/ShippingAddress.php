<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $fillable = [
        'user_id', 'recipient_name', 'phone', 'address', 'city', 'postal_code', 'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

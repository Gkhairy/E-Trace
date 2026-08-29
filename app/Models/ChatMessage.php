<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'sender_id', 'receiver_id', 'product_id', 'type', 'body', 'offer_amount', 'offer_status', 'read_at',
    ];

    protected $casts = ['read_at' => 'datetime', 'offer_amount' => 'decimal:6'];

    public function sender()   { return $this->belongsTo(User::class, 'sender_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function product()  { return $this->belongsTo(Product::class); }
}

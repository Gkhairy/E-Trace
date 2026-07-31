<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'tx_hash',
        'amount',
        'total',
        'shipping_address_id',
        'status',
        'buyer_notified_at',
        'block_number',
        'confirmations',
        'finalized_at',
    ];

    protected $casts = [
        'buyer_notified_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_ref_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }
}

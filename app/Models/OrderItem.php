<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_ref_id', 'product_id', 'seller_wallet', 'amount', 'quantity', 'item_index', 'status',
        'seller_notified_at', 'fulfillment_status', 'tracking_number', 'courier', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'seller_notified_at' => 'datetime',
        'shipped_at'         => 'datetime',
        'delivered_at'       => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_ref_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}

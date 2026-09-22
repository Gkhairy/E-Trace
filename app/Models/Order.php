<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'community_wallet_id', // diisi bila order dibayar dari dana komunitas (multisig)
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
        // AI Auto-Settlement + Garansi Tepat Waktu (DEMO)
        'settlement_status',
        'ai_decision',
        'ai_reason',
        'shipping_tlkm',
        'is_insured',
        'premium_tlkm',
        'promised_date',
        'eta_days',
        'insurance_status',
        'payout_tx',
        'payout_tlkm',
        'premium_tx',
    ];

    protected $casts = [
        'buyer_notified_at' => 'datetime',
        'finalized_at'      => 'datetime',
        'promised_date'     => 'datetime',
        'is_insured'        => 'boolean',
        'ai_decision'       => 'array',
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

    public function trackingEvents()
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('created_at');
    }
}

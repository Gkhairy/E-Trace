<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris riwayat tracking pengiriman (mentah, lintas kurir). Jadi bahan baku
 * keputusan DeliveryAI. source: 'courier' (nyata) atau 'simulated' (kontrol demo).
 */
class TrackingEvent extends Model
{
    protected $fillable = ['order_id', 'raw_text', 'source'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

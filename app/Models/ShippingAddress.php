<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'phone', 'address', 'city', 'postal_code', 'notes', 'is_default',
    ];

    // I1: data pribadi dienkripsi at-rest (kalau DB bocor, tidak langsung terbaca).
    // city & postal_code dibiarkan plaintext (dipakai estimasi ongkir, sensitivitas rendah).
    // Pakai cast toleran agar data LAMA (plaintext) tetap terbaca, data baru dienkripsi.
    protected $casts = [
        'is_default'     => 'boolean',
        'recipient_name' => \App\Casts\EncryptedOrPlain::class,
        'phone'          => \App\Casts\EncryptedOrPlain::class,
        'address'        => \App\Casts\EncryptedOrPlain::class,
        'notes'          => \App\Casts\EncryptedOrPlain::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Penanda posisi indexer (sampai blok mana sudah dipindai). */
class IndexerCursor extends Model
{
    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['name', 'block_number'];
    protected $casts = ['block_number' => 'integer'];
}

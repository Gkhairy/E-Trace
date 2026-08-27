<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'icon', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function scopeUnread($q)
    {
        return $q->whereNull('read_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

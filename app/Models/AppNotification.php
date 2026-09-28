<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'params', 'url', 'icon', 'read_at'];

    protected $casts = ['read_at' => 'datetime', 'params' => 'array'];

    public function scopeUnread($q)
    {
        return $q->whereNull('read_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Judul dalam bahasa antarmuka saat ini (title = template Indonesia). */
    public function displayTitle(): string
    {
        return __($this->title, $this->params ?? []);
    }

    /** Isi dalam bahasa antarmuka saat ini; notifikasi lama tanpa params tampil apa adanya. */
    public function displayBody(): ?string
    {
        return $this->body === null ? null : __($this->body, $this->params ?? []);
    }
}

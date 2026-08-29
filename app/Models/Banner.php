<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['title', 'image', 'link', 'sort', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** URL gambar banner (dukung file lokal & URL remote). */
    public function imageUrl(): string
    {
        return str_starts_with($this->image, 'http') ? $this->image : '/banner_images/' . $this->image;
    }
}

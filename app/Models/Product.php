<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'price_usdc', 'stock', 'seller_wallet', 'product_id', 'image', 'store_id', 'category_id',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * URL gambar produk. Mendukung file lokal (product_images) MAUPUN URL remote
     * (mis. hasil scraping). Kembalikan null bila tak ada gambar.
     */
    public function imageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return str_starts_with($this->image, 'http')
            ? $this->image
            : '/product_images/' . $this->image;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}


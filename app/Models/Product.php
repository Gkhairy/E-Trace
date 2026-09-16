<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'price_usdc', 'stock', 'seller_wallet', 'product_id', 'image', 'gallery', 'store_id', 'category_id',
    ];

    protected $casts = [
        'gallery' => 'array',
    ];

    /** Ubah satu referensi gambar (nama file lokal ATAU URL) menjadi URL tayang. */
    public static function resolveImage(string $ref): string
    {
        return str_starts_with($ref, 'http') ? $ref : '/product_images/' . $ref;
    }

    /**
     * Semua gambar produk (urut) sebagai URL siap tayang, untuk galeri/carousel.
     * Elemen pertama = thumbnail. Fallback ke kolom `image` untuk produk lama.
     */
    public function images(): array
    {
        $refs = is_array($this->gallery) ? array_values(array_filter($this->gallery)) : [];
        if (empty($refs) && $this->image) {
            $refs = [$this->image];
        }
        return array_map(fn ($r) => self::resolveImage($r), $refs);
    }

    /**
     * URL thumbnail siap-tayang (gambar pertama gallery; fallback kolom `image`).
     * Aman untuk file lokal MAUPUN URL remote (http). Null bila produk tak bergambar.
     * Pakai ini di keranjang/order/checkout alih-alih menempel '/product_images/' manual.
     */
    public function thumbnail(): ?string
    {
        return $this->images()[0] ?? null;
    }

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


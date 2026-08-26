<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Impor produk dari file JSON/JSONL hasil scraping (J1).
 *
 * Format tiap baris (JSONL): {"id","name","brand","price","list_price",
 *   "rating","reviews","seller","department","image","url"}
 *
 * Contoh:
 *   php artisan products:import
 *   php artisan products:import storage/app/seed/walmart_products.jsonl --limit=200
 *   php artisan products:import path/ke/file.jsonl --store=toko-demo --truncate
 */
class ImportProducts extends Command
{
    protected $signature = 'products:import
        {file? : Path file JSONL (default: storage/app/seed/walmart_products.jsonl)}
        {--store= : Slug toko tujuan (default: toko seller pertama)}
        {--limit=0 : Batasi jumlah produk (0 = semua)}
        {--truncate : Hapus produk toko tujuan dulu sebelum impor}';

    protected $description = 'Impor produk dari file JSON/JSONL hasil scraping ke sebuah toko.';

    /** Peta kata kunci "department" -> slug kategori. */
    private const DEPT_MAP = [
        'electronic' => 'elektronik', 'phone' => 'elektronik', 'computer' => 'elektronik', 'tv' => 'elektronik',
        'cloth' => 'fashion', 'apparel' => 'fashion', 'shoe' => 'fashion', 'fashion' => 'fashion', 'jewelry' => 'fashion',
        'home' => 'rumah-tangga', 'furniture' => 'rumah-tangga', 'kitchen' => 'rumah-tangga', 'garden' => 'rumah-tangga', 'appliance' => 'rumah-tangga',
        'beauty' => 'kecantikan', 'cosmetic' => 'kecantikan', 'personal care' => 'kecantikan',
        'grocery' => 'makanan-minuman', 'food' => 'makanan-minuman', 'beverage' => 'makanan-minuman', 'snack' => 'makanan-minuman',
        'health' => 'kesehatan', 'pharmacy' => 'kesehatan', 'medical' => 'kesehatan', 'wellness' => 'kesehatan',
        'sport' => 'olahraga', 'outdoor' => 'olahraga', 'fitness' => 'olahraga',
        'toy' => 'hobi-mainan', 'game' => 'hobi-mainan', 'hobby' => 'hobi-mainan',
        'auto' => 'otomotif', 'car' => 'otomotif', 'motor' => 'otomotif', 'vehicle' => 'otomotif',
        'book' => 'buku-alat-tulis', 'office' => 'buku-alat-tulis', 'stationery' => 'buku-alat-tulis',
        'baby' => 'bayi-anak', 'kids' => 'bayi-anak', 'child' => 'bayi-anak',
    ];

    public function handle(): int
    {
        $file = $this->argument('file') ?: storage_path('app/seed/walmart_products.jsonl');
        if (!is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            $this->line('Letakkan file JSONL di storage/app/seed/walmart_products.jsonl atau beri path sebagai argumen.');
            return self::FAILURE;
        }

        // Toko tujuan.
        $store = $this->option('store')
            ? Store::where('slug', $this->option('store'))->first()
            : Store::whereHas('user', fn ($q) => $q->where('role', 'seller'))->first() ?? Store::first();
        if (!$store) {
            $this->error('Tidak ada toko tujuan. Jalankan `php artisan db:seed --class=DemoSeeder` dulu.');
            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            $n = Product::where('store_id', $store->id)->delete();
            $this->warn("Menghapus {$n} produk lama toko {$store->name}.");
        }

        // Peta slug->id kategori (untuk resolusi cepat).
        $catIds = Category::pluck('id', 'slug');
        $lainnya = $catIds['lainnya'] ?? null;

        $limit = (int) $this->option('limit');
        $count = 0; $skipped = 0;

        $fh = fopen($file, 'r');
        $this->info("Mengimpor ke toko: {$store->name} (payout {$store->payout_wallet})");
        $bar = $this->output->createProgressBar($limit > 0 ? $limit : 0);

        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $d = json_decode($line, true);
            if (!is_array($d) || empty($d['name'])) {
                $skipped++;
                continue;
            }

            $slug = $this->mapDepartment($d['department'] ?? null);
            $catId = $catIds[$slug] ?? $lainnya;

            Product::create([
                'name'          => Str::limit((string) $d['name'], 250, ''),
                'description'   => $this->buildDescription($d),
                'price_usdc'    => $this->price($d),
                'stock'         => null,
                'store_id'      => $store->id,
                'seller_wallet' => $store->payout_wallet,
                'category_id'   => $catId,
                'product_id'    => (string) Str::uuid(),
                'image'         => $d['image'] ?? null, // URL remote didukung Product::imageUrl()
            ]);

            $count++;
            if ($limit > 0) {
                $bar->advance();
            }
            if ($limit > 0 && $count >= $limit) {
                break;
            }
        }
        fclose($fh);
        if ($limit > 0) {
            $bar->finish();
            $this->newLine();
        }

        $this->info("Selesai. {$count} produk diimpor" . ($skipped ? ", {$skipped} baris dilewati." : '.'));
        return self::SUCCESS;
    }

    private function mapDepartment(?string $dept): string
    {
        $key = mb_strtolower((string) $dept);
        foreach (self::DEPT_MAP as $needle => $slug) {
            if ($key !== '' && str_contains($key, $needle)) {
                return $slug;
            }
        }
        return 'lainnya';
    }

    /** Harga produk (TLKM). Pakai price; fallback list_price; minimal 1. */
    private function price(array $d): float
    {
        $p = (float) ($d['price'] ?? $d['list_price'] ?? 0);
        return $p > 0 ? round($p, 2) : 1.0;
    }

    private function buildDescription(array $d): string
    {
        $parts = [];
        if (!empty($d['brand']))  { $parts[] = 'Merek: ' . $d['brand']; }
        if (!empty($d['seller'])) { $parts[] = 'Penjual asal: ' . $d['seller']; }
        if (!empty($d['rating'])) { $parts[] = 'Rating sumber: ' . $d['rating']; }
        return implode(' · ', $parts) ?: 'Produk impor.';
    }
}

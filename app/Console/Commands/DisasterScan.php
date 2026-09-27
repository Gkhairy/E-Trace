<?php

namespace App\Console\Commands;

use App\Services\Disaster\DisasterRadar;
use Illuminate\Console\Command;

class DisasterScan extends Command
{
    protected $signature = 'disaster:scan';
    protected $description = 'Radar Bencana: ambil kejadian BMKG/GDACS/berita, nilai dengan AI, buka donasi bila layak.';

    public function handle(DisasterRadar $radar): int
    {
        $s = $radar->scan();
        $this->info("Diambil {$s['fetched']}, baru {$s['new']}: dibuka {$s['opened']}, antrean {$s['pending_review']}, ditolak {$s['rejected']}, duplikat {$s['duplicate']}.");
        return self::SUCCESS;
    }
}

<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Export XLSX generik dari sebuah Blade view (tabel HTML).
 * Dipakai laporan penjualan penjual (harian & bulanan) agar format
 * konsisten dengan versi PDF-nya (blade yang sama).
 */
class ViewExport implements FromView, ShouldAutoSize
{
    public function __construct(
        private string $view,
        private array $data,
    ) {}

    public function view(): View
    {
        return view($this->view, $this->data);
    }
}

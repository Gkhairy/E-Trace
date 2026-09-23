<?php

namespace App\Support;

use App\Models\OrderItem;

/**
 * Satu status yang mudah dipahami penjual, digabung dari dua status teknis item:
 * `status` (escrow on-chain) dan `fulfillment_status` (pengiriman off-chain).
 * Penjual tidak perlu tahu istilah escrow untuk tahu apa langkah berikutnya.
 */
class SellerStatus
{
    /** @return array{label:string, tone:string, hint:string} */
    public static function for(OrderItem $item): array
    {
        $tones = [
            'amber'   => 'bg-amber-50 text-amber-800 ring-amber-200',
            'blue'    => 'bg-blue-50 text-blue-700 ring-blue-200',
            'indigo'  => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'slate'   => 'bg-slate-100 text-slate-600 ring-slate-200',
            'red'     => 'bg-red-50 text-red-700 ring-red-200',
        ];

        [$label, $tone, $hint] = match (true) {
            $item->status === 'completed'
                => ['Selesai, dana cair', 'emerald', 'Pembeli sudah menerima barang dan dana masuk ke wallet toko.'],
            $item->status === 'refunded'
                => ['Dikembalikan', 'slate', 'Dana dikembalikan ke pembeli.'],
            $item->status === 'disputed'
                => ['Sengketa', 'red', 'Pembeli mengajukan keberatan. Pengawas akan memutuskan.'],
            $item->status === 'pending_confirmation'
                => ['Menunggu pembayaran', 'slate', 'Pembayaran sedang dikonfirmasi jaringan.'],
            $item->fulfillment_status === 'processing'
                => ['Sedang dikemas', 'blue', 'Masukkan nomor resi setelah paket diserahkan ke kurir.'],
            $item->fulfillment_status === 'shipped'
                => ['Dalam pengiriman', 'indigo', 'Dana cair setelah pembeli menerima barang.'],
            $item->fulfillment_status === 'delivered'
                => ['Sampai di pembeli', 'indigo', 'Menunggu pembeli mengonfirmasi, lalu dana cair.'],
            default
                => ['Perlu diproses', 'amber', 'Pembeli sudah membayar. Kemas dan kirim pesanan ini.'],
        };

        return ['label' => $label, 'tone' => $tones[$tone], 'hint' => $hint];
    }
}

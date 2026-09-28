<?php

namespace App\Http\Controllers;

use App\Exports\ViewExport;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SellerReportController extends Controller
{
    /** Ambil toko milik penjual yang login (otorisasi). */
    private function store()
    {
        $user = auth()->user();
        abort_unless($user->isSeller(), 403, __('Hanya penjual yang bisa mengunduh laporan toko.'));
        $store = $user->store;
        abort_unless($store, 404, __('Toko belum tersedia untuk akun ini.'));
        return $store;
    }

    /**
     * Unduh laporan penjualan toko.
     * Query: type=daily|monthly, format=xlsx|pdf, from=Y-m-d, to=Y-m-d.
     * Data HANYA dari transaksi (order item) yang masuk ke toko penjual.
     */
    public function download(Request $request)
    {
        $store = $this->store();

        $data = $request->validate([
            'type'   => 'required|in:daily,monthly',
            'format' => 'required|in:xlsx,pdf',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
        ]);

        // Rentang default: bulan berjalan sampai hari ini.
        $from = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfMonth();
        $to   = !empty($data['to'])   ? Carbon::parse($data['to'])->endOfDay()     : now()->endOfDay();
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        // Item milik toko ini (cocokkan wallet payout) dalam rentang.
        $items = OrderItem::with(['order.user', 'product'])
            ->where('seller_wallet', $store->payout_wallet)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $payload = $data['type'] === 'daily'
            ? $this->dailyData($store, $items, $from, $to)
            : $this->monthlyData($store, $items, $from, $to);

        $view = $data['type'] === 'daily' ? 'seller.reports.daily' : 'seller.reports.monthly';
        $slug = $store->slug ?: Str::slug($store->name ?: 'toko');
        $base = __('Laporan') . '-' . ($data['type'] === 'daily' ? __('Harian') : __('Bulanan')) . '-' . $slug
              . '-' . $from->format('Ymd') . '-' . $to->format('Ymd');

        if ($data['format'] === 'pdf') {
            $pdf = Pdf::loadView($view, $payload)->setPaper('a4', 'landscape');
            return $pdf->download($base . '.pdf');
        }

        return Excel::download(new ViewExport($view, $payload), $base . '.xlsx');
    }

    /** Bangun data laporan HARIAN (rincian per transaksi, gaya buku besar). */
    private function dailyData($store, $items, Carbon $from, Carbon $to): array
    {
        $rows = $items->map(function (OrderItem $it) {
            return [
                'date'    => $it->created_at,
                'order'   => optional($it->order)->order_id ?: ('TX-' . $it->id),
                'desc'    => trim((optional($it->product)->name ?: __('Produk.one')) . ' - ' . (optional(optional($it->order)->user)->name ?: __('Pembeli'))),
                'amount'  => (float) $it->amount,
                'status'  => $it->status,
            ];
        });

        // Total penjualan = tanpa item refund; jumlah transaksi = semua baris.
        $totalSales = $items->where('status', '!=', 'refunded')->sum(fn ($i) => (float) $i->amount);

        return [
            'store'      => $store,
            'from'       => $from,
            'to'         => $to,
            'rows'       => $rows,
            'count'      => $items->count(),
            'totalSales' => (float) $totalSales,
        ];
    }

    /** Bangun data laporan BULANAN (rekap per bulan). */
    private function monthlyData($store, $items, Carbon $from, Carbon $to): array
    {
        $rows = $items
            ->groupBy(fn (OrderItem $i) => $i->created_at->format('Y-m'))
            ->map(function ($group, $ym) {
                [$year, $month] = explode('-', $ym);
                return [
                    'month'      => Carbon::create((int) $year, (int) $month, 1)->translatedFormat('F'), // nama bulan sesuai bahasa
                    'month_num'  => (int) $month,
                    'year'       => (int) $year,
                    'total'      => (float) $group->where('status', '!=', 'refunded')->sum(fn ($i) => (float) $i->amount),
                    'count'      => $group->count(),
                ];
            })
            ->sortBy(fn ($r) => sprintf('%04d%02d', $r['year'], $r['month_num']))
            ->values();

        return [
            'store'       => $store,
            'from'        => $from,
            'to'          => $to,
            'rows'        => $rows,
            'grandTotal'  => (float) $rows->sum('total'),
            'grandCount'  => (int) $rows->sum('count'),
        ];
    }
}

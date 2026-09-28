@php
    // Tanpa pemisah ribuan agar sel Excel tetap NUMERIK (bisa dijumlah/urut).
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 6, '.', ''), '0'), '.') ?: '0';
    $statusLabel = [
        'paid'      => __('Dibayar (escrow)'),
        'completed' => __('Selesai'),
        'refunded'  => __('Refund'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1e293b; font-size: 11px; }
    table { border-collapse: collapse; width: 100%; }
    .title { font-size: 16px; font-weight: bold; color: #0f172a; }
    .sub { font-size: 12px; font-weight: bold; }
    .meta { font-size: 11px; color: #475569; }
    th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
    th { background: #eff6ff; color: #1e3a8a; font-size: 11px; }
    td.num, th.num { text-align: right; }
    tr.head-row td { border: none; padding: 2px 0; }
    tr.total td { background: #f1f5f9; font-weight: bold; }
    .muted { color: #64748b; }
</style>
</head>
<body>
<table>
    <tr class="head-row"><td colspan="5" class="title">{{ $store->name }}</td></tr>
    <tr class="head-row"><td colspan="5" class="sub">{{ __('Laporan Penjualan (Harian)') }}</td></tr>
    <tr class="head-row"><td colspan="5" class="meta">{{ __('Periode: Dari :from ke :to', ['from' => $from->format('d M Y'), 'to' => $to->format('d M Y')]) }}</td></tr>
    <tr class="head-row"><td colspan="5" class="meta muted">{{ __('Sumber: transaksi (escrow) di aplikasi E-Trace. Nilai dalam TLKM.') }}</td></tr>
    <tr class="head-row"><td colspan="5">&nbsp;</td></tr>

    <tr>
        <th>{{ __('Tanggal') }}</th>
        <th>{{ __('No. Order / Tx') }}</th>
        <th>{{ __('Keterangan') }}</th>
        <th class="num">{{ __('Nilai (TLKM)') }}</th>
        <th>{{ __('Status') }}</th>
    </tr>

    @forelse($rows as $r)
        <tr>
            <td>{{ $r['date']->format('d M Y H:i') }}</td>
            <td>{{ $r['order'] }}</td>
            <td>{{ $r['desc'] }}</td>
            <td class="num">{{ $fmt($r['amount']) }}</td>
            <td>{{ $statusLabel[$r['status']] ?? $r['status'] }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">{{ __('Tidak ada transaksi pada rentang ini.') }}</td></tr>
    @endforelse

    <tr class="total">
        <td colspan="2">{{ __('TOTAL') }}</td>
        <td>{{ __(':n transaksi', ['n' => $count]) }}</td>
        <td class="num">{{ $fmt($totalSales) }}</td>
        <td>{{ __('Penjualan (tanpa refund)') }}</td>
    </tr>
</table>
</body>
</html>

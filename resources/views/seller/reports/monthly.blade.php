@php
    // Tanpa pemisah ribuan agar sel Excel tetap NUMERIK (bisa dijumlah/urut).
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 6, '.', ''), '0'), '.') ?: '0';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1e293b; font-size: 11px; }
    table { border-collapse: collapse; width: 100%; }
    .title { font-size: 16px; font-weight: bold; color: #0f172a; }
    .sub { font-size: 12px; font-weight: bold; }
    .meta { font-size: 11px; color: #475569; }
    th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
    th { background: #eff6ff; color: #1e3a8a; }
    td.num, th.num { text-align: right; }
    tr.head-row td { border: none; padding: 2px 0; }
    tr.total td { background: #f1f5f9; font-weight: bold; }
    .muted { color: #64748b; }
</style>
</head>
<body>
<table>
    <tr class="head-row"><td colspan="4" class="title">{{ $store->name }}</td></tr>
    <tr class="head-row"><td colspan="4" class="sub">Laporan Penjualan (Rekap Bulanan)</td></tr>
    <tr class="head-row"><td colspan="4" class="meta">Periode: Dari {{ $from->format('d M Y') }} ke {{ $to->format('d M Y') }}</td></tr>
    <tr class="head-row"><td colspan="4" class="meta muted">Sumber: transaksi (escrow) di aplikasi E-Trace. Nilai dalam TLKM (tanpa refund).</td></tr>
    <tr class="head-row"><td colspan="4">&nbsp;</td></tr>

    <tr>
        <th>Bulan</th>
        <th>Tahun</th>
        <th class="num">Total Penjualan (TLKM)</th>
        <th class="num">Jumlah Transaksi</th>
    </tr>

    @forelse($rows as $r)
        <tr>
            <td>{{ $r['month'] }}</td>
            <td>{{ $r['year'] }}</td>
            <td class="num">{{ $fmt($r['total']) }}</td>
            <td class="num">{{ $r['count'] }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="muted">Tidak ada transaksi pada rentang ini.</td></tr>
    @endforelse

    <tr class="total">
        <td colspan="2">TOTAL KESELURUHAN</td>
        <td class="num">{{ $fmt($grandTotal) }}</td>
        <td class="num">{{ $grandCount }}</td>
    </tr>
</table>
</body>
</html>

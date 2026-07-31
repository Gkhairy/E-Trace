@php $expl = 'https://sepolia.etherscan.io/tx/'.$order->tx_hash; $addr = $order->shippingAddress; @endphp
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;color:#1e293b;">
    <div style="background:#0f172a;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
        <h2 style="margin:0;font-size:18px;">Pesanan Baru Masuk</h2>
        <p style="margin:6px 0 0;font-size:13px;opacity:.85;">MyCryptoShop — segera siapkan pengiriman</p>
    </div>
    <div style="border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:24px;">
        <p style="margin:0 0 14px;">Ada pembeli memesan produk kamu. Dana ditahan escrow dan akan dilepas setelah pembeli konfirmasi barang diterima.</p>

        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="text-align:left;color:#64748b;font-size:12px;text-transform:uppercase;">
                    <th style="padding:8px 0;">Produk</th>
                    <th style="padding:8px 0;text-align:right;">Nominal (bruto)</th>
                    <th style="padding:8px 0;text-align:right;">Est. diterima*</th>
                </tr>
            </thead>
            <tbody>
                @php $totalGross = 0; @endphp
                @foreach($items as $it)
                    @php $totalGross += (float) $it->amount; $net = (float)$it->amount * 0.99; @endphp
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:10px 0;">{{ $it->product->name ?? '—' }}</td>
                        <td style="padding:10px 0;text-align:right;">{{ rtrim(rtrim(number_format($it->amount, 2), '0'), '.') }} TLKM</td>
                        <td style="padding:10px 0;text-align:right;color:#059669;">{{ rtrim(rtrim(number_format($net, 2), '0'), '.') }} TLKM</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size:12px;color:#94a3b8;margin:6px 0 18px;">*Estimasi setelah fee platform 1% (dipotong saat dana dilepas ke kamu, bukan saat pembeli bayar).</p>

        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:14px 16px;font-size:13px;">
            <p style="margin:0 0 6px;font-weight:bold;">Alamat Pengiriman</p>
            @if($addr)
                <p style="margin:0;line-height:1.6;">
                    {{ $addr->recipient_name }} ({{ $addr->phone }})<br>
                    {{ $addr->address }}<br>
                    {{ $addr->city }} {{ $addr->postal_code }}
                    @if($addr->notes)<br><i>Catatan: {{ $addr->notes }}</i>@endif
                </p>
            @else
                <p style="margin:0;color:#94a3b8;">Alamat belum tersedia.</p>
            @endif
        </div>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;font-size:13px;margin-top:12px;">
            <p style="margin:0 0 4px;"><b>Order ID:</b> <span style="font-family:monospace;">{{ $order->order_id }}</span></p>
            <p style="margin:0;"><b>Bukti on-chain:</b> <a href="{{ $expl }}" style="color:#2563eb;">Lihat transaksi di Etherscan ↗</a></p>
        </div>

        <p style="font-size:12px;color:#94a3b8;margin:20px 0 0;">Email ini dikirim otomatis oleh MyCryptoShop.</p>
    </div>
</div>

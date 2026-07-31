@php $expl = 'https://sepolia.etherscan.io/tx/'.$order->tx_hash; @endphp
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;color:#1e293b;">
    <div style="background:#2563eb;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
        <h2 style="margin:0;font-size:18px;">Struk Pembelian</h2>
        <p style="margin:6px 0 0;font-size:13px;opacity:.85;">MyCryptoShop — pembayaran on-chain</p>
    </div>
    <div style="border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:24px;">
        <p style="margin:0 0 14px;">Terima kasih, pesananmu tercatat.</p>

        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="text-align:left;color:#64748b;font-size:12px;text-transform:uppercase;">
                    <th style="padding:8px 0;">Produk</th>
                    <th style="padding:8px 0;text-align:center;">Qty*</th>
                    <th style="padding:8px 0;text-align:right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $it)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:10px 0;">{{ $it->product->name ?? '—' }}</td>
                        <td style="padding:10px 0;text-align:center;color:#64748b;">—</td>
                        <td style="padding:10px 0;text-align:right;">{{ rtrim(rtrim(number_format($it->amount, 2), '0'), '.') }} TLKM</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid #e2e8f0;">
                    <td colspan="2" style="padding:12px 0;font-weight:bold;">Total</td>
                    <td style="padding:12px 0;text-align:right;font-weight:bold;color:#2563eb;">{{ rtrim(rtrim(number_format($order->total ?? $order->amount, 2), '0'), '.') }} TLKM</td>
                </tr>
            </tfoot>
        </table>

        <p style="font-size:12px;color:#94a3b8;margin:6px 0 18px;">*Nominal per baris sudah termasuk kuantitas.</p>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;font-size:13px;">
            <p style="margin:0 0 4px;"><b>Order ID:</b> <span style="font-family:monospace;">{{ $order->order_id }}</span></p>
            <p style="margin:0 0 4px;"><b>Status:</b> Dana ditahan escrow — dilepas ke penjual setelah kamu konfirmasi barang diterima.</p>
            <p style="margin:0;"><b>Bukti on-chain:</b> <a href="{{ $expl }}" style="color:#2563eb;">Lihat transaksi di Etherscan ↗</a></p>
        </div>

        @if($order->shippingAddress)
            <p style="font-size:12px;color:#64748b;margin:16px 0 0;">
                Dikirim ke: {{ $order->shippingAddress->recipient_name }},
                {{ $order->shippingAddress->address }}, {{ $order->shippingAddress->city }} {{ $order->shippingAddress->postal_code }}
            </p>
        @endif

        <p style="font-size:12px;color:#94a3b8;margin:20px 0 0;">Email ini dikirim otomatis oleh MyCryptoShop.</p>
    </div>
</div>

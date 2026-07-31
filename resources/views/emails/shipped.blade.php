@php $addr = $item->order->shippingAddress ?? null; $expl = 'https://sepolia.etherscan.io/tx/'.$item->order->tx_hash; @endphp
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;color:#1e293b;">
    <div style="background:#4f46e5;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
        <h2 style="margin:0;font-size:18px;">Pesananmu Dikirim</h2>
        <p style="margin:6px 0 0;font-size:13px;opacity:.85;">MyCryptoShop</p>
    </div>
    <div style="border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:24px;">
        <p style="margin:0 0 14px;">Kabar baik! Penjual sudah mengirim <b>{{ $item->product->name ?? 'produk' }}</b>.</p>

        <div style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:10px;padding:14px 16px;font-size:14px;">
            <p style="margin:0 0 6px;"><b>No. Resi:</b> <span style="font-family:monospace;">{{ $item->tracking_number ?: '-' }}</span></p>
            @if($item->courier)<p style="margin:0 0 6px;"><b>Kurir:</b> {{ $item->courier }}</p>@endif
            @if($addr)<p style="margin:0;"><b>Tujuan:</b> {{ $addr->recipient_name }}, {{ $addr->city }} {{ $addr->postal_code }}</p>@endif
        </div>

        <p style="font-size:13px;color:#475569;margin:16px 0 0;">
            Setelah barang diterima, buka halaman <b>Order</b> dan klik <b>Konfirmasi Terima</b> agar dana dilepas ke penjual.
        </p>
        <p style="font-size:12px;color:#94a3b8;margin:14px 0 0;">Order {{ $item->order->order_id }} · <a href="{{ $expl }}" style="color:#2563eb;">bukti on-chain ↗</a></p>
    </div>
</div>

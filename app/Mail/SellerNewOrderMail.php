<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerNewOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $sellerWallet) {}

    public function build()
    {
        // Hanya item milik penjual ini + alamat kirim (penjual butuh untuk pengiriman).
        $items = OrderItem::with('product')
            ->where('order_ref_id', $this->order->id)
            ->where('seller_wallet', $this->sellerWallet)
            ->get();

        $this->order->loadMissing('shippingAddress');

        return $this
            ->subject('Pesanan Baru Masuk — MyCryptoShop')
            ->view('emails.seller_new_order')
            ->with([
                'order'       => $this->order,
                'items'       => $items,
                'sellerWallet'=> $this->sellerWallet,
            ]);
    }
}

<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build()
    {
        $this->order->loadMissing(['items.product', 'shippingAddress']);

        return $this
            ->subject('Struk Pembelian — MyCryptoShop')
            ->view('emails.order_receipt')
            ->with(['order' => $this->order]);
    }
}

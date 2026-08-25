<?php

namespace App\Mail;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrderItem $item) {}

    public function build()
    {
        $this->item->loadMissing(['order.shippingAddress', 'product']);

        return $this
            ->subject('Pesananmu Dikirim 🚚 — E-Trace')
            ->view('emails.shipped')
            ->with(['item' => $this->item]);
    }
}

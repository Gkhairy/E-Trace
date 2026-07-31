<?php

namespace App\Jobs;

use App\Mail\OrderReceiptMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrderReceipt implements ShouldQueue
{
    use Queueable;

    // Simpan ID saja (bukan model) supaya selalu ambil data terbaru saat worker jalan.
    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::with('user')->find($this->orderId);

        // Idempotent: kirim sekali saja.
        if (!$order || $order->buyer_notified_at) {
            return;
        }
        $email = $order->user?->email;
        if (!$email) {
            return;
        }

        Mail::to($email)->send(new OrderReceiptMail($order));

        $order->forceFill(['buyer_notified_at' => now()])->save();
    }
}

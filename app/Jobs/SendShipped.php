<?php

namespace App\Jobs;

use App\Mail\ShippedMail;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendShipped implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId) {}

    public function handle(): void
    {
        $item = OrderItem::with(['order.user', 'product'])->find($this->itemId);
        if (!$item || !$item->order || !$item->order->user?->email) {
            return;
        }
        Mail::to($item->order->user->email)->send(new ShippedMail($item));
    }
}

<?php

namespace App\Jobs;

use App\Mail\SellerNewOrderMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSellerNewOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId, public string $sellerWallet) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (!$order) {
            return;
        }

        $items = OrderItem::where('order_ref_id', $order->id)
            ->where('seller_wallet', $this->sellerWallet)
            ->get();

        if ($items->isEmpty() || $items->every(fn ($i) => $i->seller_notified_at)) {
            return; // tidak ada item, atau sudah dinotif (idempotent)
        }

        // Tentukan email tujuan: utamakan email kontak Toko (per wallet payout),
        // fallback ke email akun user yang wallet-nya = seller_wallet produk.
        $wallet = strtolower($this->sellerWallet);
        $email  = Store::where('payout_wallet', $wallet)->value('contact_email');

        if (!$email) {
            $email = User::where('wallet_address', $wallet)->value('email');
        }

        if (!$email) {
            // Tidak ada email kontak toko maupun akun terdaftar.
            // Tandai supaya tidak retry selamanya.
            OrderItem::whereIn('id', $items->pluck('id'))->update(['seller_notified_at' => now()]);
            return;
        }

        Mail::to($email)->send(new SellerNewOrderMail($order, $this->sellerWallet));

        OrderItem::whereIn('id', $items->pluck('id'))->update(['seller_notified_at' => now()]);
    }
}

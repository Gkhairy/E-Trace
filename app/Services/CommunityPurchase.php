<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\CommunityProposal;
use App\Models\CommunityWallet;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

/**
 * Pembelian memakai DANA KOMUNITAS (dompet multisig Mode B).
 *
 * Alur: anggota mengusulkan di checkout -> snapshot keranjang dikunci di proposal
 * (otoritatif dari server, bukan dari browser) -> penanda tangan menyetujui ->
 * saat bulat, dompet komunitas membayar escrow (approve + payCart) dan order dicatat.
 *
 * Catatan penting: karena `payCart` dipanggil dompet komunitas, PEMBELI on-chain =
 * alamat dompet komunitas. Konfirmasi/refund/sengketa karenanya harus ditandatangani
 * memakai kunci dompet komunitas (custodial) — lihat `orders.community_wallet_id`.
 */
class CommunityPurchase
{
    private const ERC20_ABI = [
        ['inputs' => [['name' => 's', 'type' => 'address'], ['name' => 'v', 'type' => 'uint256']], 'name' => 'approve', 'outputs' => [['type' => 'bool']], 'type' => 'function'],
    ];
    private const GATEWAY_ABI = [
        ['inputs' => [['name' => 'sellers', 'type' => 'address[]'], ['name' => 'amounts', 'type' => 'uint256[]'], ['name' => 'productIds', 'type' => 'string[]'], ['name' => 'orderId', 'type' => 'string']], 'name' => 'payCart', 'outputs' => [], 'type' => 'function'],
    ];

    /**
     * Kunci isi keranjang pembeli jadi snapshot yang akan dibayar bila disetujui.
     * Dihitung di server supaya harga/penjual tak bisa dimanipulasi dari browser.
     */
    public function snapshot(int $buyerId, ShippingAddress $addr, bool $insured, ShippingService $shipping): array
    {
        $items = CartItem::with('product')->where('user_id', $buyerId)->get();
        abort_if($items->isEmpty(), 422, 'Keranjang kosong.');

        $sellers = $lines = $productIds = $amounts = [];
        $total = '0';
        foreach ($items->values() as $i => $it) {
            $p = $it->product;
            abort_unless($p && $p->seller_wallet, 422, 'Produk tidak valid dalam keranjang.');
            $amount = number_format((float) $p->price_usdc * $it->quantity, 6, '.', '');

            $sellers[]    = strtolower($p->seller_wallet);
            $amounts[]    = $amount;
            $productIds[] = (string) $p->product_id;
            $lines[] = [
                'item_index'    => $i,
                'db_product_id' => $p->id,
                'product_uuid'  => (string) $p->product_id,
                'name'          => $p->name,
                'seller_wallet' => strtolower($p->seller_wallet),
                'qty'           => (int) $it->quantity,
                'price'         => (float) $p->price_usdc,
                'amount'        => $amount,
            ];
            $total = bcadd($total, $amount, 6);
        }

        // Ongkir + ETA per penjual (dasar Garansi Tepat Waktu).
        $buyerCity = $addr->city ?? null;
        $shipTlkm = 0.0;
        $etaMax = 1;
        foreach (collect($sellers)->unique() as $sw) {
            $store = Store::where('payout_wallet', $sw)->first();
            $est = $shipping->estimate($store?->origin_address, $buyerCity);
            $shipTlkm += (float) $est['fee_tlkm'];
            $etaMax = max($etaMax, $shipping->etaDays($store?->origin_address, $buyerCity));
        }

        return [
            'order_id'            => 'COMM-' . now()->timestamp . random_int(100, 999),
            'buyer_user_id'       => $buyerId,
            'shipping_address_id' => $addr->id,
            'sellers'             => $sellers,
            'amounts'             => $amounts,
            'product_ids'         => $productIds,
            'lines'               => $lines,
            'total'               => $total,
            'shipping_tlkm'       => round($shipTlkm, 6),
            'eta_days'            => $etaMax,
            'is_insured'          => $insured,
        ];
    }

    /**
     * Dipanggil setelah persetujuan bulat: bayar escrow dari dompet komunitas lalu
     * catat Order + OrderItem. Return tx hash payCart.
     */
    public function payAndRecord(CommunityWallet $wallet, CommunityProposal $p, ChainSigner $signer): string
    {
        $m = $p->meta ?: [];
        abort_if(empty($m['lines']), 500, 'Snapshot pembelian tidak lengkap.');

        $gateway = config('chain.gateway');
        $zero = '0x0000000000000000000000000000000000000000';
        abort_if(!$gateway || $gateway === $zero, 422, 'Alamat gateway escrow belum dikonfigurasi.');

        // Idempoten: kalau order sudah tercatat, jangan bayar dua kali.
        if ($existing = Order::where('order_id', $m['order_id'])->first()) {
            return (string) $existing->tx_hash;
        }

        $priv = (new EmbeddedWallet())->decryptServer($wallet->only(['wallet_enc', 'wallet_salt', 'wallet_iv', 'wallet_tag']));
        abort_unless($priv, 500, 'Kunci dompet komunitas gagal dibuka.');

        $totalWei = $signer->toWei((string) $m['total']);
        $amountsWei = array_map(fn ($a) => $signer->toWei((string) $a), $m['amounts']);

        // 1) Allowance TLKM untuk gateway (approve bila kurang), lalu bayar cart.
        $this->ensureAllowance($signer, $priv, $gateway, $totalWei);
        // Encoder manual: encoder bawaan web3.php salah meng-encode string[] (lihat AbiEncoder).
        $data = \App\Support\AbiEncoder::payCart($m['sellers'], $amountsWei, $m['product_ids'], $m['order_id']);
        $hash = $signer->sendRaw($priv, $gateway, '0x0', $data);
        $this->requireSuccess($signer, $hash, 'Pembayaran escrow gagal di blockchain (transaksi revert) — dana komunitas tidak berkurang.');

        // 2) Catat order (pembeli = pengusul; dana dari dompet komunitas).
        DB::transaction(function () use ($m, $wallet, $hash) {
            $order = Order::create([
                'user_id'             => $m['buyer_user_id'],
                'community_wallet_id' => $wallet->id,
                'product_id'          => $m['lines'][0]['db_product_id'], // legacy (kolom NOT NULL)
                'order_id'            => $m['order_id'],
                'tx_hash'             => $hash,
                'amount'              => $m['total'],
                'total'               => $m['total'],
                'shipping_address_id' => $m['shipping_address_id'],
                'status'              => 'paid',
            ]);

            foreach ($m['lines'] as $ln) {
                OrderItem::create([
                    'order_ref_id'  => $order->id,
                    'product_id'    => $ln['db_product_id'],
                    'seller_wallet' => $ln['seller_wallet'],
                    'amount'        => $ln['amount'],
                    'quantity'      => $ln['qty'],
                    'item_index'    => $ln['item_index'],
                    'status'        => 'paid',
                ]);
                $prod = Product::find($ln['db_product_id']);
                if ($prod && $prod->stock !== null) {
                    $prod->stock = max(0, $prod->stock - (int) $ln['qty']);
                    $prod->save();
                }
            }

            $ins   = config('chain.insurance');
            $insOn = (bool) ($ins['enabled'] ?? false) && !empty($ins['pool_wallet']);
            $order->shipping_tlkm = $m['shipping_tlkm'];
            $order->eta_days      = $m['eta_days'];
            $order->promised_date = now()->addDays((int) $m['eta_days'] + (int) ($ins['eta_buffer_days'] ?? 3));
            if (!empty($m['is_insured']) && $insOn) {
                $order->is_insured       = true;
                $order->insurance_status = 'active';
            }
            $order->save();

            // Keranjang pembeli dikosongkan setelah dibayar.
            CartItem::where('user_id', $m['buyer_user_id'])->delete();
        });

        return $hash;
    }

    /** Pastikan allowance dompet komunitas cukup untuk gateway. */
    private function ensureAllowance(ChainSigner $s, string $priv, string $spender, string $amountWei): void
    {
        $token = config('chain.tlkm');
        $owner = (new EmbeddedWallet())->addressFromPrivate($priv);
        if (bccomp($s->allowance($token, $owner, $spender), $amountWei) >= 0) {
            return;
        }
        $hash = $s->sendContractCall($priv, $token, self::ERC20_ABI, 'approve', [$spender, $amountWei]);
        $this->requireSuccess($s, $hash, 'Persetujuan (approve) TLKM gagal di blockchain.');
    }

    /** Tunggu receipt dan PASTIKAN sukses. Tx yang revert TIDAK boleh dianggap berhasil. */
    private function requireSuccess(ChainSigner $s, string $hash, string $message): void
    {
        $r = $s->waitReceipt($hash);
        abort_if(!$r, 422, 'Transaksi belum terkonfirmasi di blockchain. Coba lagi sebentar. (tx: ' . $hash . ')');
        $status = strtolower((string) ($r['status'] ?? ''));
        abort_if(!in_array($status, ['0x1', '1'], true), 422, $message . ' (tx: ' . $hash . ')');
    }
}

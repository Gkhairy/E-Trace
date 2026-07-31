<?php

namespace App\Services;

use kornrunner\Keccak;
use Illuminate\Support\Facades\Http;

/**
 * Verifikasi transaksi PaymentGateway v3 langsung ke jaringan (RPC Sepolia).
 * Sumber kebenaran = on-chain. Data dari browser TIDAK dipercaya mentah.
 */
class SepoliaVerifier
{
    private string $rpc;
    private string $gateway;

    public function __construct()
    {
        $this->rpc     = config('chain.rpc_url');
        $this->gateway = strtolower(config('chain.gateway'));
    }

    private function rpc(string $method, array $params)
    {
        $res = Http::timeout(12)->acceptJson()->post($this->rpc, [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
        ]);
        return $res->ok() ? ($res->json()['result'] ?? null) : null;
    }

    public function latestBlock(): ?int
    {
        $r = $this->rpc('eth_blockNumber', []);
        return $r ? hexdec($r) : null;
    }

    public function getReceipt(string $txHash): ?array
    {
        return $this->rpc('eth_getTransactionReceipt', [$txHash]);
    }

    // Saldo TLKM sebuah alamat (dari kontrak token, live). Return desimal string atau null.
    public function tlkmBalance(string $address): ?string
    {
        $tlkm = strtolower(config('chain.tlkm'));
        $sel  = substr(Keccak::hash('balanceOf(address)', 256), 0, 8);
        $arg  = str_pad(substr(strtolower($address), 2), 64, '0', STR_PAD_LEFT);
        $res  = $this->rpc('eth_call', [['to' => $tlkm, 'data' => '0x' . $sel . $arg], 'latest']);
        if (!$res || $res === '0x') {
            return null;
        }
        $wei = gmp_strval(gmp_init($res));
        return bcdiv($wei, '1000000000000000000', 4);
    }

    // --- encode getItem(string,uint256) ---
    private function encodeGetItem(string $orderId, int $index): string
    {
        $sel    = substr(Keccak::hash('getItem(string,uint256)', 256), 0, 8);
        $offset = str_pad('40', 64, '0', STR_PAD_LEFT);              // offset ke string = 0x40
        $idx    = str_pad(dechex($index), 64, '0', STR_PAD_LEFT);
        $len    = str_pad(dechex(strlen($orderId)), 64, '0', STR_PAD_LEFT);
        $hex    = bin2hex($orderId);
        $data   = str_pad($hex, (int) (ceil(strlen($hex) / 64) * 64), '0', STR_PAD_RIGHT);
        return '0x' . $sel . $offset . $idx . $len . $data;
    }

    /**
     * Baca 1 item escrow on-chain. Return: buyer, seller, amount_wei, amount_tlkm,
     * productId, status(int), atau null bila gagal.
     */
    public function getItem(string $orderId, int $index): ?array
    {
        $res = $this->rpc('eth_call', [[
            'to'   => $this->gateway,
            'data' => $this->encodeGetItem($orderId, $index),
        ], 'latest']);
        if (!$res || strlen($res) < 66) {
            return null;
        }
        $h = substr($res, 2);
        $word = fn ($i) => substr($h, $i * 64, 64);

        // word0 = offset tuple (0x20). Tuple mulai word1:
        // buyer(1) seller(2) amount(3) pidOffset(4) createdAt(5) status(6)
        $buyer   = '0x' . substr($word(1), 24);
        $seller  = '0x' . substr($word(2), 24);
        $amtWei  = gmp_strval(gmp_init('0x' . $word(3)));
        $created = hexdec($word(5));
        $status  = hexdec($word(6));

        // productId (string) — offset relatif ke awal tuple (byte 32).
        $productId = '';
        try {
            $pidRel  = hexdec($word(4));
            $pidWord = 1 + intdiv($pidRel, 32);
            $pidLen  = hexdec($word($pidWord));
            if ($pidLen > 0 && $pidLen < 256) {
                $productId = hex2bin(substr($h, ($pidWord + 1) * 64, $pidLen * 2));
            }
        } catch (\Throwable $e) { /* toleran */ }

        return [
            'buyer'      => strtolower($buyer),
            'seller'     => strtolower($seller),
            'amount_wei' => $amtWei,
            'amount_tlkm'=> bcdiv($amtWei, '1000000000000000000', 6),
            'productId'  => $productId,
            'createdAt'  => $created,
            'status'     => $status, // 0 None,1 Paid,2 Completed,3 Refunded
        ];
    }

    /**
     * Verifikasi hybrid sebuah pembayaran cart.
     * $expected: array[ item_index => ['product_id_uuid'=>..,'seller_wallet'=>..] ]
     * Return ['ok'=>bool, 'reason'=>?, 'block_number'=>?, 'confirmations'=>?, 'items'=>[index=>['amount_tlkm'=>..]]]
     */
    public function verifyCart(string $orderId, string $txHash, string $buyerWallet, array $expected): array
    {
        $buyer = strtolower($buyerWallet);

        $receipt = $this->getReceipt($txHash);
        if (!$receipt) {
            return ['ok' => false, 'reason' => 'Transaksi belum ditemukan / belum ter-mine.'];
        }
        if (($receipt['status'] ?? '') !== '0x1') {
            return ['ok' => false, 'reason' => 'Transaksi gagal (reverted) di blockchain.'];
        }
        if (strtolower($receipt['to'] ?? '') !== $this->gateway) {
            return ['ok' => false, 'reason' => 'Transaksi bukan ke kontrak PaymentGateway.'];
        }
        if (strtolower($receipt['from'] ?? '') !== $buyer) {
            return ['ok' => false, 'reason' => 'Wallet pembayar tidak cocok dengan wallet akunmu.'];
        }

        $block = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;
        $latest = $this->latestBlock();
        $confirmations = ($block && $latest) ? max(1, $latest - $block + 1) : 1;

        $items = [];
        foreach ($expected as $index => $exp) {
            $it = $this->getItem($orderId, (int) $index);
            if (!$it) {
                return ['ok' => false, 'reason' => "Item #$index tidak terbaca di kontrak."];
            }
            if ($it['status'] !== 1) {
                return ['ok' => false, 'reason' => "Item #$index tidak berstatus Paid di kontrak."];
            }
            if ($it['buyer'] !== $buyer) {
                return ['ok' => false, 'reason' => "Buyer item #$index tidak cocok."];
            }
            if ($it['seller'] !== strtolower($exp['seller_wallet'])) {
                return ['ok' => false, 'reason' => "Penjual item #$index tidak cocok."];
            }
            if (!empty($exp['product_id_uuid']) && $it['productId'] !== '' && $it['productId'] !== $exp['product_id_uuid']) {
                return ['ok' => false, 'reason' => "Produk item #$index tidak cocok."];
            }
            $items[$index] = ['amount_tlkm' => $it['amount_tlkm'], 'amount_wei' => $it['amount_wei']];
        }

        return [
            'ok' => true,
            'block_number' => $block,
            'confirmations' => $confirmations,
            'items' => $items,
        ];
    }
}

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
     * Baca jumlah item cart on-chain untuk sebuah orderId (mapping publik itemCount(string)).
     * Dipakai untuk memastikan SEMUA item yang dibayar tersimpan (cegah item hilang).
     */
    public function itemCount(string $orderId): ?int
    {
        $sel    = substr(Keccak::hash('itemCount(string)', 256), 0, 8);
        $offset = str_pad('20', 64, '0', STR_PAD_LEFT);
        $len    = str_pad(dechex(strlen($orderId)), 64, '0', STR_PAD_LEFT);
        $hex    = bin2hex($orderId);
        $data   = str_pad($hex, (int) (ceil(strlen($hex) / 64) * 64), '0', STR_PAD_RIGHT);
        $res = $this->rpc('eth_call', [[
            'to'   => $this->gateway,
            'data' => '0x' . $sel . $offset . $len . $data,
        ], 'latest']);
        if (!$res || strlen($res) < 66) {
            return null;
        }
        return (int) hexdec(substr($res, 2, 64));
    }

    private function poolConfigured(): ?string
    {
        $pool = strtolower((string) config('chain.donation_pool'));
        return (!$pool || preg_match('/^0x0+$/', $pool)) ? null : $pool;
    }

    // Saldo campaign yang belum disalurkan (balance[campaignId]) on-chain, dalam TLKM.
    public function campaignBalanceOnChain(string $campaignIdHex): ?string
    {
        $pool = $this->poolConfigured();
        if (!$pool) {
            return null;
        }
        $sel = substr(Keccak::hash('balance(bytes32)', 256), 0, 8);
        $arg = substr($campaignIdHex, 2);
        $res = $this->rpc('eth_call', [['to' => $pool, 'data' => '0x' . $sel . $arg], 'latest']);
        if (!$res || $res === '0x') {
            return null;
        }
        return bcdiv(gmp_strval(gmp_init($res)), '1000000000000000000', 4);
    }

    /**
     * Verifikasi donasi ke sebuah campaign. Cek: sukses, tujuan = kontrak pool,
     * dan event Donated dengan campaignId yang cocok. Return donor + nominal.
     */
    public function verifyDonation(string $txHash, string $expectedIdHex): array
    {
        $pool = $this->poolConfigured();
        if (!$pool) {
            return ['ok' => false, 'reason' => 'Kontrak donasi belum dikonfigurasi.'];
        }

        $receipt = $this->getReceipt($txHash);
        if (!$receipt) {
            return ['ok' => false, 'reason' => 'Transaksi belum ditemukan / belum ter-mine.'];
        }
        if (($receipt['status'] ?? '') !== '0x1') {
            return ['ok' => false, 'reason' => 'Transaksi gagal (reverted) di blockchain.'];
        }
        if (strtolower($receipt['to'] ?? '') !== $pool) {
            return ['ok' => false, 'reason' => 'Transaksi bukan ke kontrak donasi.'];
        }

        // Donated(bytes32 indexed campaignId, address indexed donor, uint256 amount, uint256 ts)
        $topic0 = '0x' . Keccak::hash('Donated(bytes32,address,uint256,uint256)', 256);
        $eid    = strtolower($expectedIdHex);
        $donor = null; $amtWei = null;
        foreach (($receipt['logs'] ?? []) as $log) {
            if (strtolower($log['address'] ?? '') !== $pool) {
                continue;
            }
            $topics = $log['topics'] ?? [];
            if (count($topics) < 3 || strtolower($topics[0]) !== strtolower($topic0)) {
                continue;
            }
            if (strtolower($topics[1]) !== $eid) {
                continue; // campaign lain
            }
            $donor  = '0x' . substr($topics[2], -40);                 // indexed donor
            $amtWei = gmp_strval(gmp_init('0x' . substr(substr($log['data'] ?? '0x', 2), 0, 64)));
            break;
        }

        if ($donor === null || $amtWei === null) {
            return ['ok' => false, 'reason' => 'Event donasi untuk campaign ini tidak ditemukan.'];
        }

        $block = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;

        return [
            'ok'           => true,
            'donor'        => strtolower($donor),
            'amount_wei'   => $amtWei,
            'amount_tlkm'  => bcdiv($amtWei, '1000000000000000000', 6),
            'block_number' => $block,
        ];
    }

    /**
     * Verifikasi penyaluran (Disbursed) sebuah campaign oleh validator.
     * Cek campaignId cocok; return alamat tujuan (to), pemicu (by), nominal.
     */
    public function verifyDisbursement(string $txHash, string $expectedIdHex): array
    {
        $pool = $this->poolConfigured();
        if (!$pool) {
            return ['ok' => false, 'reason' => 'Kontrak donasi belum dikonfigurasi.'];
        }

        $receipt = $this->getReceipt($txHash);
        if (!$receipt) {
            return ['ok' => false, 'reason' => 'Transaksi belum ditemukan / belum ter-mine.'];
        }
        if (($receipt['status'] ?? '') !== '0x1') {
            return ['ok' => false, 'reason' => 'Transaksi gagal (reverted) di blockchain.'];
        }
        if (strtolower($receipt['to'] ?? '') !== $pool) {
            return ['ok' => false, 'reason' => 'Transaksi bukan ke kontrak donasi.'];
        }

        // Disbursed(bytes32 indexed campaignId, address indexed to, uint256 amount, address by, uint256 ts)
        $topic0 = '0x' . Keccak::hash('Disbursed(bytes32,address,uint256,address,uint256)', 256);
        $eid    = strtolower($expectedIdHex);
        $by = null; $to = null; $amtWei = null;
        foreach (($receipt['logs'] ?? []) as $log) {
            if (strtolower($log['address'] ?? '') !== $pool) {
                continue;
            }
            $topics = $log['topics'] ?? [];
            if (count($topics) < 3 || strtolower($topics[0]) !== strtolower($topic0)) {
                continue;
            }
            if (strtolower($topics[1]) !== $eid) {
                continue;
            }
            $to     = '0x' . substr($topics[2], -40);
            $data   = substr($log['data'] ?? '0x', 2);
            $amtWei = gmp_strval(gmp_init('0x' . substr($data, 0, 64)));   // word0 = amount
            $by     = '0x' . substr(substr($data, 64, 64), -40);          // word1 = by (address)
            break;
        }

        if ($to === null || $amtWei === null) {
            return ['ok' => false, 'reason' => 'Event penyaluran untuk campaign ini tidak ditemukan.'];
        }

        $block = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;

        return [
            'ok'           => true,
            'by'           => strtolower($by),
            'to'           => strtolower($to),
            'amount_wei'   => $amtWei,
            'amount_tlkm'  => bcdiv($amtWei, '1000000000000000000', 6),
            'block_number' => $block,
        ];
    }

    /**
     * Verifikasi transfer TLKM P2P (ERC20 transfer). Cek from cocok, ambil to + nominal.
     * Return ['ok'=>bool,'reason'=>?, 'from'=>?, 'to'=>?, 'amount_wei'=>?, 'amount_tlkm'=>?, 'block_number'=>?]
     */
    public function verifyTransfer(string $txHash, string $expectedFrom): array
    {
        $tlkm = strtolower((string) config('chain.tlkm'));
        $from = strtolower($expectedFrom);

        $receipt = $this->getReceipt($txHash);
        if (!$receipt) {
            return ['ok' => false, 'reason' => 'Transaksi belum ditemukan / belum ter-mine.'];
        }
        if (($receipt['status'] ?? '') !== '0x1') {
            return ['ok' => false, 'reason' => 'Transaksi gagal (reverted) di blockchain.'];
        }

        // Transfer(address indexed from, address indexed to, uint256 value)
        $topic0 = '0x' . Keccak::hash('Transfer(address,address,uint256)', 256);
        $to = null; $amtWei = null;
        foreach (($receipt['logs'] ?? []) as $log) {
            if (strtolower($log['address'] ?? '') !== $tlkm) {
                continue;
            }
            $topics = $log['topics'] ?? [];
            if (count($topics) < 3 || strtolower($topics[0]) !== strtolower($topic0)) {
                continue;
            }
            if (strtolower('0x' . substr($topics[1], -40)) !== $from) {
                continue; // pengirim tidak cocok
            }
            $to     = '0x' . substr($topics[2], -40);
            $amtWei = gmp_strval(gmp_init('0x' . substr($log['data'] ?? '0x', 2, 64)));
            break;
        }

        if ($to === null || $amtWei === null) {
            return ['ok' => false, 'reason' => 'Event transfer TLKM tidak ditemukan / pengirim tidak cocok.'];
        }

        $block = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;

        return [
            'ok'           => true,
            'from'         => $from,
            'to'           => strtolower($to),
            'amount_wei'   => $amtWei,
            'amount_tlkm'  => bcdiv($amtWei, '1000000000000000000', 6),
            'block_number' => $block,
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

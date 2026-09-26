<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\ChainSigner;
use App\Services\EmbeddedWallet;
use App\Services\GasTopUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Eksekusi transaksi on-chain untuk embedded-wallet user via PIN.
 * Alur: verifikasi PIN → dekripsi private key (sesaat di memori) → tanda tangani &
 * siarkan tx → kembalikan tx hash. Frontend lalu memanggil endpoint pencatatan yang
 * SUDAH ADA (yang memverifikasi on-chain), jadi verifikasi tetap sumber kebenaran.
 * Private key TIDAK PERNAH ke frontend.
 */
class PinTxController extends Controller
{
    private const ERC20_ABI = [
        ['inputs' => [['name' => 'to', 'type' => 'address'], ['name' => 'v', 'type' => 'uint256']], 'name' => 'transfer', 'outputs' => [['type' => 'bool']], 'type' => 'function'],
        ['inputs' => [['name' => 's', 'type' => 'address'], ['name' => 'v', 'type' => 'uint256']], 'name' => 'approve', 'outputs' => [['type' => 'bool']], 'type' => 'function'],
    ];
    private const DONATION_ABI = [
        ['inputs' => [['name' => 'campaignId', 'type' => 'bytes32'], ['name' => 'amount', 'type' => 'uint256']], 'name' => 'donate', 'outputs' => [], 'type' => 'function'],
    ];
    private const GATEWAY_ABI = [
        ['inputs' => [['name' => 'sellers', 'type' => 'address[]'], ['name' => 'amounts', 'type' => 'uint256[]'], ['name' => 'productIds', 'type' => 'string[]'], ['name' => 'orderId', 'type' => 'string']], 'name' => 'payCart', 'outputs' => [], 'type' => 'function'],
    ];
    private const PAYLATER_ABI = [
        ['inputs' => [], 'name' => 'depositCollateral', 'outputs' => [], 'stateMutability' => 'payable', 'type' => 'function'],
        ['inputs' => [['name' => 'amount', 'type' => 'uint256']], 'name' => 'borrow', 'outputs' => [], 'type' => 'function'],
        ['inputs' => [['name' => 'amount', 'type' => 'uint256']], 'name' => 'repay', 'outputs' => [], 'type' => 'function'],
        ['inputs' => [['name' => 'amount', 'type' => 'uint256']], 'name' => 'withdrawCollateral', 'outputs' => [], 'type' => 'function'],
        ['inputs' => [['name' => 'term', 'type' => 'uint8'], ['name' => 'amount', 'type' => 'uint256']], 'name' => 'supply', 'outputs' => [], 'type' => 'function'],
        ['inputs' => [['name' => 'term', 'type' => 'uint8'], ['name' => 'shareAmount', 'type' => 'uint256']], 'name' => 'withdrawSupply', 'outputs' => [], 'type' => 'function'],
    ];

    /** Verifikasi PIN + dekripsi private key. Return hex atau lempar (JSON 422). */
    private function unlock(string $pin): string
    {
        $user = auth()->user();
        abort_unless($user && $user->isEmbedded() && $user->pin_hash, 422, 'Akun ini bukan embedded wallet.');
        abort_if($user->pinLocked(), 423, 'PIN terkunci sementara. Coba lagi nanti.');

        if (!Hash::check($pin, $user->pin_hash)) {
            $user->increment('pin_attempts');
            if ($user->pin_attempts >= 5) {
                $user->forceFill(['pin_locked_until' => now()->addMinutes(15), 'pin_attempts' => 0])->save();
                abort(423, 'PIN salah 5×. Wallet dikunci 15 menit.');
            }
            abort(422, 'PIN salah. Sisa percobaan: ' . max(0, 5 - $user->pin_attempts) . '.');
        }

        $user->forceFill(['pin_attempts' => 0])->save();
        $wallet = new EmbeddedWallet();
        $priv = $wallet->decrypt($user, $pin);
        abort_unless($priv, 422, 'Gagal membuka wallet (PIN salah?).');

        // Gas drip saat daftar cuma sekali; isi ulang tBNB bila sudah menipis.
        app(GasTopUp::class)->ensure($wallet->addressFromPrivate($priv));
        return $priv;
    }

    /** Pastikan allowance cukup; kalau kurang, approve & tunggu ter-mine. */
    private function ensureAllowance(ChainSigner $s, string $priv, string $spender, string $amountWei): void
    {
        $token = config('chain.tlkm');
        $owner = (new EmbeddedWallet())->addressFromPrivate($priv);
        if (bccomp($s->allowance($token, $owner, $spender), $amountWei) >= 0) {
            return; // sudah cukup
        }
        $hash = $s->sendContractCall($priv, $token, self::ERC20_ABI, 'approve', [$spender, $amountWei]);
        $s->waitReceipt($hash); // tunggu allowance aktif sebelum panggilan berikutnya
    }

    /** Kirim TLKM P2P. Return tx hash (frontend lalu POST /wallet/send). */
    public function transfer(Request $req, ChainSigner $signer)
    {
        $data = $req->validate([
            'pin'    => 'required|digits:6',
            'to'     => ['required', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'amount' => 'required|numeric|min:0.000001',
        ]);
        $priv = $this->unlock($data['pin']);
        $hash = $signer->sendContractCall($priv, config('chain.tlkm'), self::ERC20_ABI, 'transfer', [$data['to'], $signer->toWei($data['amount'])]);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Donasi ke campaign (approve pool + donate). Return tx hash donate. */
    public function donate(Request $req, ChainSigner $signer)
    {
        $data = $req->validate([
            'pin'    => 'required|digits:6',
            'slug'   => 'required|string',
            'amount' => 'required|numeric|min:0.000001',
        ]);
        $campaign = Campaign::where('slug', $data['slug'])->firstOrFail();
        $pool = config('chain.donation_pool');
        $priv = $this->unlock($data['pin']);
        $wei  = $signer->toWei($data['amount']);

        $this->ensureAllowance($signer, $priv, $pool, $wei);
        $hash = $signer->sendContractCall($priv, $pool, self::DONATION_ABI, 'donate', [$campaign->chainId(), $wei]);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Bayar cart multi-penjual (approve gateway + payCart). Return tx hash payCart. */
    public function checkout(Request $req, ChainSigner $signer)
    {
        $data = $req->validate([
            'pin'         => 'required|digits:6',
            'order_id'    => 'required|string|max:80',
            'sellers'     => 'required|array|min:1',
            'sellers.*'   => ['regex:/^0x[a-fA-F0-9]{40}$/'],
            'amounts'     => 'required|array|min:1',
            'amounts.*'   => 'required|numeric|gt:0',
            'productIds'  => 'required|array',
            'productIds.*'=> 'required|string|max:80',
        ]);
        // Indeks dinormalisasi: array dengan kunci tak berurutan (amounts[5]) tak boleh
        // membuat baris-baris keranjang saling tertukar.
        foreach (['sellers', 'amounts', 'productIds'] as $k) {
            $data[$k] = array_values($data[$k]);
        }
        $n = count($data['sellers']);
        abort_unless($n === count($data['amounts']) && $n === count($data['productIds']), 422, 'Data keranjang tidak lengkap.');

        $amountsWei = array_map(fn ($a) => $signer->toWei($a), $data['amounts']);

        // Tolak SEBELUM menandatangani: tiap baris harus produk sungguhan milik penjual
        // itu, dengan nominal yang menutup harga x qty. /order/store juga memeriksa ini,
        // tapi tanpa cek di sini pembeli bisa kehilangan dana ke escrow untuk order yang
        // lalu ditolak (mis. harga berubah sejak halaman checkout dimuat).
        foreach ($data['productIds'] as $i => $pid) {
            $product = \App\Models\Product::where('product_id', $pid)->first();
            abort_unless($product && strtolower($product->seller_wallet) === strtolower($data['sellers'][$i]), 422,
                'Produk di keranjang tidak valid. Muat ulang halaman checkout.');
            $priceWei = bcmul((string) $product->getRawOriginal('price_usdc'), '1000000000000000000', 0);
            abort_if(\App\Services\ChainVerifier::paidQuantity($amountsWei[$i], $priceWei) === null, 422,
                'Harga "' . $product->name . '" berubah. Muat ulang halaman checkout.');
        }

        $gateway = config('chain.gateway');
        $priv = $this->unlock($data['pin']);

        $totalWei = array_reduce($amountsWei, fn ($c, $v) => bcadd($c, $v), '0');

        $this->ensureAllowance($signer, $priv, $gateway, $totalWei);
        // Encoder manual: encoder bawaan web3.php salah meng-encode string[] sehingga
        // calldata malformed dan payCart selalu revert tanpa pesan (lihat AbiEncoder).
        $payload = \App\Support\AbiEncoder::payCart(
            $data['sellers'], $amountsWei, $data['productIds'], $data['order_id']
        );
        $hash = $signer->sendRaw($priv, $gateway, '0x0', $payload);

        // Tx yang revert tidak boleh dilaporkan sebagai sukses.
        $r = $signer->waitReceipt($hash);
        abort_if(!$r, 422, 'Transaksi belum terkonfirmasi. Coba lagi sebentar. (tx: ' . $hash . ')');
        abort_if(!in_array(strtolower((string) ($r['status'] ?? '')), ['0x1', '1'], true), 422,
            'Pembayaran escrow gagal di blockchain (transaksi revert). Saldo tidak berkurang. (tx: ' . $hash . ')');

        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Paylater: deposit agunan tBNB (native value). Return tx hash. */
    public function paylaterDeposit(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['pin' => 'required|digits:6', 'amount' => 'required|numeric|min:0.000001']);
        $paylater = config('chain.paylater_address');
        abort_unless($paylater, 422, 'Paylater belum dikonfigurasi.');
        $priv = $this->unlock($data['pin']);
        $hash = $signer->sendContractCall($priv, $paylater, self::PAYLATER_ABI, 'depositCollateral', [], $signer->toWeiHex($data['amount']));
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Paylater: pinjam TLKM dalam batas limit. Return tx hash. */
    public function paylaterBorrow(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['pin' => 'required|digits:6', 'amount' => 'required|numeric|min:0.000001']);
        $paylater = config('chain.paylater_address');
        abort_unless($paylater, 422, 'Paylater belum dikonfigurasi.');
        $priv = $this->unlock($data['pin']);
        $hash = $signer->sendContractCall($priv, $paylater, self::PAYLATER_ABI, 'borrow', [$signer->toWei($data['amount'])]);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Paylater: lunasi utang TLKM (approve kontrak + repay). Return tx hash. */
    public function paylaterRepay(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['pin' => 'required|digits:6', 'amount' => 'required|numeric|min:0.000001']);
        $paylater = config('chain.paylater_address');
        abort_unless($paylater, 422, 'Paylater belum dikonfigurasi.');
        $priv = $this->unlock($data['pin']);
        $wei  = $signer->toWei($data['amount']);
        $this->ensureAllowance($signer, $priv, $paylater, $wei); // repay = pull TLKM
        $hash = $signer->sendContractCall($priv, $paylater, self::PAYLATER_ABI, 'repay', [$wei]);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Paylater: tarik agunan tBNB (hanya jika utang 0). Return tx hash. */
    public function paylaterWithdraw(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['pin' => 'required|digits:6', 'amount' => 'required|numeric|min:0.000001']);
        $paylater = config('chain.paylater_address');
        abort_unless($paylater, 422, 'Paylater belum dikonfigurasi.');
        $priv = $this->unlock($data['pin']);
        $hash = $signer->sendContractCall($priv, $paylater, self::PAYLATER_ABI, 'withdrawCollateral', [$signer->toWei($data['amount'])]);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Paylater (lender): danai pool TLKM pada jangka `term` (approve + supply). Return tx hash. */
    public function paylaterSupply(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['pin' => 'required|digits:6', 'term' => 'required|integer|between:0,2', 'amount' => 'required|numeric|min:0.000001']);
        $paylater = config('chain.paylater_address');
        abort_unless($paylater, 422, 'Paylater belum dikonfigurasi.');
        $priv = $this->unlock($data['pin']);
        $wei  = $signer->toWei($data['amount']);
        $this->ensureAllowance($signer, $priv, $paylater, $wei); // supply = pull TLKM
        $hash = $signer->sendContractCall($priv, $paylater, self::PAYLATER_ABI, 'supply', [(int) $data['term'], $wei]);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Paylater (lender): tarik dana penyuplai jangka `term`. `shares` = share EKSAK (integer wei-scale). */
    public function paylaterWithdrawSupply(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['pin' => 'required|digits:6', 'term' => 'required|integer|between:0,2', 'shares' => ['required', 'regex:/^[0-9]{1,78}$/']]);
        $paylater = config('chain.paylater_address');
        abort_unless($paylater, 422, 'Paylater belum dikonfigurasi.');
        $priv = $this->unlock($data['pin']);
        // shares sudah dalam satuan mentah (bukan human) -> jangan konversi toWei.
        $hash = $signer->sendContractCall($priv, $paylater, self::PAYLATER_ABI, 'withdrawSupply', [(int) $data['term'], $data['shares']]);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Aksi dompet komunitas via PIN (whitelist metode). */
    public function community(Request $req, ChainSigner $signer)
    {
        $data = $req->validate([
            'pin'     => 'required|digits:6',
            'address' => ['required', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'method'  => 'required|string',
            'args'    => 'nullable|array',
        ]);

        // Metode yang diizinkan + indeks argumen yang bernilai token (dikonversi ke wei).
        $defs = [
            'deposit'         => ['abi' => [['inputs' => [['name' => 'amount', 'type' => 'uint256']], 'name' => 'deposit', 'outputs' => [], 'type' => 'function']], 'wei' => [0], 'approve' => true],
            'withdraw'        => ['abi' => [['inputs' => [['name' => 'amount', 'type' => 'uint256']], 'name' => 'withdraw', 'outputs' => [], 'type' => 'function']], 'wei' => [0]],
            'setMember'       => ['abi' => [['inputs' => [['name' => 'm', 'type' => 'address'], ['name' => 'l', 'type' => 'uint256']], 'name' => 'setMember', 'outputs' => [], 'type' => 'function']], 'wei' => [1]],
            'removeMember'    => ['abi' => [['inputs' => [['name' => 'm', 'type' => 'address']], 'name' => 'removeMember', 'outputs' => [], 'type' => 'function']], 'wei' => []],
            'proposeTransfer' => ['abi' => [['inputs' => [['name' => 'to', 'type' => 'address'], ['name' => 'a', 'type' => 'uint256']], 'name' => 'proposeTransfer', 'outputs' => [['type' => 'uint256']], 'type' => 'function']], 'wei' => [1]],
            'approve'         => ['abi' => [['inputs' => [['name' => 'id', 'type' => 'uint256']], 'name' => 'approve', 'outputs' => [], 'type' => 'function']], 'wei' => []],
            'execute'         => ['abi' => [['inputs' => [['name' => 'id', 'type' => 'uint256']], 'name' => 'execute', 'outputs' => [], 'type' => 'function']], 'wei' => []],
        ];

        $method = $data['method'];
        abort_unless(isset($defs[$method]), 422, 'Metode komunitas tidak diizinkan.');
        $def  = $defs[$method];
        $args = array_values($data['args'] ?? []);
        foreach ($def['wei'] as $i) {
            if (isset($args[$i])) $args[$i] = $signer->toWei($args[$i]);
        }

        $priv = $this->unlock($data['pin']);
        if (!empty($def['approve']) && isset($args[0])) {
            $this->ensureAllowance($signer, $priv, $data['address'], $args[0]); // deposit butuh allowance
        }
        $hash = $signer->sendContractCall($priv, $data['address'], $def['abi'], $method, $args);
        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }
}

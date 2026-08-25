<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\ChainSigner;
use App\Services\EmbeddedWallet;
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
        $priv = (new EmbeddedWallet())->decrypt($user, $pin);
        abort_unless($priv, 422, 'Gagal membuka wallet (PIN salah?).');
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
            'productIds'  => 'required|array',
        ]);
        $gateway = config('chain.gateway');
        $priv = $this->unlock($data['pin']);

        $amountsWei = array_map(fn ($a) => $signer->toWei($a), $data['amounts']);
        $totalWei = array_reduce($amountsWei, fn ($c, $v) => bcadd($c, $v), '0');

        $this->ensureAllowance($signer, $priv, $gateway, $totalWei);
        $hash = $signer->sendContractCall($priv, $gateway, self::GATEWAY_ABI, 'payCart',
            [$data['sellers'], $amountsWei, $data['productIds'], $data['order_id']]);

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

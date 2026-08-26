<?php

namespace App\Services;

use App\Models\User;
use Elliptic\EC;
use kornrunner\Keccak;

/**
 * DIY embedded wallet untuk E-Trace (TESTNET/DEMO SAJA — BUKAN untuk uang asli).
 *
 * Model keamanan:
 * - Private key TIDAK PERNAH disimpan/dikirim plaintext.
 * - Disimpan sbg AES-256-GCM ciphertext. Kunci enkripsi = PBKDF2-SHA256 dari
 *   (PIN + WALLET_ENC_SECRET server + salt unik per-user), 100k iterasi.
 * - Salah PIN => auth-tag GCM gagal => dekripsi mengembalikan null (bukan hanya cek hash).
 * - PIN juga disimpan sebagai bcrypt hash (verifikasi cepat + lockout anti brute-force).
 *
 * CATATAN: menyimpan kunci ter-derive-PIN di server tetap punya risiko (server & secret
 * jadi target). Cukup untuk demo testnet; produksi uang nyata butuh HSM/KMS atau
 * non-custodial (mis. MPC/passkey). Opsi MetaMask tetap tersedia untuk yang mau.
 */
class EmbeddedWallet
{
    private function serverSecret(): string
    {
        // WALLET_ENC_SECRET wajib di produksi; fallback ke APP_KEY agar dev tetap jalan.
        return (string) (env('WALLET_ENC_SECRET') ?: config('app.key'));
    }

    /** Buat keypair secp256k1 baru. Return ['address'=>0x.., 'private'=>hex(64)]. */
    public function generate(): array
    {
        $ec  = new EC('secp256k1');
        $key = $ec->genKeyPair();
        $priv = str_pad($key->getPrivate('hex'), 64, '0', STR_PAD_LEFT);
        return ['address' => $this->addressFromPrivate($priv), 'private' => $priv];
    }

    /** Turunkan alamat Ethereum dari private key hex. */
    public function addressFromPrivate(string $privHex): string
    {
        $ec  = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privHex, 'hex');
        $pub = $key->getPublic(false, 'hex');            // 04 + X(64) + Y(64)
        $hash = Keccak::hash(hex2bin(substr($pub, 2)), 256);
        return '0x' . substr($hash, -40);
    }

    /** Enkripsi private key dg kunci turunan PIN. Return kolom untuk disimpan. */
    public function encrypt(string $privHex, string $pin): array
    {
        $salt = random_bytes(16);
        $iv   = random_bytes(12);
        $dkey = hash_pbkdf2('sha256', $pin . $this->serverSecret(), $salt, 100000, 32, true);
        $tag  = '';
        $cipher = openssl_encrypt($privHex, 'aes-256-gcm', $dkey, OPENSSL_RAW_DATA, $iv, $tag);

        return [
            'wallet_enc'  => base64_encode($cipher),
            'wallet_salt' => base64_encode($salt),
            'wallet_iv'   => base64_encode($iv),
            'wallet_tag'  => base64_encode($tag),
        ];
    }

    /**
     * Enkripsi private key dg kunci turunan SECRET SERVER saja (tanpa PIN).
     * Untuk dompet komunitas custodial (dikelola app; ditandatangani backend).
     */
    public function encryptServer(string $privHex): array
    {
        return $this->encryptWith($privHex, '');
    }

    /** Dekripsi private key yang dienkripsi server-only. Return hex atau null. */
    public function decryptServer(array $cols): ?string
    {
        $dkey = hash_pbkdf2('sha256', '' . $this->serverSecret(), base64_decode($cols['wallet_salt']), 100000, 32, true);
        $priv = openssl_decrypt(
            base64_decode($cols['wallet_enc']), 'aes-256-gcm', $dkey, OPENSSL_RAW_DATA,
            base64_decode($cols['wallet_iv']), base64_decode($cols['wallet_tag'])
        );
        return $priv === false ? null : $priv;
    }

    /** Inti enkripsi: kunci = PBKDF2(prefix + serverSecret + salt). */
    private function encryptWith(string $privHex, string $prefix): array
    {
        $salt = random_bytes(16);
        $iv   = random_bytes(12);
        $dkey = hash_pbkdf2('sha256', $prefix . $this->serverSecret(), $salt, 100000, 32, true);
        $tag  = '';
        $cipher = openssl_encrypt($privHex, 'aes-256-gcm', $dkey, OPENSSL_RAW_DATA, $iv, $tag);
        return [
            'wallet_enc'  => base64_encode($cipher),
            'wallet_salt' => base64_encode($salt),
            'wallet_iv'   => base64_encode($iv),
            'wallet_tag'  => base64_encode($tag),
        ];
    }

    /** Dekripsi private key user dg PIN. Return hex atau null (PIN salah/rusak). */
    public function decrypt(User $user, string $pin): ?string
    {
        if (!$user->wallet_enc || !$user->wallet_salt || !$user->wallet_iv || !$user->wallet_tag) {
            return null;
        }
        $dkey = hash_pbkdf2('sha256', $pin . $this->serverSecret(), base64_decode($user->wallet_salt), 100000, 32, true);
        $priv = openssl_decrypt(
            base64_decode($user->wallet_enc), 'aes-256-gcm', $dkey, OPENSSL_RAW_DATA,
            base64_decode($user->wallet_iv), base64_decode($user->wallet_tag)
        );
        return $priv === false ? null : $priv;
    }
}

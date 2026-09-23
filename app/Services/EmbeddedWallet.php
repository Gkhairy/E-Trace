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
    /**
     * Kunci turunan untuk ENKRIPSI baru.
     *
     * Dibaca dari config (bukan env()): setelah config:cache, env() di luar folder
     * config selalu NULL, sehingga dulu produksi diam-diam jatuh ke APP_KEY dan
     * web/worker memakai kunci yang berbeda.
     */
    private function serverSecret(): string
    {
        $secret = (string) config('wallet.enc_secret');
        if ($secret !== '') {
            return $secret;
        }
        // Di produksi lebih baik gagal keras daripada mengenkripsi dengan kunci yang
        // salah — wallet yang terlanjur terenkripsi begitu tak bisa dibuka lagi.
        if (app()->isProduction()) {
            throw new \RuntimeException('WALLET_ENC_SECRET belum di-set di environment produksi.');
        }
        return (string) config('app.key'); // dev lokal tanpa secret
    }

    /**
     * Kandidat kunci untuk DEKRIPSI: kunci utama, lalu APP_KEY sebagai kunci lama.
     *
     * APP_KEY ikut dicoba karena sebelum perbaikan config, wallet yang dibuat di
     * produksi terenkripsi dengan APP_KEY (fallback dari env() yang NULL). Aman
     * dicoba berurutan: AES-GCM memverifikasi tag, jadi kunci yang salah ditolak,
     * bukan menghasilkan private key palsu.
     */
    private function decryptionSecrets(): array
    {
        $primary = (string) config('wallet.enc_secret');
        $legacy  = (string) config('app.key');
        return array_values(array_unique(array_filter([$primary, $legacy], fn ($s) => $s !== '')));
    }

    /** Coba dekripsi dengan tiap kandidat kunci; kembalikan hex atau null. */
    private function decryptWithSecrets(string $prefix, string $enc, string $salt, string $iv, string $tag): ?string
    {
        foreach ($this->decryptionSecrets() as $secret) {
            $dkey = hash_pbkdf2('sha256', $prefix . $secret, base64_decode($salt), 100000, 32, true);
            $priv = openssl_decrypt(
                base64_decode($enc), 'aes-256-gcm', $dkey, OPENSSL_RAW_DATA,
                base64_decode($iv), base64_decode($tag)
            );
            if ($priv !== false) {
                return $priv;
            }
        }
        return null;
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
        return $this->decryptWithSecrets('', $cols['wallet_enc'], $cols['wallet_salt'], $cols['wallet_iv'], $cols['wallet_tag']);
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
        return $this->decryptWithSecrets($pin, $user->wallet_enc, $user->wallet_salt, $user->wallet_iv, $user->wallet_tag);
    }
}

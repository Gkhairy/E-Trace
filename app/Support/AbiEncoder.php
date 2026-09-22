<?php

namespace App\Support;

use kornrunner\Keccak;

/**
 * Encoder ABI manual untuk panggilan yang memuat array bertipe DINAMIS.
 *
 * Kenapa ada: encoder bawaan web3.php salah meng-encode `string[]` — word OFFSET
 * tiap elemen tidak ditulis, sehingga calldata menjadi malformed dan decoder
 * Solidity menolaknya dengan revert TANPA pesan. Akibatnya `payCart` selalu gagal
 * bila ditandatangani dari backend (PIN / dompet komunitas).
 *
 * Spesifikasi ABI untuk `string[]`:
 *   [jumlah elemen][offset_0]..[offset_n-1][len_0][data_0]..
 * dengan offset dihitung relatif terhadap awal blok offset (setelah word jumlah).
 */
class AbiEncoder
{
    /** Word 32-byte dari angka desimal (string/int). */
    private static function num(string|int $dec): string
    {
        return str_pad(gmp_strval(gmp_init((string) $dec, 10), 16), 64, '0', STR_PAD_LEFT);
    }

    /** Data bytes di-pad kanan ke kelipatan 32 byte. */
    private static function padRight(string $hex): string
    {
        $len = (int) (ceil(strlen($hex) / 64) * 64);
        return str_pad($hex, $len, '0', STR_PAD_RIGHT);
    }

    /** enc(string) = [panjang][data dipad]. */
    private static function str(string $s): string
    {
        return self::num(strlen($s)) . self::padRight(bin2hex($s));
    }

    /**
     * payCart(address[] sellers, uint256[] amounts, string[] productIds, string orderId)
     *
     * @param string[] $sellers     alamat 0x…
     * @param string[] $amountsWei  nominal dalam wei (string desimal)
     * @param string[] $productIds  id produk (string)
     */
    public static function payCart(array $sellers, array $amountsWei, array $productIds, string $orderId): string
    {
        $n = count($sellers);

        // address[]
        $a = self::num($n);
        foreach ($sellers as $s) {
            $a .= str_pad(strtolower(preg_replace('/^0x/i', '', $s)), 64, '0', STR_PAD_LEFT);
        }
        // uint256[]
        $b = self::num($n);
        foreach ($amountsWei as $v) {
            $b .= self::num($v);
        }
        // string[] — inilah bagian yang salah di encoder bawaan.
        $elements = [];
        foreach ($productIds as $pid) {
            $elements[] = self::str((string) $pid);
        }
        $offsets = '';
        $cursor  = 32 * $n;                 // offset relatif terhadap awal blok offset
        foreach ($elements as $e) {
            $offsets .= self::num($cursor);
            $cursor  += strlen($e) / 2;     // panjang elemen dalam byte
        }
        $c = self::num($n) . $offsets . implode('', $elements);
        // string
        $d = self::str($orderId);

        $offA = 128;                        // 4 word head
        $offB = $offA + strlen($a) / 2;
        $offC = $offB + strlen($b) / 2;
        $offD = $offC + strlen($c) / 2;

        $selector = substr(Keccak::hash('payCart(address[],uint256[],string[],string)', 256), 0, 8);

        return '0x' . $selector
            . self::num($offA) . self::num($offB) . self::num($offC) . self::num($offD)
            . $a . $b . $c . $d;
    }
}

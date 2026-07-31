<?php

namespace App\Support;

use App\Models\WalletLabel;
use App\Models\Store;
use App\Models\User;

/**
 * Resolusi identitas publik sebuah wallet untuk Explorer.
 * Prioritas: label terverifikasi (mis. "US GOV") > toko > pseudonim user > alamat pendek.
 * TIDAK PERNAH memaparkan nama asli/email/telepon/alamat (PII).
 */
class Identity
{
    public static function short(string $a): string
    {
        return strlen($a) > 12 ? substr($a, 0, 6) . '…' . substr($a, -4) : $a;
    }

    public static function resolve(string $address): array
    {
        $a = strtolower($address);

        if ($lab = WalletLabel::where('address', $a)->first()) {
            return ['name' => $lab->label, 'verified' => (bool) $lab->verified, 'category' => $lab->category, 'type' => 'entity'];
        }
        if ($store = Store::where('payout_wallet', $a)->first()) {
            return ['name' => $store->name, 'verified' => false, 'category' => 'toko', 'type' => 'store', 'store_id' => $store->id];
        }
        if ($user = User::where('wallet_address', $a)->first()) {
            $name = ($user->explorer_public && $user->public_name) ? $user->public_name : self::short($a);
            return ['name' => $name, 'verified' => false, 'category' => null, 'type' => 'buyer'];
        }

        return ['name' => self::short($a), 'verified' => false, 'category' => null, 'type' => 'wallet'];
    }
}

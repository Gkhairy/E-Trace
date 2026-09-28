<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\Store;
use App\Models\User;

/**
 * Helper ringkas untuk membuat notifikasi in-app.
 * Best-effort: kegagalan menyimpan notifikasi tidak boleh menggagalkan aksi utama.
 *
 * Judul & isi berupa template Indonesia (kunci terjemahan di lang/en.json). Isi boleh
 * berupa [template, params] — mis. ['Pesan dari :name', ['name' => 'Budi']] — supaya
 * diterjemahkan saat ditampilkan, mengikuti bahasa pembaca.
 */
class Notify
{
    public static function send(?int $userId, string $type, string $title, string|array|null $body = null, ?string $url = null, ?string $icon = null): void
    {
        if (!$userId) {
            return;
        }
        [$body, $params] = is_array($body) ? [$body[0], $body[1] ?? []] : [$body, null];
        try {
            AppNotification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'body'    => $body,
                'params'  => $params ?: null,
                'url'     => $url,
                'icon'    => $icon,
            ]);
        } catch (\Throwable $e) {
            // diamkan: notifikasi bukan jalur kritis
        }
    }

    /** Kirim ke pemilik wallet (mis. penjual dari seller_wallet). */
    public static function toWallet(string $wallet, string $type, string $title, string|array|null $body = null, ?string $url = null, ?string $icon = null): void
    {
        $addr = strtolower($wallet);
        // Prioritas: pemilik toko dengan payout wallet ini, lalu user dengan wallet ini.
        $userId = optional(Store::where('payout_wallet', $addr)->first())->user_id
            ?? optional(User::where('wallet_address', $addr)->first())->id;
        self::send($userId, $type, $title, $body, $url, $icon);
    }

    /** Kirim ke semua pengawas (supervisor). */
    public static function toSupervisors(string $type, string $title, string|array|null $body = null, ?string $url = null, ?string $icon = null): void
    {
        foreach (User::where('role', 'supervisor')->pluck('id') as $id) {
            self::send($id, $type, $title, $body, $url, $icon);
        }
    }
}

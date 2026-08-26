<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use App\Services\EmbeddedWallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder demo (J1): membuat akun dasar + toko penjual, supaya setelah reset DB
 * aplikasi punya data awal dan importer produk (products:import) punya toko tujuan.
 *
 * Idempotent (firstOrCreate). PIN default semua akun demo: 123456.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $ew = new EmbeddedWallet();

        $make = function (string $name, string $email, string $role) use ($ew): User {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                return $existing;
            }
            $w = $ew->generate();
            $enc = $ew->encrypt($w['private'], '123456');
            return User::create([
                'name'              => $name,
                'email'             => $email,
                'phone'             => '+628' . random_int(1000000000, 9999999999),
                'role'              => $role,
                'password'          => Hash::make('password'),
                'wallet_address'    => $w['address'],
                'nonce'             => Str::random(20),
                'email_verified_at' => now(),
                'is_embedded'       => true,
                'pin_hash'          => Hash::make('123456'),
            ] + $enc);
        };

        $supervisor = $make('Pengawas E-Trace', 'supervisor@etrace.test', 'supervisor');
        $seller     = $make('Penjual Demo', 'seller@etrace.test', 'seller');
        $buyer      = $make('Pembeli Demo', 'buyer@etrace.test', 'buyer');

        // Toko untuk penjual demo (payout = wallet penjual).
        Store::firstOrCreate(
            ['user_id' => $seller->id],
            [
                'name'          => 'Toko E-Trace Demo',
                'slug'          => 'toko-etrace-demo',
                'description'   => 'Toko demo untuk data awal & impor produk.',
                'origin_address'=> 'Jakarta',
                'payout_wallet' => $seller->wallet_address,
                'status'        => 'active',
            ]
        );

        $this->command->info('DemoSeeder: akun supervisor/seller/buyer (@etrace.test, pass "password", PIN 123456) + toko demo siap.');
        $this->command->info('Impor produk: php artisan products:import --limit=200');
    }
}

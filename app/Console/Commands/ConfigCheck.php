<?php

namespace App\Console\Commands;

use App\Services\OpsWallets;
use Illuminate\Console\Command;

/**
 * Periksa rahasia yang di-seal di Railway, dijalankan entrypoint setiap boot.
 *
 * Variabel yang di-seal tak bisa dilihat siapa pun, jadi nilai yang kosong atau salah
 * tempel baru ketahuan saat fiturnya gagal — ini pernah terjadi: variabel yang di-seal
 * lewat Raw Editor tersimpan tanpa nilai. Perintah ini menulis ke log (yang bisa dibaca)
 * apakah tiap rahasia terisi, dan untuk kunci privat, apakah ALAMAT PUBLIK turunannya
 * cocok dengan yang seharusnya.
 *
 * TIDAK PERNAH mencetak nilai rahasia. Alamat publik memang boleh dilihat siapa saja.
 * Tidak pernah menggagalkan boot: setiap pemeriksaan berdiri sendiri.
 */
class ConfigCheck extends Command
{
    protected $signature = 'config:check';

    protected $description = 'Laporkan apakah rahasia (sealed) terisi & cocok, tanpa menampilkan nilainya';

    private OpsWallets $ops;

    public function handle(OpsWallets $ops): int
    {
        $this->ops = $ops;
        $this->line('[config:check] mulai');

        foreach ([
            'APP_KEY'              => config('app.key'),
            'WALLET_ENC_SECRET'    => config('wallet.enc_secret'),
            'OPENAI_API_KEY'       => config('services.openai.key'),
            'CMC_API_KEY'          => config('services.cmc.key'),
            'RAJAONGKIR_API_KEY'   => config('chain.shipping.rajaongkir.key'),
            'TURNSTILE_SECRET_KEY' => config('services.turnstile.secret'),
            'RESEND_API_KEY'       => config('services.resend.key'),
        ] as $name => $value) {
            $this->report($name, filled($value) ? 'terisi' : 'KOSONG');
        }

        $this->report('MAIL_MAILER', (string) config('mail.default'));

        // Kunci arbiter harus cocok dengan arbiter yang tercatat di kontrak escrow.
        $this->checkKey('KEEPER_ARBITER_PRIVATE_KEY', config('chain.arbiter_key'), function (string $addr) {
            $onchain = $this->ops->arbiterOnChain();
            if ($onchain === null) {
                return 'arbiter kontrak tak terbaca (RPC)';
            }
            return $addr === $onchain ? 'cocok dengan arbiter kontrak' : 'TIDAK COCOK (kontrak: ' . OpsWallets::short($onchain) . ')';
        });

        // Kunci pool asuransi harus milik alamat INSURANCE_POOL_ADDRESS.
        $this->checkKey('INSURANCE_POOL_PRIVATE_KEY', config('chain.insurance.pool_key'), function (string $addr) {
            $expected = strtolower((string) config('chain.insurance.pool_wallet'));
            if ($expected === '') {
                return 'INSURANCE_POOL_ADDRESS kosong';
            }
            return $addr === $expected ? 'cocok dengan INSURANCE_POOL_ADDRESS' : 'TIDAK COCOK (seharusnya ' . OpsWallets::short($expected) . ')';
        });

        // Kunci gas harus punya saldo tBNB untuk dibagikan ke wallet baru.
        $this->checkKey('PLATFORM_GAS_PRIVATE_KEY', config('wallet.gas_private_key'), function (string $addr) {
            $bal = $this->ops->nativeBalance($addr);
            return $bal === null ? 'saldo tak terbaca (RPC)' : "saldo {$bal} tBNB";
        });

        $this->line('[config:check] selesai');
        return self::SUCCESS;
    }

    /** Turunkan alamat dari kunci privat lalu jalankan pencocokan; kunci tak pernah dicetak. */
    private function checkKey(string $name, $key, callable $verify): void
    {
        if ((string) $key === '') {
            $this->report($name, 'KOSONG');
            return;
        }
        $addr = $this->ops->addressOf($key);
        if ($addr === null) {
            $this->report($name, 'terisi tapi BUKAN kunci privat valid (harus 64 karakter hex)');
            return;
        }
        $this->report($name, 'alamat ' . OpsWallets::short($addr) . ' — ' . $verify($addr));
    }

    private function report(string $name, string $status): void
    {
        $this->line(sprintf('[config:check] %-28s %s', $name, $status));
    }
}

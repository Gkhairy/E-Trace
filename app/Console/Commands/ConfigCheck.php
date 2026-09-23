<?php

namespace App\Console\Commands;

use App\Services\EmbeddedWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use kornrunner\Keccak;
use Throwable;

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

    public function handle(): int
    {
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
            $onchain = $this->arbiterOnChain();
            if ($onchain === null) {
                return 'arbiter kontrak tak terbaca (RPC)';
            }
            return $addr === $onchain ? 'cocok dengan arbiter kontrak' : "TIDAK COCOK (kontrak: {$this->short($onchain)})";
        });

        // Kunci pool asuransi harus milik alamat INSURANCE_POOL_ADDRESS.
        $this->checkKey('INSURANCE_POOL_PRIVATE_KEY', config('chain.insurance.pool_key'), function (string $addr) {
            $expected = strtolower((string) config('chain.insurance.pool_wallet'));
            if ($expected === '') {
                return 'INSURANCE_POOL_ADDRESS kosong';
            }
            return $addr === $expected ? 'cocok dengan INSURANCE_POOL_ADDRESS' : "TIDAK COCOK (seharusnya {$this->short($expected)})";
        });

        // Kunci gas harus punya saldo tBNB untuk dibagikan ke wallet baru.
        $this->checkKey('PLATFORM_GAS_PRIVATE_KEY', config('wallet.gas_private_key'), function (string $addr) {
            $bal = $this->balance($addr);
            return $bal === null ? 'saldo tak terbaca (RPC)' : "saldo {$bal} tBNB";
        });

        $this->line('[config:check] selesai');
        return self::SUCCESS;
    }

    /** Turunkan alamat dari kunci privat lalu jalankan pencocokan; kunci tak pernah dicetak. */
    private function checkKey(string $name, $key, callable $verify): void
    {
        $key = (string) $key;
        if ($key === '') {
            $this->report($name, 'KOSONG');
            return;
        }
        try {
            $hex = str_starts_with($key, '0x') ? substr($key, 2) : $key;
            if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) {
                $this->report($name, 'terisi tapi BUKAN kunci privat valid (harus 64 karakter hex)');
                return;
            }
            $addr = strtolower((new EmbeddedWallet())->addressFromPrivate($hex));
            $this->report($name, "alamat {$this->short($addr)} — " . $verify($addr));
        } catch (Throwable $e) {
            $this->report($name, 'terisi, gagal diperiksa: ' . class_basename($e));
        }
    }

    private function arbiterOnChain(): ?string
    {
        $result = $this->rpc('eth_call', [[
            'to'   => config('chain.gateway'),
            'data' => '0x' . substr(Keccak::hash('arbiter()', 256), 0, 8),
        ], 'latest']);
        return is_string($result) && strlen($result) >= 42 ? '0x' . strtolower(substr($result, -40)) : null;
    }

    private function balance(string $addr): ?string
    {
        $result = $this->rpc('eth_getBalance', [$addr, 'latest']);
        if (!is_string($result)) {
            return null;
        }
        return rtrim(rtrim(bcdiv(gmp_strval(gmp_init($result, 16)), '1000000000000000000', 6), '0'), '.') ?: '0';
    }

    private function rpc(string $method, array $params)
    {
        try {
            return Http::timeout(8)->post((string) config('chain.rpc_url'), [
                'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
            ])->json('result');
        } catch (Throwable) {
            return null;
        }
    }

    private function short(string $addr): string
    {
        return substr($addr, 0, 6) . '…' . substr($addr, -4);
    }

    private function report(string $name, string $status): void
    {
        $this->line(sprintf('[config:check] %-28s %s', $name, $status));
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CommunityWallet;
use App\Services\ChainSigner;
use Illuminate\Console\Command;

/**
 * Isi GAS (ETH testnet Sepolia — GRATIS, bukan uang nyata) ke dompet komunitas
 * supaya bisa menarik/mengirim dana. Sumber dana: PLATFORM_GAS_PRIVATE_KEY (.env),
 * yaitu satu wallet pendana berisi ETH testnet (ambil gratis dari faucet Sepolia).
 *
 *   php artisan community:fund-gas                # isi semua yang saldo gas-nya rendah
 *   php artisan community:fund-gas --amount=0.02  # jumlah ETH testnet per dompet
 *   php artisan community:fund-gas --all          # isi semua dompet (walau sudah ada gas)
 */
class FundCommunityGas extends Command
{
    protected $signature = 'community:fund-gas {--amount=0.01} {--all}';
    protected $description = 'Isi gas (ETH testnet) ke dompet komunitas dari PLATFORM_GAS_PRIVATE_KEY.';

    public function handle(): int
    {
        $priv = (string) env('PLATFORM_GAS_PRIVATE_KEY', '');
        if ($priv === '') {
            $this->error('PLATFORM_GAS_PRIVATE_KEY belum diisi di .env.');
            $this->line('Buat 1 wallet, ambil ETH testnet gratis di https://sepoliafaucet.com,');
            $this->line('lalu isi PLATFORM_GAS_PRIVATE_KEY=<private key wallet itu> di .env.');
            return self::FAILURE;
        }
        if (str_starts_with($priv, '0x')) {
            $priv = substr($priv, 2);
        }

        $amount = (string) $this->option('amount');
        $signer = new ChainSigner();

        $funder = (new \App\Services\EmbeddedWallet())->addressFromPrivate($priv);
        $funderEth = $signer->ethBalance($funder);
        $this->info("Pendana: {$funder} — saldo " . ($funderEth ?? '?') . ' ETH testnet');
        if ($funderEth !== null && $funderEth < 0.001) {
            $this->error('Wallet pendana hampir tidak punya ETH testnet. Ambil dulu dari faucet Sepolia.');
            return self::FAILURE;
        }

        $wallets = CommunityWallet::all();
        $sent = 0; $skipped = 0;

        foreach ($wallets as $w) {
            $eth = $signer->ethBalance($w->address);
            if (!$this->option('all') && $eth !== null && $eth >= 0.003) {
                $this->line("• {$w->name}: sudah ada gas (" . $eth . ' ETH), dilewati.');
                $skipped++;
                continue;
            }
            try {
                $hash = $signer->sendRaw($priv, $w->address, $signer->toWeiHex($amount));
                $w->forceFill(['gas_dripped_at' => now()])->save();
                $this->info("• {$w->name} ({$w->address}): terkirim {$amount} ETH testnet — tx {$hash}");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("• {$w->name}: gagal — " . $e->getMessage());
            }
        }

        $this->info("Selesai. {$sent} dompet diisi gas, {$skipped} dilewati.");
        $this->line('Catatan: gas ini ETH TESTNET (gratis) — tidak ada biaya uang nyata.');
        return self::SUCCESS;
    }
}

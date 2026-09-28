<?php

namespace App\Console\Commands;

use App\Jobs\WelcomeTlkm;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Kirim (ulang) bonus TLKM uji coba: untuk satu akun lewat email, atau untuk semua akun
 * terverifikasi yang belum menerimanya (--missing), mis. setelah wallet platform diisi TLKM.
 */
class GrantWelcomeTlkm extends Command
{
    protected $signature = 'tlkm:grant-welcome {email? : Email akun penerima} {--missing : Semua akun terverifikasi yang belum menerima bonus}';

    protected $description = 'Kirim bonus TLKM uji coba (WELCOME_TLKM_AMOUNT) dari wallet platform.';

    public function handle(): int
    {
        $query = User::whereNotNull('email_verified_at')->whereNull('tlkm_granted_at')->whereNotNull('wallet_address');

        if ($email = $this->argument('email')) {
            $query->where('email', $email);
        } elseif (!$this->option('missing')) {
            $this->error('Sebutkan email akun, atau pakai --missing untuk semua akun yang belum menerima bonus.');
            return self::FAILURE;
        }

        $users = $query->get(['id', 'email']);
        if ($users->isEmpty()) {
            $this->info('Tidak ada akun yang perlu dikirimi bonus.');
            return self::SUCCESS;
        }

        foreach ($users as $user) {
            WelcomeTlkm::dispatchSync($user->id); // berurutan: satu wallet pengirim, nonce tidak bentrok
            $sent = User::whereKey($user->id)->whereNotNull('tlkm_granted_at')->exists();
            $this->line(($sent ? '✔ ' : '✘ ') . $user->email . ($sent ? '' : ' — gagal, cek log'));
        }
        return self::SUCCESS;
    }
}

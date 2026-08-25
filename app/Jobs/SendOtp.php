<?php

namespace App\Jobs;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim kode OTP ke email user via QUEUE (RabbitMQ) — tidak memblok request.
 * Kode plaintext hanya lewat di payload job (transient); DB menyimpan hash-nya.
 */
class SendOtp implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId, public string $code) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user || !$user->email) {
            return;
        }
        Mail::to($user->email)->send(new OtpMail($this->code, $user->name ?? ''));
    }
}

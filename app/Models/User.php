<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /** Daftar peran yang valid (sinkron dengan enum kolom `role`). */
    public const ROLES = ['buyer', 'seller', 'supervisor'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'public_name',
        'explorer_public',
        'email',
        'phone',
        'wallet_address',
        'role',
        'password',
        'nonce',
        'nonce_expires_at',
        'otp_hash',
        'otp_expires_at',
        'otp_attempts',
        'otp_sent_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /** 2FA TOTP aktif & sudah dikonfirmasi? */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    public function store()
    {
        return $this->hasOne(Store::class);
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'explorer_public' => 'boolean',
            'nonce_expires_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
        ];
    }
}

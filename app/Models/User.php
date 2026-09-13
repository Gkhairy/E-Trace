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
        'is_embedded',
        'wallet_enc',
        'wallet_salt',
        'wallet_iv',
        'wallet_tag',
        'pin_hash',
        'pin_attempts',
        'pin_locked_until',
        'gas_dripped_at',
    ];

    /**
     * Jaga blind index phone_hash tetap sinkron dengan phone (I1).
     * phone disimpan terenkripsi; phone_hash (HMAC) dipakai untuk pencarian.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('phone')) {
                $user->phone_hash = $user->phone ? self::hashPhone($user->phone) : null;
            }
        });
    }

    /** Normalisasi nomor telepon ke bentuk kanonik (angka saja, 0 depan -> 62). */
    public static function normalizePhone(string $raw): string
    {
        $d = preg_replace('/[^0-9]/', '', $raw) ?? '';
        if ($d !== '' && str_starts_with($d, '0')) {
            $d = '62' . ltrim($d, '0'); // asumsi Indonesia bila diawali 0
        }
        return $d;
    }

    /** Blind index HMAC untuk mencari user berdasarkan nomor telepon. */
    public static function hashPhone(string $raw): string
    {
        return hash_hmac('sha256', self::normalizePhone($raw), (string) config('app.key'));
    }

    /** 2FA TOTP aktif & sudah dikonfirmasi? */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    /** Wallet dibuat & dikelola platform (embedded) — bisa bayar pakai PIN. */
    public function isEmbedded(): bool
    {
        return (bool) $this->is_embedded;
    }

    /** PIN sedang terkunci (terlalu banyak salah)? */
    public function pinLocked(): bool
    {
        return $this->pin_locked_until !== null && now()->lessThan($this->pin_locked_until);
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
        'wallet_enc',
        'wallet_salt',
        'wallet_iv',
        'wallet_tag',
        'pin_hash',
        'otp_hash',
        'nonce',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'phone_hash',
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
            'phone' => \App\Casts\EncryptedOrPlain::class, // I1: terenkripsi (toleran data lama)
            'explorer_public' => 'boolean',
            'nonce_expires_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'is_embedded' => 'boolean',
            'pin_locked_until' => 'datetime',
            'gas_dripped_at' => 'datetime',
        ];
    }
}

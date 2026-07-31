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
        'is_admin',
        'role',
        'password',
        'nonce',
        'nonce_expires_at',
    ];

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
            'is_admin' => 'boolean',
            'explorer_public' => 'boolean',
            'nonce_expires_at' => 'datetime',
        ];
    }
}

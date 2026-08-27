<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Enkripsi at-rest yang TOLERAN terhadap data lama (plaintext).
 *
 * Kolom yang mulai dienkripsi setelah ada data lama tidak bisa memakai cast
 * 'encrypted' bawaan (langsung melempar DecryptException saat membaca nilai
 * plaintext lama). Cast ini:
 *  - get: coba dekripsi; kalau gagal (data lama plaintext), kembalikan apa adanya.
 *  - set: selalu enkripsi nilai baru.
 *
 * Sekali record disimpan ulang, nilainya otomatis jadi terenkripsi.
 */
class EncryptedOrPlain implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // data lama belum terenkripsi -> tampilkan apa adanya
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value === null ? null : Crypt::encryptString((string) $value);
    }
}

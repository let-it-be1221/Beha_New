<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

/**
 * HasConfidentialId — encrypt/decrypt the confidential_id column on access.
 *
 * Spec §6: Confidential IDs must be stored encrypted at rest and only
 * decrypted in services after an explicit policy check.
 *
 * Usage on User model:
 *   protected $casts = [];
 *   protected $encrypted = ['confidential_id'];
 */
trait HasConfidentialId
{
    /**
     * Mutator — encrypts the value before persisting.
     */
    public function setConfidentialIdAttribute(?string $value): void
    {
        $this->attributes['confidential_id'] = $value === null
            ? null
            : Crypt::encryptString($value);
    }

    /**
     * Accessor — decrypts the value when read.
     *
     * WARNING: this exposes the value to ANY caller — callers MUST first
     * check `auth()->user()->can('viewConfidential', $this)` before reading.
     */
    public function getConfidentialIdAttribute(?string $value): ?string
    {
        return $value === null ? null : Crypt::decryptString($value);
    }
}

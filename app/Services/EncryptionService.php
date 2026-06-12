<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    public function encrypt(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::encryptString((string) $value);
        } catch (\Exception $e) {
            Log::error('Encryption failed: ' . $e->getMessage());
            return null;
        }
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            Log::error('Decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    public function encryptArray(?array $value): ?string
    {
        if ($value === null || empty($value)) {
            return null;
        }

        try {
            return encrypt(json_encode($value));
        } catch (\Exception $e) {
            Log::error('Array encryption failed: ' . $e->getMessage());
            return null;
        }
    }

    public function decryptArray(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $decoded = json_decode(decrypt($value), true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Exception $e) {
            Log::error('Array decryption failed: ' . $e->getMessage());
            return null;
        }
    }
}

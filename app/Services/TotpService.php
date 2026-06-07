<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Str;

/**
 * TOTP (Time-based One-Time Password) Service
 *
 * Handles TOTP generation, verification, and QR code generation for MFA.
 * Implements RFC 6238 TOTP specification.
 *
 * Requirements:
 * - PHP ext-gd (for QR code generation)
 * - simplesoftwareio/simple-qrcode package
 */
class TotpService
{
    /**
     * TOTP window (number of time windows to check)
     * Allows for clock skew between server and client
     */
    protected const TOTP_WINDOW = 1;

    /**
     * TOTP time step in seconds (standard is 30)
     */
    protected const TOTP_TIME_STEP = 30;

    /**
     * TOTP code length (standard is 6)
     */
    protected const TOTP_CODE_LENGTH = 6;

    /**
     * Number of backup codes to generate
     */
    protected const BACKUP_CODES_COUNT = 10;

    /**
     * Length of each backup code
     */
    protected const BACKUP_CODE_LENGTH = 8;

    /**
     * Generate a new TOTP secret for a user
     *
     * @return string Base32-encoded TOTP secret
     */
    public function generateSecret(): string
    {
        // Generate 20 random bytes (160 bits) - RFC 4648 recommends at least 160 bits
        $randomBytes = random_bytes(20);

        // Encode to base32 (for QR codes and manual entry)
        return $this->base32Encode($randomBytes);
    }

    /**
     * Generate QR code for TOTP setup
     *
     * @param User $user
     * @param string $secret Base32-encoded TOTP secret
     * @param string|null $issuer Issuer name to display in authenticator app
     * @return string SVG QR code
     */
    public function generateQrCode(User $user, string $secret, ?string $issuer = null): string
    {
        $issuer = $issuer ?? config('app.name', 'Hospital Management');

        // OTPAuth URI format: otpauth://totp/Label?secret=SECRET&issuer=ISSUER
        $otpauthUri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            urlencode($issuer),
            urlencode($user->email),
            urlencode($secret),
            urlencode($issuer),
            self::TOTP_CODE_LENGTH,
            self::TOTP_TIME_STEP
        );

        try {
            // Use simple-qrcode package to generate QR
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->errorCorrection('H')
                ->generate($otpauthUri);

            return $qrCode;
        } catch (Exception $e) {
            throw new Exception("Failed to generate QR code: {$e->getMessage()}");
        }
    }

    /**
     * Verify a TOTP code
     *
     * @param string $secret Base32-encoded TOTP secret
     * @param string $code 6-digit TOTP code from authenticator app
     * @param int|null $timestamp Override timestamp for testing
     * @return bool
     */
    public function verifyCode(string $secret, string $code, ?int $timestamp = null): bool
    {
        // Validate code format (must be 6 digits)
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp = $timestamp ?? now()->timestamp;

        // Decode secret from base32
        $decodedSecret = $this->base32Decode($secret);

        if ($decodedSecret === false) {
            return false;
        }

        // Check current and adjacent time windows (for clock skew)
        for ($i = -self::TOTP_WINDOW; $i <= self::TOTP_WINDOW; $i++) {
            $timeCounter = intdiv($timestamp, self::TOTP_TIME_STEP) + $i;
            $generatedCode = $this->generateTotpCode($decodedSecret, $timeCounter);

            if (hash_equals($code, $generatedCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify a backup code and remove it from the list
     *
     * @param User $user
     * @param string $code Backup code to verify
     * @return bool
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        if (!$user->mfa_backup_codes) {
            return false;
        }

        $backupCodes = json_decode(decrypt($user->mfa_backup_codes), true);

        if (!is_array($backupCodes)) {
            return false;
        }

        // Find and remove the code (use hash_equals for constant time)
        foreach ($backupCodes as $index => $storedCode) {
            if (hash_equals($code, $storedCode)) {
                // Remove used code
                unset($backupCodes[$index]);
                $user->mfa_backup_codes = encrypt(json_encode(array_values($backupCodes)));
                $user->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Generate backup codes for account recovery
     *
     * @return array Array of backup codes
     */
    public function generateBackupCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < self::BACKUP_CODES_COUNT; $i++) {
            $codes[] = Str::upper(Str::random(self::BACKUP_CODE_LENGTH));
        }

        return $codes;
    }

    /**
     * Enable MFA for a user
     *
     * @param User $user
     * @param string $secret Base32-encoded TOTP secret
     * @param array $backupCodes Backup codes
     * @param string $verifyCode Initial TOTP code to verify setup
     * @return bool True if enabled successfully
     */
    public function enableMfa(User $user, string $secret, array $backupCodes, string $verifyCode): bool
    {
        // Verify the code before enabling
        if (!$this->verifyCode($secret, $verifyCode)) {
            return false;
        }

        // Save encrypted MFA settings
        $user->mfa_enabled = true;
        $user->mfa_secret = encrypt($secret);
        $user->mfa_backup_codes = encrypt(json_encode($backupCodes));
        $user->mfa_verified_at = now();
        $user->mfa_disabled_at = null;
        $user->save();

        return true;
    }

    /**
     * Disable MFA for a user
     *
     * @param User $user
     * @return bool
     */
    public function disableMfa(User $user): bool
    {
        $user->mfa_enabled = false;
        $user->mfa_secret = null;
        $user->mfa_backup_codes = null;
        $user->mfa_verified_at = null;
        $user->mfa_disabled_at = now();
        $user->mfa_last_code = null;
        $user->mfa_last_code_at = null;
        $user->save();

        return true;
    }

    /**
     * Check if MFA is setup and verified for user
     *
     * @param User $user
     * @return bool
     */
    public function isMfaSetup(User $user): bool
    {
        return $user->mfa_enabled
            && $user->mfa_secret
            && $user->mfa_verified_at;
    }

    /**
     * Get remaining backup codes count
     *
     * @param User $user
     * @return int
     */
    public function getRemainingBackupCodes(User $user): int
    {
        if (!$user->mfa_backup_codes) {
            return 0;
        }

        try {
            $codes = json_decode(decrypt($user->mfa_backup_codes), true);
            return is_array($codes) ? count($codes) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Generate TOTP code for a time counter
     *
     * @param string $secret Decoded (binary) TOTP secret
     * @param int $counter Time counter
     * @return string 6-digit TOTP code
     */
    protected function generateTotpCode(string $secret, int $counter): string
    {
        // Pack counter as big-endian 64-bit integer
        $counterBinary = pack('J', $counter);

        // Generate HMAC-SHA1
        $hmac = hash_hmac('sha1', $counterBinary, $secret, true);

        // Extract 4 bytes using dynamic truncation
        $offset = ord($hmac[-1]) & 0x0f;
        $fourBytes = substr($hmac, $offset, 4);

        // Convert 4 bytes to 31-bit integer
        $value = unpack('N', $fourBytes)[1] & 0x7fffffff;

        // Generate code
        $code = $value % pow(10, self::TOTP_CODE_LENGTH);

        // Pad with leading zeros
        return str_pad($code, self::TOTP_CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Encode binary data to base32
     *
     * @param string $data Binary data
     * @return string Base32-encoded string
     */
    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';

        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 0x1f];
            }
        }

        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 0x1f];
        }

        // Add padding
        while (strlen($output) % 8) {
            $output .= '=';
        }

        return $output;
    }

    /**
     * Decode base32 string to binary data
     *
     * @param string $data Base32-encoded string
     * @return string|false Decoded binary data or false on error
     */
    protected function base32Decode(string $data): string|false
    {
        $data = strtoupper($data);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

        // Remove padding
        $data = rtrim($data, '=');

        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $c = strpos($alphabet, $data[$i]);

            if ($c === false) {
                return false;
            }

            $v = ($v << 5) | $c;
            $vbits += 5;

            while ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 0xff);
            }
        }

        return $output;
    }
}

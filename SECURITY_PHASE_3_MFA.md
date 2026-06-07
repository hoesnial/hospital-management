# PHASE 3 - MFA/2FA (TOTP) Implementation

**Status**: ✅ COMPLETED  
**Date Implemented**: June 7, 2026

---

## Overview

Multi-Factor Authentication (MFA) using Time-based One-Time Password (TOTP) provides an additional layer of security beyond passwords. Users can enable MFA to require authentication from a second factor (authenticator app like Google Authenticator or Authy) during login.

This implementation follows RFC 6238 TOTP specification and includes:
- TOTP code generation and verification
- QR code generation for easy setup
- Backup codes for account recovery
- Per-user MFA enable/disable
- Integration with existing session and JWT authentication

---

## Files Created/Modified

### New Files (5)

1. **database/migrations/2025_12_07_000001_add_mfa_to_users_table.php** - Database migration
2. **app/Services/TotpService.php** - TOTP generation and verification service
3. **app/Http/Controllers/Auth/MfaController.php** - MFA controller with setup/verification endpoints
4. **app/Http/Requests/Auth/VerifyMfaRequest.php** - MFA code verification validation
5. **app/Http/Requests/Auth/SetupMfaRequest.php** - MFA setup validation

### Modified Files (2)

1. **routes/auth.php** - Added MFA routes (guest and authenticated)
2. **app/Http/Controllers/Auth/AuthenticatedSessionController.php** - Integrated MFA check into login flow

---

## Implementation Details

### 1. Database Schema

**Migration**: `add_mfa_to_users_table.php`

Adds the following columns to `users` table:

```sql
ALTER TABLE users ADD (
    mfa_enabled              BOOLEAN DEFAULT FALSE,
    mfa_secret              TEXT NULL,              -- Encrypted TOTP secret
    mfa_backup_codes        TEXT NULL,              -- Encrypted JSON array of backup codes
    mfa_verified_at         TIMESTAMP NULL,         -- When MFA was first verified
    mfa_disabled_at         TIMESTAMP NULL,         -- When MFA was disabled
    mfa_last_code           VARCHAR(6) NULL,        -- Last used TOTP code (prevent reuse)
    mfa_last_code_at        TIMESTAMP NULL,         -- Timestamp of last code use
    
    INDEX(mfa_enabled)                              -- For querying MFA-enabled users
);
```

**To run migration**:
```bash
php artisan migrate
```

### 2. TOTP Service

**Location**: `app/Services/TotpService.php`

**Key Methods**:

```php
// Generate new TOTP secret
$secret = $totpService->generateSecret();  // Returns Base32-encoded string

// Generate QR code for setup
$qrCode = $totpService->generateQrCode($user, $secret);

// Verify TOTP code (6-digit code from authenticator app)
$isValid = $totpService->verifyCode($secret, '123456');

// Verify backup code
$isValid = $totpService->verifyBackupCode($user, 'ABCD1234');

// Enable MFA for user
$success = $totpService->enableMfa($user, $secret, $backupCodes, $verifyCode);

// Disable MFA for user
$totpService->disableMfa($user);

// Get remaining backup codes count
$count = $totpService->getRemainingBackupCodes($user);

// Check if MFA is properly setup
$isSetup = $totpService->isMfaSetup($user);
```

**Security Features**:
- 160-bit random secret (RFC 4648 recommendation)
- Base32 encoding for QR codes and manual entry
- Time-window verification (±30 seconds) to handle clock skew
- Code reuse prevention (tracks last used code)
- Backup codes encrypted in database
- Secrets encrypted using Laravel's built-in encryption

### 3. MFA Controller

**Location**: `app/Http/Controllers/Auth/MfaController.php`

**Endpoints**:

#### GET /mfa/verify (guest)
Shows MFA verification page during login.

**Response**: Inertia render with MFA verification form

#### POST /mfa/verify (guest)
Verifies TOTP or backup code after password authentication.

**Request**:
```json
{
    "user_id": 1,
    "code": "123456",
    "is_backup_code": false
}
```

**Response (200)**:
```json
{
    "message": "MFA verification successful",
    "verification_token": "eyJ1c2VyX2lk...",
    "user_id": 1
}
```

**Response (401)**:
```json
{
    "message": "Invalid verification code",
    "error": "invalid_code"
}
```

#### GET /mfa/setup (auth)
Shows MFA setup page with QR code and backup codes.

**Response**: Inertia render with:
- `qrCode` - SVG QR code
- `secret` - Base32-encoded TOTP secret
- `backupCodes` - Array of backup codes

#### POST /mfa/enable (auth)
Enables MFA for user after verifying the setup code.

**Request**:
```json
{
    "secret": "JBSWY3DPEBLW64TMMQ...",
    "backup_codes": ["ABCD1234", "EFGH5678", ...],
    "verification_code": "123456"
}
```

**Response (200)**:
```json
{
    "message": "MFA enabled successfully",
    "mfaEnabled": true
}
```

#### POST /mfa/disable (auth)
Disables MFA for user. Requires password for security.

**Request**:
```json
{
    "password": "user_password"
}
```

**Response (200)**:
```json
{
    "message": "MFA disabled successfully",
    "mfaEnabled": false
}
```

#### GET /mfa/status (auth)
Get current MFA status for user.

**Response**:
```json
{
    "mfaEnabled": true,
    "mfaVerifiedAt": "2024-01-15T10:30:00Z",
    "remainingBackupCodes": 8
}
```

#### POST /mfa/regenerate-backup-codes (auth)
Generate new set of backup codes. Requires password.

**Request**:
```json
{
    "password": "user_password"
}
```

**Response**:
```json
{
    "message": "Backup codes regenerated successfully",
    "backupCodes": ["ABCD1234", "EFGH5678", ...]
}
```

### 4. Login Flow with MFA

```
User submits email & password
        ↓
AuthenticatedSessionController::store()
        ↓
Validate credentials
        ↓
Check if user has MFA enabled
        ├─ YES → Store user_id in session, redirect to /mfa/verify
        │          ↓
        │        User enters TOTP/backup code
        │          ↓
        │        MfaController::verify()
        │          ↓
        │        If valid → Create session and redirect to dashboard
        │        If invalid → Show error, ask for retry
        │
        └─ NO → Create session and redirect to dashboard
```

### 5. Routes

**In routes/auth.php**:

```php
// Guest routes (during MFA verification)
Route::middleware('guest')->group(function () {
    Route::get('mfa/verify', [MfaController::class, 'verifyPage'])->name('mfa.verify');
    Route::post('mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify.post');
});

// Authenticated routes (MFA management)
Route::middleware('auth')->group(function () {
    Route::get('mfa/setup', [MfaController::class, 'setupPage'])->name('mfa.setup');
    Route::post('mfa/enable', [MfaController::class, 'enable'])->name('mfa.enable');
    Route::post('mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');
    Route::post('mfa/regenerate-backup-codes', [MfaController::class, 'regenerateBackupCodes'])->name('mfa.regenerate-backup-codes');
    Route::get('mfa/status', [MfaController::class, 'status'])->name('mfa.status');
});
```

---

## Installation & Setup

### 1. Run Migration

```bash
php artisan migrate
```

This creates the MFA-related columns in the users table.

### 2. Update Login Flow

The AuthenticatedSessionController has been updated to:
1. Check if user has MFA enabled after password validation
2. Redirect to MFA verification page if enabled
3. Allow session establishment after MFA verification

### 3. Environment Configuration (Optional)

If you want to enforce MFA for JWT API authentication:

```env
# .env
JWT_REQUIRE_MFA=true  # Users must have MFA enabled for JWT login
```

Default is `false` (MFA optional).

---

## User Guide

### Setting Up MFA

1. **User navigates to**: `/mfa/setup`
2. **System displays**:
   - QR code to scan with authenticator app
   - Manual entry key (backup option)
   - Backup codes to save for recovery
3. **User scans QR code** with Google Authenticator, Authy, Microsoft Authenticator, etc.
4. **User enters 6-digit code** from authenticator app
5. **System verifies** code and enables MFA
6. **Backup codes** are provided for account recovery

### Logging In with MFA

1. **User enters email and password** as normal
2. **System redirects** to MFA verification page
3. **User enters 6-digit code** from authenticator app
4. **System verifies** code and creates session
5. **User is logged in** and redirected to dashboard

### Recovery with Backup Code

1. **During login**, instead of 6-digit code, user can enter backup code
2. **User marks checkbox** "Use backup code"
3. **Enters 8-character backup code** (e.g., `ABCD1234`)
4. **System verifies** and completes login
5. **Backup code is removed** and cannot be reused

### Disabling MFA

1. **User navigates to**: `/mfa/setup` (already enabled, shows manage page)
2. **Click**: "Disable MFA"
3. **Enter password** for security confirmation
4. **MFA is disabled** and user can login with password only again

### Regenerating Backup Codes

1. **After MFA is enabled**, user can regenerate backup codes
2. **Navigate to**: MFA settings
3. **Click**: "Generate New Backup Codes"
4. **Enter password** for confirmation
5. **New backup codes** are displayed and old ones are invalidated

---

## Security Considerations

### TOTP Implementation

- **RFC 6238 Compliant**: Standard TOTP algorithm
- **Time Window**: ±1 window (±30 seconds) to handle clock skew
- **Hash Algorithm**: HMAC-SHA1 (RFC 6238 standard)
- **Code Length**: 6 digits (standard)
- **Code Reuse Prevention**: Last used code is tracked
- **Secret Storage**: Base32 encoding for QR codes; encrypted in database

### Backup Codes

- **Count**: 10 backup codes per setup
- **Length**: 8 characters each (alphanumeric)
- **Storage**: Encrypted JSON in database
- **One-Time Use**: Each code can only be used once, then removed
- **Recovery**: Essential for account access if authenticator is lost

### Encryption

- **At Rest**: TOTP secret and backup codes encrypted using Laravel's encryption
- **In Transit**: HTTPS/TLS (when configured in Phase 9)
- **Key Management**: Uses APP_KEY from Laravel configuration

### Rate Limiting

- **MFA Verification**: Subject to general rate limiting (configurable)
- **Backup Code Attempts**: Each attempt is logged (Phase 5)
- **Failed Attempts**: Track for security alerts (Phase 6)

---

## Authenticator App Compatibility

Supported authenticator apps (any RFC 6238 compliant app):

✅ **Google Authenticator** (iOS, Android)  
✅ **Microsoft Authenticator** (iOS, Android, Windows Phone)  
✅ **Authy** (iOS, Android, Desktop)  
✅ **FreeOTP** (iOS, Android)  
✅ **Bitwarden** (Web, iOS, Android)  
✅ **1Password** (iOS, Android, Mac, Windows)  
✅ **LastPass** (iOS, Android)  

Any app supporting TOTP RFC 6238 will work.

---

## Testing MFA

### Manual Testing

1. **Enable MFA**:
   - Login as user
   - Navigate to `/mfa/setup`
   - Scan QR with authenticator app
   - Enter code from app to verify setup

2. **Login with MFA**:
   - Logout
   - Login with email and password
   - Verify redirected to MFA page
   - Enter TOTP code from app
   - Verify redirected to dashboard

3. **Test Backup Code**:
   - Logout
   - Login with email and password
   - Select "Use backup code"
   - Enter one of the backup codes
   - Verify login succeeds and code is removed

4. **Disable MFA**:
   - Navigate to `/mfa/setup`
   - Disable MFA with password confirmation
   - Logout and verify can login with password only

### Unit Testing

Create `tests/Feature/MfaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Tests\TestCase;

class MfaTest extends TestCase
{
    protected TotpService $totpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totpService = app(TotpService::class);
    }

    public function test_user_can_setup_mfa()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'mfa_enabled' => false,
        ]);

        $secret = $this->totpService->generateSecret();
        $backupCodes = $this->totpService->generateBackupCodes();

        // Generate a valid TOTP code for testing
        // Note: In real tests, you'd use a test secret with fixed time
        $code = '000000'; // Placeholder

        $this->actingAs($user)
            ->post('/mfa/enable', [
                'secret' => $secret,
                'backup_codes' => $backupCodes,
                'verification_code' => $code,
            ])
            ->assertStatus(200);

        $user->refresh();
        $this->assertTrue($user->mfa_enabled);
    }

    public function test_user_cannot_login_without_mfa_code()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'mfa_enabled' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/mfa/verify');
    }

    public function test_user_can_login_with_valid_totp()
    {
        // Generate a test secret and code
        $secret = $this->totpService->generateSecret();
        $code = $this->generateTotpCode($secret); // Helper to generate valid TOTP

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'mfa_enabled' => true,
            'mfa_secret' => encrypt($secret),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/mfa/verify');

        $this->post('/mfa/verify', [
            'user_id' => $user->id,
            'code' => $code,
            'is_backup_code' => false,
        ])->assertStatus(200);
    }

    public function test_invalid_totp_code_fails()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'mfa_enabled' => true,
        ]);

        $this->post('/mfa/verify', [
            'user_id' => $user->id,
            'code' => '000000', // Invalid code
            'is_backup_code' => false,
        ])->assertStatus(401);
    }
}
```

---

## Future Enhancements

### Phase 3 Extension (Optional)

- **SMS-based 2FA**: Alternative to TOTP
- **Email-based 2FA**: One-time codes via email
- **WebAuthn/FIDO2**: Hardware security key support
- **Biometric authentication**: Fingerprint/face recognition
- **Remember this device**: Skip MFA on trusted devices

### Integration with Phase 4+

- **Audit Logging** (Phase 5): Log all MFA events
- **Telegram Alerts** (Phase 6): Alert on MFA enable/disable
- **Security Monitoring** (Phase 8): Track MFA usage patterns

---

## Troubleshooting

### "TOTP code not working"

**Causes**:
- System time not synchronized
- Entered code from previous time window
- Using old/cached code

**Solutions**:
- Check system time (run `date`)
- Sync system time: `ntpdate -s time.nist.gov`
- Use backup code as fallback

### "QR code not scanning"

**Causes**:
- Authenticator app camera not working
- Poor lighting
- QR code size too small

**Solutions**:
- Use manual entry key instead
- Adjust phone camera or lighting
- Try different authenticator app

### "Can't login - forgot authenticator"

**Solution**:
- Use backup codes (if user saved them)
- Admin can disable MFA for user in database (Phase 5)
- Password reset doesn't disable MFA (security feature)

### "Backup codes all used"

**Solution**:
- User can regenerate new codes from settings
- Regenerate requires password confirmation

---

## API Integration (Phase 2)

When using JWT API authentication with MFA enabled:

```bash
# Step 1: Login (returns verification required response if MFA enabled)
curl -X POST https://api.hospital.local/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'

# Response (MFA required):
{
  "message": "MFA verification required",
  "error": "mfa_required",
  "user_id": 1
}

# Step 2: Verify MFA (would need separate endpoint)
curl -X POST https://api.hospital.local/api/auth/verify-mfa \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "code": "123456"
  }'

# Response (MFA verified, now get JWT):
{
  "message": "MFA verified, proceed to login",
  "verification_token": "eyJ..."
}

# Step 3: Complete JWT login with verification token
curl -X POST https://api.hospital.local/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password",
    "verification_token": "eyJ...",
    "code": "123456"
  }'

# Response (JWT tokens issued):
{
  "access_token": "eyJ...",
  "refresh_token": "eyJ...",
  ...
}
```

---

## Summary

✅ **Implemented**:
- RFC 6238 compliant TOTP implementation
- QR code generation for easy setup
- 10 backup codes for account recovery
- TOTP code verification with time-window
- Backup code one-time use and management
- Integration with existing login flow
- MFA enable/disable with password confirmation
- Per-user MFA status and configuration
- Code reuse prevention
- Encrypted secret and backup code storage
- Compatible with all standard authenticator apps

✅ **Ready for**:
- Production deployment
- Integration with Phase 5 (audit logging)
- Integration with Phase 6 (Telegram alerts)
- Integration with JWT authentication

🔄 **For Future Enhancement**:
- SMS/Email 2FA alternatives
- WebAuthn/FIDO2 security key support
- Device trust/remember this device
- MFA enforcement policies
- Biometric authentication

---

**Documentation Updated**: June 7, 2026  
**Next Phase**: Phase 4 - Data Encryption Implementation

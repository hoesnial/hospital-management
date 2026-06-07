<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetupMfaRequest;
use App\Http\Requests\Auth\VerifyMfaRequest;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MFA (Multi-Factor Authentication) Controller
 *
 * Handles MFA setup, verification, and management.
 * Supports TOTP (Time-based One-Time Password) and backup codes.
 */
class MfaController extends Controller
{
    protected TotpService $totpService;

    public function __construct(TotpService $totpService)
    {
        $this->totpService = $totpService;
        // Middleware is applied selectively per method in routes
    }

    /**
     * Show MFA verification page during login
     *
     * Displays form to enter TOTP code or backup code after password authentication.
     */
    public function verifyPage(Request $request): Response
    {
        $mfaPendingUserId = $request->session()->get('mfa_pending_user_id');

        if (!$mfaPendingUserId) {
            return Inertia::render('Auth/Login');
        }

        return Inertia::render('Auth/MfaVerify', [
            'userId' => $mfaPendingUserId,
        ]);
    }

    /**
     * Show MFA setup page (generate QR code and secret)
     *
     * Displays setup form with QR code for scanning with authenticator app.
     * Requires authenticated user.
     */
    public function setupPage(): Response
    {
        $user = Auth::user();

        if (!$user) {
            return Inertia::render('Auth/Login');
        }

        if ($user->mfa_enabled) {
            return Inertia::render('Auth/MfaManage', [
                'mfaEnabled' => true,
                'remainingBackupCodes' => $this->totpService->getRemainingBackupCodes($user),
            ]);
        }

        // Generate new secret and QR code
        $secret = $this->totpService->generateSecret();
        $qrCode = $this->totpService->generateQrCode($user, $secret);
        $backupCodes = $this->totpService->generateBackupCodes();

        return Inertia::render('Auth/MfaSetup', [
            'qrCode' => $qrCode,
            'secret' => $secret,
            'backupCodes' => $backupCodes,
        ]);
    }

    /**
     * Enable MFA for user
     *
     * Verifies the setup code and enables MFA.
     */
    public function enable(SetupMfaRequest $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->mfa_enabled) {
            return response()->json([
                'message' => 'MFA is already enabled',
                'error' => 'mfa_already_enabled',
            ], 422);
        }

        $secret = $request->input('secret');
        $backupCodes = $request->input('backup_codes', []);
        $verifyCode = $request->input('verification_code');

        if (empty($secret) || empty($backupCodes) || empty($verifyCode)) {
            return response()->json([
                'message' => 'Missing required setup parameters',
                'error' => 'missing_parameters',
            ], 422);
        }

        // Enable MFA
        if (!$this->totpService->enableMfa($user, $secret, $backupCodes, $verifyCode)) {
            return response()->json([
                'message' => 'Invalid verification code',
                'error' => 'invalid_verification_code',
            ], 422);
        }

        // Log MFA enablement
        // This will be enhanced in Phase 5 - Security Logging

        return response()->json([
            'message' => 'MFA enabled successfully',
            'mfaEnabled' => true,
        ], 200);
    }

    /**
     * Disable MFA for user
     *
     * Removes MFA from account. Requires current password for security.
     */
    public function disable(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->mfa_enabled) {
            return response()->json([
                'message' => 'MFA is not enabled',
                'error' => 'mfa_not_enabled',
            ], 422);
        }

        // Verify password
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // Disable MFA
        $this->totpService->disableMfa($user);

        // Log MFA disablement
        // This will be enhanced in Phase 5 - Security Logging

        return response()->json([
            'message' => 'MFA disabled successfully',
            'mfaEnabled' => false,
        ], 200);
    }

    /**
     * Verify MFA code during login
     *
     * Used after email/password login but before session establishment.
     * Called from AuthenticatedSessionController when MFA is required.
     */
    public function verify(VerifyMfaRequest $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $code = $request->input('code');
        $isBackupCode = $request->input('is_backup_code', false);

        // Find user
        $user = \App\Models\User::findOrFail($userId);

        // Check if MFA is enabled
        if (!$this->totpService->isMfaSetup($user)) {
            return response()->json([
                'message' => 'MFA is not enabled for this account',
                'error' => 'mfa_not_enabled',
            ], 422);
        }

        $isValid = false;

        if ($isBackupCode) {
            // Verify backup code
            $isValid = $this->totpService->verifyBackupCode($user, $code);
        } else {
            // Verify TOTP code
            $decodedSecret = decrypt($user->mfa_secret);
            $isValid = $this->totpService->verifyCode($decodedSecret, $code);

            // Prevent code reuse within the same time window
            if ($isValid && $user->mfa_last_code === $code) {
                $lastCodeTime = $user->mfa_last_code_at?->timestamp ?? 0;
                $now = now()->timestamp;

                if ($now - $lastCodeTime < 30) {
                    $isValid = false;
                }
            }

            // Update last used code
            if ($isValid) {
                $user->mfa_last_code = $code;
                $user->mfa_last_code_at = now();
                $user->save();
            }
        }

        if (!$isValid) {
            return response()->json([
                'message' => 'Invalid verification code',
                'error' => 'invalid_code',
            ], 401);
        }

        // Log successful MFA verification
        // This will be enhanced in Phase 5 - Security Logging

        // Return verification token to be used for session establishment
        $verificationToken = base64_encode(json_encode([
            'user_id' => $user->id,
            'verified_at' => now()->timestamp,
            'is_backup_code' => $isBackupCode,
        ]));

        return response()->json([
            'message' => 'MFA verification successful',
            'verification_token' => $verificationToken,
            'user_id' => $user->id,
        ], 200);
    }

    /**
     * Get MFA status for current user
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'mfaEnabled' => $user->mfa_enabled,
            'mfaVerifiedAt' => $user->mfa_verified_at,
            'remainingBackupCodes' => $this->totpService->getRemainingBackupCodes($user),
        ], 200);
    }

    /**
     * Regenerate backup codes
     *
     * Creates a new set of backup codes after MFA is setup.
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->mfa_enabled) {
            return response()->json([
                'message' => 'MFA is not enabled',
                'error' => 'mfa_not_enabled',
            ], 422);
        }

        // Verify password
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // Generate new backup codes
        $newBackupCodes = $this->totpService->generateBackupCodes();

        // Save new backup codes
        if ($user instanceof User) {
            $user->mfa_backup_codes = encrypt(json_encode($newBackupCodes));
            $user->save();
        }

        // Log backup code regeneration
        // This will be enhanced in Phase 5 - Security Logging

        return response()->json([
            'message' => 'Backup codes regenerated successfully',
            'backupCodes' => $newBackupCodes,
        ], 200);
    }
}

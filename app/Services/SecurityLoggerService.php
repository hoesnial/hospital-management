<?php

namespace App\Services;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Security Logger Service
 *
 * Logs all security-related events for audit trail and monitoring.
 * Tracks: login attempts, role changes, unauthorized access, file uploads, etc.
 */
class SecurityLoggerService
{
    /**
     * Log successful login
     */
    public static function logLoginSuccess(User $user): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'login_success',
            event_type: 'authentication',
            severity: 'info',
            description: "User {$user->email} logged in successfully",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'mfa_enabled' => $user->mfa_enabled,
            ]
        );
    }

    /**
     * Log failed login attempt
     */
    public static function logLoginFailed(?string $email, string $reason = 'Invalid credentials'): SecurityLog
    {
        // Check for brute force - more than 5 attempts in 15 minutes
        $recentAttempts = SecurityLog::where('email', $email)
            ->where('action', 'login_failed')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        $severity = $recentAttempts >= 5 ? 'critical' : 'warning';

        return self::createLog(
            user: null,
            action: 'login_failed',
            event_type: 'authentication',
            severity: $severity,
            description: "Failed login attempt for {$email}: {$reason}",
            details: [
                'email' => $email,
                'reason' => $reason,
                'attempt_count' => $recentAttempts + 1,
            ],
            email: $email,
            failureReason: $reason,
            attemptCount: $recentAttempts + 1
        );
    }

    /**
     * Log logout
     */
    public static function logLogout(User $user, string $reason = 'User initiated logout'): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'logout',
            event_type: 'authentication',
            severity: 'info',
            description: "User {$user->email} logged out: {$reason}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log MFA enabled
     */
    public static function logMfaEnabled(User $user): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'mfa_enabled',
            event_type: 'mfa',
            severity: 'info',
            description: "User {$user->email} enabled MFA",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
            ]
        );
    }

    /**
     * Log MFA disabled
     */
    public static function logMfaDisabled(User $user): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'mfa_disabled',
            event_type: 'mfa',
            severity: 'warning',
            description: "User {$user->email} disabled MFA",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
            ]
        );
    }

    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess(User $user, string $resourceType, string $resourceId, string $reason = 'Unauthorized access'): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'unauthorized_access',
            event_type: 'unauthorized_access',
            severity: 'critical',
            description: "User {$user->email} attempted unauthorized access to {$resourceType}#{$resourceId}: {$reason}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'reason' => $reason,
            ],
            resourceType: $resourceType,
            resourceId: $resourceId,
            failureReason: $reason
        );
    }

    /**
     * Log file upload
     */
    public static function logFileUpload(User $user, string $fileName, string $mimeType, int $fileSize): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'file_upload',
            event_type: 'file_operation',
            severity: 'info',
            description: "User {$user->email} uploaded file: {$fileName}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]
        );
    }

    /**
     * Log suspicious file upload
     */
    public static function logSuspiciousUpload(User $user, string $fileName, string $reason): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'suspicious_upload',
            event_type: 'security_threat',
            severity: 'critical',
            description: "User {$user->email} attempted suspicious upload: {$fileName} - {$reason}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'file_name' => $fileName,
                'reason' => $reason,
            ],
            failureReason: $reason
        );
    }

    /**
     * Log SQL injection attempt
     */
    public static function logSqlInjectionAttempt(User $user, string $input, string $parameter): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'sql_injection_attempt',
            event_type: 'security_threat',
            severity: 'critical',
            description: "Possible SQL injection attempt from user {$user->email} on parameter {$parameter}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'parameter' => $parameter,
                'input_sample' => substr($input, 0, 100),
            ],
            failureReason: 'SQL injection pattern detected'
        );
    }

    /**
     * Log XSS attempt
     */
    public static function logXssAttempt(User $user, string $input, string $field): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'xss_attempt',
            event_type: 'security_threat',
            severity: 'critical',
            description: "Possible XSS attempt from user {$user->email} in field {$field}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'field' => $field,
                'input_sample' => substr($input, 0, 100),
            ],
            failureReason: 'XSS pattern detected'
        );
    }

    /**
     * Log role change
     */
    public static function logRoleChange(User $user, string $oldRole, string $newRole, ?User $changedBy = null): SecurityLog
    {
        $changedByEmail = $changedBy ? $changedBy->email : 'System';

        return self::createLog(
            user: $user,
            action: 'role_changed',
            event_type: 'authorization',
            severity: 'warning',
            description: "Role changed for user {$user->email} from {$oldRole} to {$newRole} by {$changedByEmail}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_role' => $oldRole,
                'new_role' => $newRole,
                'changed_by' => $changedByEmail,
                'changed_by_id' => $changedBy?->id,
            ]
        );
    }

    /**
     * Log account lockout
     */
    public static function logAccountLockout(User $user, string $reason = 'Multiple failed login attempts'): SecurityLog
    {
        return self::createLog(
            user: $user,
            action: 'account_lockout',
            event_type: 'account_security',
            severity: 'critical',
            description: "Account locked for user {$user->email}: {$reason}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log password change
     */
    public static function logPasswordChange(User $user, ?User $changedBy = null): SecurityLog
    {
        $changedByEmail = $changedBy ? $changedBy->email : $user->email;
        $action = $changedBy ? 'password_reset_by_admin' : 'password_changed';

        return self::createLog(
            user: $user,
            action: $action,
            event_type: 'account_security',
            severity: 'warning',
            description: "Password changed for user {$user->email} by {$changedByEmail}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'changed_by' => $changedByEmail,
                'changed_by_id' => $changedBy?->id,
            ]
        );
    }

    /**
     * Log record created/modified/deleted
     */
    public static function logDataOperation(User $user, string $operation, string $model, string $recordId, ?array $changes = null): SecurityLog
    {
        $actionMap = [
            'create' => 'record_created',
            'update' => 'record_updated',
            'delete' => 'record_deleted',
        ];

        return self::createLog(
            user: $user,
            action: $actionMap[$operation] ?? 'record_operated',
            event_type: 'data_operation',
            severity: $operation === 'delete' ? 'warning' : 'info',
            description: "User {$user->email} {$operation}d {$model} record #{$recordId}",
            details: [
                'user_id' => $user->id,
                'email' => $user->email,
                'operation' => $operation,
                'model' => $model,
                'record_id' => $recordId,
                'changes' => $changes,
            ],
            resourceType: $model,
            resourceId: $recordId
        );
    }

    /**
     * Internal helper to create security log
     */
    private static function createLog(
        ?User $user,
        string $action,
        string $event_type,
        string $severity,
        string $description,
        array $details = [],
        ?string $email = null,
        string $failureReason = '',
        int $attemptCount = 1,
        string $resourceType = '',
        string $resourceId = ''
    ): SecurityLog {
        $userRole = null;
        if ($user) {
            $userRole = $user->role ?? 'user';
            $email = $user->email;
        }

        return SecurityLog::create([
            'user_id' => $user?->id,
            'email' => $email,
            'user_agent' => Request::header('User-Agent'),
            'ip_address' => Request::ip(),
            'user_role' => $userRole,
            'action' => $action,
            'event_type' => $event_type,
            'severity' => $severity,
            'description' => $description,
            'details' => $details,
            'attempt_count' => $attemptCount,
            'failure_reason' => $failureReason ?: null,
            'resource_type' => $resourceType ?: null,
            'resource_id' => $resourceId ?: null,
            'alert_sent' => false,
            'device_id' => Request::header('Device-ID'),
        ]);
    }
}

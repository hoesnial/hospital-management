<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Login Success Event
 */
class LoginSuccess
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user)
    {
    }
}

/**
 * Login Failed Event
 */
class LoginFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ?string $email,
        public string $reason = 'Invalid credentials'
    ) {
    }
}

/**
 * Unauthorized Access Event
 */
class UnauthorizedAccess
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resourceType,
        public string $resourceId,
        public string $reason = 'Unauthorized access'
    ) {
    }
}

/**
 * Suspicious Upload Event
 */
class SuspiciousUpload
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $fileName,
        public string $reason
    ) {
    }
}

/**
 * SQL Injection Attempt Event
 */
class SqlInjectionAttempt
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $parameter,
        public string $input
    ) {
    }
}

/**
 * XSS Attempt Event
 */
class XssAttempt
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $field,
        public string $input
    ) {
    }
}

/**
 * Account Lockout Event
 */
class AccountLockout
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $reason = 'Multiple failed login attempts'
    ) {
    }
}

/**
 * MFA Disabled Event
 */
class MfaDisabled
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user)
    {
    }
}

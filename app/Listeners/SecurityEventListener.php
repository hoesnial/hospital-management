<?php

namespace App\Listeners;

use App\Events\AccountLockout;
use App\Events\LoginFailed;
use App\Events\LoginSuccess;
use App\Events\MfaDisabled;
use App\Events\SqlInjectionAttempt;
use App\Events\SuspiciousUpload;
use App\Events\UnauthorizedAccess;
use App\Events\XssAttempt;
use App\Services\SecurityLoggerService;
use App\Services\TelegramAlertService;

/**
 * Security Event Listeners
 *
 * Listen to security events and perform logging and alerting.
 */
class SecurityEventListener
{
    protected SecurityLoggerService $logger;
    protected TelegramAlertService $telegram;

    public function __construct()
    {
        $this->logger = new SecurityLoggerService();
        $this->telegram = new TelegramAlertService();
    }

    /**
     * Handle login success
     */
    public function handleLoginSuccess(LoginSuccess $event)
    {
        $log = SecurityLoggerService::logLoginSuccess($event->user);
        // Could add additional alerts here if needed
    }

    /**
     * Handle login failed
     */
    public function handleLoginFailed(LoginFailed $event)
    {
        $log = SecurityLoggerService::logLoginFailed($event->email, $event->reason);

        // Send alert if critical (5+ attempts)
        if ($log->severity === 'critical') {
            $this->telegram->sendFailedLoginAlert($event->email, $log->attempt_count);
        }
    }

    /**
     * Handle unauthorized access
     */
    public function handleUnauthorizedAccess(UnauthorizedAccess $event)
    {
        $log = SecurityLoggerService::logUnauthorizedAccess(
            $event->user,
            $event->resourceType,
            $event->resourceId,
            $event->reason
        );

        // Always send critical alert for unauthorized access
        $this->telegram->sendUnauthorizedAccessAlert(
            $event->user->email,
            $event->resourceType,
            $event->resourceId
        );
    }

    /**
     * Handle suspicious upload
     */
    public function handleSuspiciousUpload(SuspiciousUpload $event)
    {
        $log = SecurityLoggerService::logSuspiciousUpload(
            $event->user,
            $event->fileName,
            $event->reason
        );

        $this->telegram->sendSuspiciousUploadAlert(
            $event->user->email,
            $event->fileName,
            $event->reason
        );
    }

    /**
     * Handle SQL injection attempt
     */
    public function handleSqlInjectionAttempt(SqlInjectionAttempt $event)
    {
        $log = SecurityLoggerService::logSqlInjectionAttempt(
            $event->user,
            $event->input,
            $event->parameter
        );

        $this->telegram->sendSqlInjectionAlert(
            $event->user->email,
            $event->parameter
        );
    }

    /**
     * Handle XSS attempt
     */
    public function handleXssAttempt(XssAttempt $event)
    {
        $log = SecurityLoggerService::logXssAttempt(
            $event->user,
            $event->input,
            $event->field
        );

        $this->telegram->sendXssAlert($event->user->email, $event->field);
    }

    /**
     * Handle account lockout
     */
    public function handleAccountLockout(AccountLockout $event)
    {
        $log = SecurityLoggerService::logAccountLockout($event->user, $event->reason);

        $this->telegram->sendAccountLockoutAlert($event->user->email, $event->reason);
    }

    /**
     * Handle MFA disabled
     */
    public function handleMfaDisabled(MfaDisabled $event)
    {
        $log = SecurityLoggerService::logMfaDisabled($event->user);

        $this->telegram->sendMfaDisabledAlert($event->user->email);
    }
}

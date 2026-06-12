<?php

namespace App\Services;

use App\Models\SecurityLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram Alert Service
 *
 * Sends real-time security alerts to Telegram for critical events.
 * Integrates with SecurityLogger for event-based alerting.
 *
 * Configuration required:
 * - TELEGRAM_BOT_TOKEN: Telegram bot token
 * - TELEGRAM_CHAT_ID: Telegram chat/group ID for alerts
 */
class TelegramAlertService
{
    protected ?string $botToken = null;
    protected ?string $chatId = null;
    protected string $apiUrl = 'https://api.telegram.org/bot';

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    /**
     * Send alert for security event
     */
    public function sendAlert(SecurityLog $log): bool
    {
        // Only send alerts for critical events
        if ($log->severity !== 'critical') {
            return false;
        }

        // Check if already sent
        if ($log->alert_sent) {
            return false;
        }

        try {
            $message = $this->formatAlertMessage($log);
            $success = $this->sendMessage($message);

            if ($success) {
                $log->markAlertSent('telegram');
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Telegram alert failed: ' . $e->getMessage(), [
                'security_log_id' => $log->id,
                'event' => $log->action,
            ]);

            return false;
        }
    }

    /**
     * Send custom alert message
     */
    public function sendCustomAlert(string $title, string $message, ?string $severity = 'warning'): bool
    {
        try {
            $emoji = match ($severity) {
                'critical' => '🚨',
                'warning' => '⚠️',
                'info' => 'ℹ️',
                default => '📢',
            };

            $text = "{$emoji} *{$title}*\n\n{$message}";

            return $this->sendMessage($text);
        } catch (\Exception $e) {
            Log::error('Telegram custom alert failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Send failed login alert (batch for multiple attempts)
     */
    public function sendFailedLoginAlert(string $email, int $attemptCount): bool
    {
        $message = "🚨 *BRUTE FORCE ATTACK DETECTED*\n\n";
        $message .= "Email: `{$email}`\n";
        $message .= "Failed Attempts: {$attemptCount}\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $message .= "⚠️ Account may need to be locked if attempts continue";

        return $this->sendMessage($message);
    }

    /**
     * Send unauthorized access alert
     */
    public function sendUnauthorizedAccessAlert(string $userEmail, string $resourceType, string $resourceId): bool
    {
        $message = "🚨 *UNAUTHORIZED ACCESS ATTEMPT*\n\n";
        $message .= "User: `{$userEmail}`\n";
        $message .= "Resource: {$resourceType}#{$resourceId}\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
        $message .= "IP: " . request()->ip() . "\n\n";
        $message .= "🔒 Access denied - logging for investigation";

        return $this->sendMessage($message);
    }

    /**
     * Send SQL injection attempt alert
     */
    public function sendSqlInjectionAlert(string $userEmail, string $parameter): bool
    {
        $message = "🚨 *SQL INJECTION ATTEMPT DETECTED*\n\n";
        $message .= "User: `{$userEmail}`\n";
        $message .= "Parameter: `{$parameter}`\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
        $message .= "IP: " . request()->ip() . "\n\n";
        $message .= "🛡️ Request blocked - investigation required";

        return $this->sendMessage($message);
    }

    /**
     * Send XSS attempt alert
     */
    public function sendXssAlert(string $userEmail, string $field): bool
    {
        $message = "🚨 *XSS ATTACK ATTEMPT DETECTED*\n\n";
        $message .= "User: `{$userEmail}`\n";
        $message .= "Field: `{$field}`\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
        $message .= "IP: " . request()->ip() . "\n\n";
        $message .= "🛡️ Malicious input sanitized - investigation required";

        return $this->sendMessage($message);
    }

    /**
     * Send account lockout alert
     */
    public function sendAccountLockoutAlert(string $userEmail, string $reason): bool
    {
        $message = "🔒 *ACCOUNT LOCKED*\n\n";
        $message .= "User: `{$userEmail}`\n";
        $message .= "Reason: {$reason}\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $message .= "Administrator action may be required";

        return $this->sendMessage($message);
    }

    /**
     * Send suspicious file upload alert
     */
    public function sendSuspiciousUploadAlert(string $userEmail, string $fileName, string $reason): bool
    {
        $message = "⚠️ *SUSPICIOUS FILE UPLOAD*\n\n";
        $message .= "User: `{$userEmail}`\n";
        $message .= "File: `{$fileName}`\n";
        $message .= "Reason: {$reason}\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $message .= "📋 Review upload logs for more details";

        return $this->sendMessage($message);
    }

    /**
     * Send MFA disabled alert
     */
    public function sendMfaDisabledAlert(string $userEmail): bool
    {
        $message = "⚠️ *MFA DISABLED*\n\n";
        $message .= "User: `{$userEmail}`\n";
        $message .= "Time: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $message .= "🔓 Two-factor authentication has been disabled";

        return $this->sendMessage($message);
    }

    /**
     * Send daily security summary
     */
    public function sendDailySummary(): bool
    {
        $today = today();

        $stats = [
            'failed_logins' => SecurityLog::failedLogins()->whereDate('created_at', $today)->count(),
            'unauthorized_attempts' => SecurityLog::unauthorizedAttempts()->whereDate('created_at', $today)->count(),
            'critical_events' => SecurityLog::critical()->whereDate('created_at', $today)->count(),
            'users_affected' => SecurityLog::distinct('user_id')->whereDate('created_at', $today)->count(),
        ];

        $message = "📊 *DAILY SECURITY SUMMARY*\n";
        $message .= "Date: " . $today->format('Y-m-d') . "\n\n";
        $message .= "Failed Logins: {$stats['failed_logins']}\n";
        $message .= "Unauthorized Attempts: {$stats['unauthorized_attempts']}\n";
        $message .= "Critical Events: {$stats['critical_events']}\n";
        $message .= "Users Affected: {$stats['users_affected']}\n\n";

        if ($stats['critical_events'] > 0) {
            $message .= "🚨 Critical events detected - review immediately";
        } else {
            $message .= "✅ No critical security events";
        }

        return $this->sendMessage($message);
    }

    /**
     * Format security log to alert message
     */
    private function formatAlertMessage(SecurityLog $log): string
    {
        $emoji = match ($log->action) {
            'login_failed' => '❌',
            'unauthorized_access' => '🚨',
            'sql_injection_attempt' => '🛡️',
            'xss_attempt' => '🛡️',
            'account_lockout' => '🔒',
            'suspicious_upload' => '⚠️',
            'mfa_disabled' => '⚠️',
            default => '📋',
        };

        $message = "{$emoji} *" . strtoupper(str_replace('_', ' ', $log->action)) . "*\n\n";

        if ($log->user) {
            $message .= "User: `{$log->user->email}`\n";
            $message .= "Role: {$log->user_role}\n";
        } elseif ($log->email) {
            $message .= "Email: `{$log->email}`\n";
        }

        $message .= "IP: " . ($log->ip_address ?? 'Unknown') . "\n";
        $message .= "Time: " . $log->created_at->format('Y-m-d H:i:s') . "\n\n";

        $message .= "Description: {$log->description}\n";

        if ($log->failure_reason) {
            $message .= "Reason: {$log->failure_reason}\n";
        }

        if ($log->attempt_count > 1) {
            $message .= "Attempts: {$log->attempt_count}\n";
        }

        return $message;
    }

    /**
     * Send message to Telegram
     */
    private function sendMessage(string $text): bool
    {
        if (!$this->botToken || !$this->chatId) {
            Log::warning('Telegram bot token or chat ID not configured');

            return false;
        }

        try {
            $response = Http::post($this->apiUrl . $this->botToken . '/sendMessage', [
                'chat_id' => $this->chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Test connection to Telegram API
     */
    public function testConnection(): bool
    {
        if (!$this->botToken || !$this->chatId) {
            return false;
        }

        try {
            $response = Http::get($this->apiUrl . $this->botToken . '/getMe');

            if ($response->successful()) {
                return $this->sendMessage('✅ *Telegram Alert Service Connected*\n\nHospital Management Security System is online.');
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram connection test failed: ' . $e->getMessage());

            return false;
        }
    }
}

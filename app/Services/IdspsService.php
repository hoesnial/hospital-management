<?php

namespace App\Services;

use App\Models\SecurityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IdspsService
{
    protected array $config;
    protected SecurityLoggerService $logger;
    protected TelegramAlertService $telegram;

    public function __construct()
    {
        $this->config = config('idsps');
        $this->logger = app(SecurityLoggerService::class);
        $this->telegram = app(TelegramAlertService::class);
    }

    public function isEnabled(): bool
    {
        return $this->config['mode'] !== 'off';
    }

    public function isPreventionMode(): bool
    {
        return $this->config['mode'] === 'prevent';
    }

    public function detectAndLog(string $attackType, string $description, array $details = []): ?SecurityLog
    {
        $ip = request()->ip();
        $this->incrementSuspiciousCount($ip);

        $log = SecurityLog::create([
            'user_id' => auth()->id(),
            'email' => auth()->user()?->email ?? 'guest',
            'user_agent' => request()->userAgent(),
            'ip_address' => $ip,
            'user_role' => auth()->user()?->role ?? 'guest',
            'action' => $attackType,
            'event_type' => 'ids_ips',
            'severity' => 'critical',
            'description' => $description,
            'details' => array_merge($details, [
                'method' => request()->method(),
                'path' => request()->path(),
                'query' => request()->query(),
            ]),
            'failure_reason' => $attackType,
            'alert_sent' => false,
        ]);

        $this->sendAlertIfNeeded($log, $attackType, $description);

        if ($this->isPreventionMode()) {
            $this->blockIfThresholdExceeded($ip);
        }

        return $log;
    }

    public function isIpBlacklisted(string $ip): bool
    {
        return in_array($ip, $this->config['blacklisted_ips']);
    }

    public function isIpWhitelisted(string $ip): bool
    {
        return in_array($ip, $this->config['whitelisted_ips']);
    }

    public function isIpBlocked(string $ip): bool
    {
        if ($this->isIpBlacklisted($ip)) {
            return true;
        }
        return Cache::has("idsps:blocked:{$ip}");
    }

    public function getSuspiciousCount(string $ip): int
    {
        return Cache::get("idsps:suspicious:{$ip}", 0);
    }

    public function incrementSuspiciousCount(string $ip): int
    {
        $key = "idsps:suspicious:{$ip}";
        $count = Cache::increment($key);
        Cache::expire($key, now()->addMinutes($this->config['block_duration']));
        return $count;
    }

    public function blockIp(string $ip, int $durationMinutes = null): void
    {
        $duration = $durationMinutes ?? $this->config['block_duration'];
        Cache::put("idsps:blocked:{$ip}", true, now()->addMinutes($duration));

        Log::warning("IDS/IPS blocked IP: {$ip} for {$duration} minutes");

        $this->telegram->sendCustomAlert(
            'IP Blocked by IDS/IPS',
            "IP: `{$ip}`\nDuration: {$duration} minutes\nReason: Threshold exceeded\nTime: " . now()->format('Y-m-d H:i:s'),
            'critical'
        );
    }

    public function unblockIp(string $ip): void
    {
        Cache::forget("idsps:blocked:{$ip}");
        Cache::forget("idsps:suspicious:{$ip}");
    }

    public function isExcludedPath(string $path): bool
    {
        foreach ($this->config['excluded_paths'] as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return true;
            }
        }
        return false;
    }

    public function checkInput($value, string $key = ''): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        if (strlen($value) > $this->config['max_input_length']) {
            return 'Input exceeds maximum allowed length';
        }

        // SQL Injection check
        foreach ($this->config['sql_injection_patterns'] as $pattern) {
            if (preg_match($pattern, $value)) {
                return "SQL injection pattern detected in field: {$key}";
            }
        }

        // XSS check
        foreach ($this->config['xss_patterns'] as $pattern) {
            if (preg_match($pattern, $value)) {
                return "XSS pattern detected in field: {$key}";
            }
        }

        // Path traversal check
        foreach ($this->config['path_traversal_patterns'] as $pattern) {
            if (preg_match($pattern, $value)) {
                return "Path traversal pattern detected in field: {$key}";
            }
        }

        // Command injection check
        foreach ($this->config['command_injection_patterns'] as $pattern) {
            if (preg_match($pattern, $value)) {
                return "Command injection pattern detected in field: {$key}";
            }
        }

        return null;
    }

    public function checkUserAgent(string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return 'Missing User-Agent header';
        }

        foreach ($this->config['suspicious_user_agents'] as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return "Suspicious User-Agent detected: {$agent}";
            }
        }

        return null;
    }

    public function checkRequestRate(string $ip): bool
    {
        $maxRate = $this->config['max_request_rate'];
        if ($maxRate <= 0) {
            return false;
        }

        $key = "idsps:rate:{$ip}";
        $count = Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->addMinute());

        return $count > $maxRate;
    }

    protected function sendAlertIfNeeded(SecurityLog $log, string $attackType, string $description): void
    {
        $ip = request()->ip();
        $count = $this->getSuspiciousCount($ip);

        if ($count >= $this->config['alert_threshold']) {
            $this->telegram->sendCustomAlert(
                "IDS/IPS: " . strtoupper(str_replace('_', ' ', $attackType)),
                "IP: `{$ip}`\nPath: " . request()->fullUrl() . "\nCount: {$count}\nDescription: {$description}\nTime: " . now()->format('Y-m-d H:i:s'),
                'critical'
            );
        }

        $this->telegram->sendAlert($log);
    }

    protected function blockIfThresholdExceeded(string $ip): void
    {
        $count = $this->getSuspiciousCount($ip);
        if ($count >= $this->config['max_suspicious_requests']) {
            $this->blockIp($ip);
        }
    }
}

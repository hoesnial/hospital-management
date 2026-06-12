<?php

namespace App\Console\Commands;

use App\Services\IdspsService;
use Illuminate\Console\Command;

class IdspsManageCommand extends Command
{
    protected $signature = 'idsps:manage
                           {action : Action to perform (status|block|unblock|whitelist|blacklist|flush|test)}
                           {--ip= : IP address for block/unblock/whitelist/blacklist actions}
                           {--duration=30 : Block duration in minutes (default: 30)}
                           {--mode= : Set IDS/IPS mode (detect|prevent|off)}';

    protected $description = 'Manage the IDS/IPS system';

    public function handle(IdspsService $idsps)
    {
        $action = $this->argument('action');

        return match ($action) {
            'status' => $this->showStatus($idsps),
            'block' => $this->blockIp($idsps),
            'unblock' => $this->unblockIp($idsps),
            'flush' => $this->flushAll($idsps),
            'test' => $this->runTest($idsps),
            default => $this->error("Unknown action: {$action}. Available: status, block, unblock, flush, test"),
        };
    }

    protected function showStatus(IdspsService $idsps): int
    {
        $mode = config('idsps.mode');
        $modeStr = match ($mode) {
            'detect' => '🟡 Detection Mode (Log Only)',
            'prevent' => '🔴 Prevention Mode (Block & Log)',
            'off' => '⚪ Disabled',
            default => "⚪ Unknown ({$mode})",
        };

        $this->info('=== IDS/IPS Status ===');
        $this->line("Mode: {$modeStr}");
        $this->line("Max Suspicious Requests: " . config('idsps.max_suspicious_requests'));
        $this->line("Block Duration: " . config('idsps.block_duration') . ' minutes');
        $this->line("Alert Threshold: " . config('idsps.alert_threshold'));
        $this->line("Max Request Rate: " . config('idsps.max_request_rate') . ' req/min');
        $this->line("Whitelisted IPs: " . implode(', ', config('idsps.whitelisted_ips') ?: ['none']));
        $this->line("Blacklisted IPs: " . implode(', ', config('idsps.blacklisted_ips') ?: ['none']));

        if ($mode === 'off') {
            $this->warn("\nIDS/IPS is DISABLED. Set IDSPS_MODE=detect or IDSPS_MODE=prevent in .env to enable.");
        }

        return 0;
    }

    protected function blockIp(IdspsService $idsps): int
    {
        $ip = $this->option('ip');
        if (!$ip) {
            $this->error('--ip is required');
            return 1;
        }

        $duration = (int)$this->option('duration');
        $idsps->blockIp($ip, $duration);
        $this->info("✅ IP {$ip} blocked for {$duration} minutes");

        return 0;
    }

    protected function unblockIp(IdspsService $idsps): int
    {
        $ip = $this->option('ip');
        if (!$ip) {
            $this->error('--ip is required');
            return 1;
        }

        $idsps->unblockIp($ip);
        $this->info("✅ IP {$ip} unblocked");

        return 0;
    }

    protected function flushAll(IdspsService $idsps): int
    {
        if (!$this->confirm('Flush all temporary blocks and suspicious counts?')) {
            return 0;
        }

        // Flush by key pattern - use cache store directly
        $store = cache()->store(config('cache.default'));
        $this->info('🔄 Flushing all IDS/IPS cache entries...');
        $store->flush();

        $this->info('✅ All temporary blocks and counters flushed');

        return 0;
    }

    protected function runTest(IdspsService $idsps): int
    {
        $this->info('=== IDS/IPS Test ===');

        $testCases = [
            ['SQL Injection', "1' OR '1'='1"],
            ['SQL Injection', 'UNION SELECT * FROM users'],
            ['XSS', '<script>alert("xss")</script>'],
            ['XSS', 'javascript:alert(1)'],
            ['Path Traversal', '../../../etc/passwd'],
            ['Command Injection', '; whoami'],
            ['Command Injection', '`ls -la`'],
            ['Large Input', str_repeat('A', 50000)],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($testCases as [$type, $input]) {
            $result = $idsps->checkInput($input, 'test_field');
            if ($result !== null) {
                $this->info("✅ [{$type}] Detected: {$result}");
                $passed++;
            } else {
                $this->warn("⚠️  [{$type}] NOT DETECTED for: " . substr($input, 0, 50));
                $failed++;
            }
        }

        // Test suspicious User-Agent
        $uaResult = $idsps->checkUserAgent('sqlmap/1.5 (http://sqlmap.org)');
        if ($uaResult) {
            $this->info("✅ [Suspicious UA] Detected: {$uaResult}");
            $passed++;
        } else {
            $this->warn("⚠️  [Suspicious UA] NOT DETECTED");
            $failed++;
        }

        $total = $passed + $failed;
        $this->line('');
        $this->info("Results: {$passed}/{$total} passed, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }
}

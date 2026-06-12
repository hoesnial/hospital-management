<?php

namespace Tests\Unit;

use Tests\TestCase;

class SecurityConfigValidationTest extends TestCase
{
    public function test_idsps_config_has_required_keys(): void
    {
        $config = config('idsps');

        $this->assertNotNull($config, 'idsps config should exist');
        $this->assertArrayHasKey('mode', $config);
        $this->assertArrayHasKey('sql_injection_patterns', $config);
        $this->assertArrayHasKey('xss_patterns', $config);
        $this->assertArrayHasKey('path_traversal_patterns', $config);
        $this->assertArrayHasKey('command_injection_patterns', $config);
        $this->assertArrayHasKey('suspicious_user_agents', $config);
        $this->assertArrayHasKey('excluded_paths', $config);
        $this->assertArrayHasKey('whitelisted_ips', $config);
        $this->assertArrayHasKey('blacklisted_ips', $config);
        $this->assertArrayHasKey('max_suspicious_requests', $config);
        $this->assertArrayHasKey('block_duration', $config);
        $this->assertArrayHasKey('max_input_length', $config);
    }

    public function test_idsps_config_valid_mode(): void
    {
        $mode = config('idsps.mode');
        $this->assertContains($mode, ['detect', 'prevent', 'off'],
            "Mode must be one of: detect, prevent, off");
    }

    public function test_sql_injection_patterns_are_valid_regex(): void
    {
        foreach (config('idsps.sql_injection_patterns') as $pattern) {
            $this->assertNotFalse(@preg_match($pattern, ''),
                "Invalid regex pattern: {$pattern}");
        }
    }

    public function test_xss_patterns_are_valid_regex(): void
    {
        foreach (config('idsps.xss_patterns') as $pattern) {
            $this->assertNotFalse(@preg_match($pattern, ''),
                "Invalid regex pattern: {$pattern}");
        }
    }

    public function test_path_traversal_patterns_are_valid_regex(): void
    {
        foreach (config('idsps.path_traversal_patterns') as $pattern) {
            $this->assertNotFalse(@preg_match($pattern, ''),
                "Invalid regex pattern: {$pattern}");
        }
    }

    public function test_command_injection_patterns_are_valid_regex(): void
    {
        foreach (config('idsps.command_injection_patterns') as $pattern) {
            $this->assertNotFalse(@preg_match($pattern, ''),
                "Invalid regex pattern: {$pattern}");
        }
    }

    public function test_whitelisted_ips_includes_localhost(): void
    {
        $whitelisted = config('idsps.whitelisted_ips');
        $this->assertContains('127.0.0.1', $whitelisted);
        $this->assertContains('::1', $whitelisted);
    }

    public function test_session_encryption_is_enabled(): void
    {
        $this->assertEquals('true', env('SESSION_ENCRYPT', 'false'));
    }

    public function test_session_lifetime_is_secure(): void
    {
        $lifetime = config('session.lifetime', 120);
        $this->assertLessThanOrEqual(15, $lifetime,
            'Session lifetime should be at most 15 minutes');
    }

    public function test_hashing_uses_argon2id(): void
    {
        $driver = config('hashing.driver', 'bcrypt');
        $this->assertEquals('argon2id', $driver,
            'Password hashing should use Argon2id');
    }

    public function test_cors_config_allows_secure_origins(): void
    {
        $origins = config('cors.allowed_origins', []);
        // Should not have wildcard in production
        $this->assertNotContains('*', $origins,
            'CORS should not use wildcard origin');
    }

    public function test_app_debug_is_disabled_in_production(): void
    {
        if (config('app.env') === 'production') {
            $this->assertFalse(config('app.debug'),
                'APP_DEBUG must be false in production');
        }
    }

    public function test_encryptable_trait_defined_in_models(): void
    {
        $models = [
            \App\Models\Appointment::class,
            \App\Models\Prescription::class,
            \App\Models\BookingDiagnostic::class,
            \App\Models\TestResult::class,
        ];

        foreach ($models as $model) {
            $traits = class_uses($model);
            $this->assertContains(
                \App\Models\Concerns\Encryptable::class,
                $traits,
                "{$model} should use Encryptable trait"
            );
        }
    }

    public function test_security_log_model_exists(): void
    {
        $this->assertTrue(class_exists(\App\Models\SecurityLog::class));
    }

    public function test_encryption_service_exists(): void
    {
        $this->assertTrue(class_exists(\App\Services\EncryptionService::class));
    }

    public function test_idsps_service_exists(): void
    {
        $this->assertTrue(class_exists(\App\Services\IdspsService::class));
    }

    public function test_idsps_middleware_exists(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\IdspsMiddleware::class));
    }

    public function test_encryptable_trait_exists(): void
    {
        $this->assertTrue(trait_exists(\App\Models\Concerns\Encryptable::class));
    }

    public function test_encryptable_columns_defined_in_models(): void
    {
        $reflection = new \ReflectionProperty(\App\Models\Appointment::class, 'encryptable');
        $reflection->setAccessible(true);
        $model = new \App\Models\Appointment();
        $encryptable = $reflection->getValue($model);
        $this->assertIsArray($encryptable);
        $this->assertContains('additional_notes', $encryptable);

        $reflectionP = new \ReflectionProperty(\App\Models\Prescription::class, 'encryptable');
        $reflectionP->setAccessible(true);
        $prescription = new \App\Models\Prescription();
        $this->assertContains('prescription_text', $reflectionP->getValue($prescription));

        $reflectionB = new \ReflectionProperty(\App\Models\BookingDiagnostic::class, 'encryptable');
        $reflectionB->setAccessible(true);
        $booking = new \App\Models\BookingDiagnostic();
        $encryptableB = $reflectionB->getValue($booking);
        $this->assertContains('address', $encryptableB);
        $this->assertContains('additional_notes', $encryptableB);

        $reflectionT = new \ReflectionProperty(\App\Models\TestResult::class, 'encryptable');
        $reflectionT->setAccessible(true);
        $result = new \App\Models\TestResult();
        $this->assertContains('file_path', $reflectionT->getValue($result));
    }
}

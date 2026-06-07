<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // MFA enabled flag
            $table->boolean('mfa_enabled')->default(false)->after('password');

            // MFA secret (encrypted TOTP secret)
            $table->text('mfa_secret')->nullable()->after('mfa_enabled');

            // MFA backup codes (encrypted, JSON array)
            $table->text('mfa_backup_codes')->nullable()->after('mfa_secret');

            // MFA verified at (timestamp when MFA was first verified)
            $table->timestamp('mfa_verified_at')->nullable()->after('mfa_backup_codes');

            // MFA disabled at (timestamp when MFA was disabled)
            $table->timestamp('mfa_disabled_at')->nullable()->after('mfa_verified_at');

            // Last MFA code used (to prevent code reuse)
            $table->string('mfa_last_code')->nullable()->after('mfa_disabled_at');

            // Last MFA code timestamp (for rate limiting)
            $table->timestamp('mfa_last_code_at')->nullable()->after('mfa_last_code');

            // Create index for finding MFA-enabled users
            $table->index('mfa_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mfa_enabled',
                'mfa_secret',
                'mfa_backup_codes',
                'mfa_verified_at',
                'mfa_disabled_at',
                'mfa_last_code',
                'mfa_last_code_at',
            ]);
        });
    }
};

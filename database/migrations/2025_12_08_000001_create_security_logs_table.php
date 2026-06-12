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
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            
            // User information
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email', 100)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_role', 50)->nullable();
            
            // Event information
            $table->string('action', 100)->index();
            $table->string('event_type', 50)->index();
            $table->string('severity', 20)->default('info')->index(); // info, warning, critical
            $table->text('description')->nullable();
            $table->json('details')->nullable();
            
            // Security-specific fields
            $table->integer('attempt_count')->default(1);
            $table->string('failure_reason', 255)->nullable();
            $table->string('resource_type', 100)->nullable();
            $table->string('resource_id', 100)->nullable();
            
            // Alert status
            $table->boolean('alert_sent')->default(false)->index();
            $table->string('alert_channel', 50)->nullable();
            $table->timestamp('alert_sent_at')->nullable();
            
            // Metadata
            $table->string('device_id', 100)->nullable();
            $table->string('geo_location', 255)->nullable();
            $table->text('additional_data')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for queries
            $table->index(['created_at', 'severity']);
            $table->index(['user_id', 'action']);
            $table->index(['event_type', 'alert_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};

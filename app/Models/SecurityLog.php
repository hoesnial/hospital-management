<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Security Log Model
 *
 * Tracks all security-related events for audit and monitoring.
 */
class SecurityLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'user_agent',
        'ip_address',
        'user_role',
        'action',
        'event_type',
        'severity',
        'description',
        'details',
        'attempt_count',
        'failure_reason',
        'resource_type',
        'resource_id',
        'alert_sent',
        'alert_channel',
        'alert_sent_at',
        'device_id',
        'geo_location',
        'additional_data',
    ];

    protected $casts = [
        'details' => 'json',
        'additional_data' => 'json',
        'alert_sent' => 'boolean',
        'alert_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user associated with this security log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get critical events
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope: Get events for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get failed login attempts
     */
    public function scopeFailedLogins($query)
    {
        return $query->where('action', 'login_failed');
    }

    /**
     * Scope: Get unauthorized access attempts
     */
    public function scopeUnauthorizedAttempts($query)
    {
        return $query->where('event_type', 'unauthorized_access');
    }

    /**
     * Scope: Get alerts that haven't been sent
     */
    public function scopeUnsent($query)
    {
        return $query->where('alert_sent', false)->where('severity', 'critical');
    }

    /**
     * Mark log as alert sent
     */
    public function markAlertSent($channel = 'telegram'): void
    {
        $this->update([
            'alert_sent' => true,
            'alert_channel' => $channel,
            'alert_sent_at' => now(),
        ]);
    }
}

<?php

namespace App\Providers;

use App\Events\AccountLockout;
use App\Events\LoginFailed;
use App\Events\LoginSuccess;
use App\Events\MfaDisabled;
use App\Events\SqlInjectionAttempt;
use App\Events\SuspiciousUpload;
use App\Events\UnauthorizedAccess;
use App\Events\XssAttempt;
use App\Listeners\SecurityEventListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Phase 5-6: Security Events & Logging
        LoginSuccess::class => [
            SecurityEventListener::class . '@handleLoginSuccess',
        ],
        LoginFailed::class => [
            SecurityEventListener::class . '@handleLoginFailed',
        ],
        UnauthorizedAccess::class => [
            SecurityEventListener::class . '@handleUnauthorizedAccess',
        ],
        SuspiciousUpload::class => [
            SecurityEventListener::class . '@handleSuspiciousUpload',
        ],
        SqlInjectionAttempt::class => [
            SecurityEventListener::class . '@handleSqlInjectionAttempt',
        ],
        XssAttempt::class => [
            SecurityEventListener::class . '@handleXssAttempt',
        ],
        AccountLockout::class => [
            SecurityEventListener::class . '@handleAccountLockout',
        ],
        MfaDisabled::class => [
            SecurityEventListener::class . '@handleMfaDisabled',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}

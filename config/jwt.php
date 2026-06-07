<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Configuration
    |--------------------------------------------------------------------------
    |
    | JWT (JSON Web Token) configuration for API authentication.
    | This provides modern, stateless API authentication.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign and verify JWT tokens.
    | Must be kept secure and never exposed.
    |
    */
    'secret' => env('JWT_SECRET', 'your-secret-key-change-this'),

    /*
    |--------------------------------------------------------------------------
    | Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used for signing JWT tokens.
    | Options: HS256 (HMAC SHA-256), HS384, HS512, RS256 (RSA), etc.
    | Default: HS256 (recommended for most applications)
    |
    */
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Token Expiration (seconds)
    |--------------------------------------------------------------------------
    |
    | How long the JWT access token is valid for (in seconds).
    | Default: 3600 seconds (1 hour)
    | For high-security apps: 900 seconds (15 minutes)
    | For convenience: 86400 seconds (24 hours)
    |
    */
    'expiration' => env('JWT_EXPIRATION', 3600),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Expiration (seconds)
    |--------------------------------------------------------------------------
    |
    | How long the refresh token is valid for (in seconds).
    | Must be longer than access token expiration.
    | Default: 604800 seconds (7 days)
    |
    */
    'refresh_expiration' => env('JWT_REFRESH_EXPIRATION', 604800),

    /*
    |--------------------------------------------------------------------------
    | Token Leeway (seconds)
    |--------------------------------------------------------------------------
    |
    | Leeway (grace period) for token validation.
    | Useful for handling clock skew between servers.
    | Default: 0 seconds (no leeway)
    |
    */
    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Issuer
    |--------------------------------------------------------------------------
    |
    | The issuer of the token (typically your app name).
    | Used to validate token origin.
    |
    */
    'issuer' => env('JWT_ISSUER', env('APP_NAME', 'Hospital Management')),

    /*
    |--------------------------------------------------------------------------
    | Audience
    |--------------------------------------------------------------------------
    |
    | The audience (intended recipients) of the token.
    | Used to validate token scope.
    |
    */
    'audience' => env('JWT_AUDIENCE', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Token Blacklist
    |--------------------------------------------------------------------------
    |
    | Enable/disable JWT token blacklist (revocation) support.
    | When enabled, revoked tokens are stored in cache/database.
    | Useful for immediate logout (without waiting for expiration).
    |
    */
    'enable_blacklist' => env('JWT_ENABLE_BLACKLIST', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Cache Driver
    |--------------------------------------------------------------------------
    |
    | Cache driver for storing blacklisted tokens.
    | Options: database, redis, file, array, etc.
    | Must match one of your configured cache drivers.
    |
    */
    'blacklist_cache' => env('JWT_BLACKLIST_CACHE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Require Email Verification for JWT
    |--------------------------------------------------------------------------
    |
    | If true, users must verify their email before receiving JWT tokens.
    | Set to false to allow unverified users to obtain tokens (less secure).
    |
    */
    'require_email_verified' => env('JWT_REQUIRE_EMAIL_VERIFIED', true),

    /*
    |--------------------------------------------------------------------------
    | Require MFA for JWT
    |--------------------------------------------------------------------------
    |
    | If true, users must complete MFA before receiving JWT tokens.
    | Requires Phase 3 (MFA) implementation.
    | Note: Can be overridden per endpoint.
    |
    */
    'require_mfa' => env('JWT_REQUIRE_MFA', false),

    /*
    |--------------------------------------------------------------------------
    | Hash Algorithm for Token Revocation
    |--------------------------------------------------------------------------
    |
    | Algorithm to hash tokens for storage in blacklist.
    | Uses SHA-256 for security.
    |
    */
    'hash_algorithm' => 'sha256',

];

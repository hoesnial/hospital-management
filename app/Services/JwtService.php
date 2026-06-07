<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Exception;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * JWT Service
 *
 * Handles JWT token generation, validation, and revocation.
 * Uses Firebase/JWT library for standards-compliant JWT handling.
 */
class JwtService
{
    /**
     * The secret key for signing tokens
     */
    protected string $secret;

    /**
     * The algorithm for signing tokens
     */
    protected string $algorithm;

    /**
     * Token expiration time (seconds)
     */
    protected int $expiration;

    /**
     * Refresh token expiration time (seconds)
     */
    protected int $refreshExpiration;

    /**
     * Token issuer
     */
    protected string $issuer;

    /**
     * Token audience
     */
    protected string $audience;

    /**
     * Enable token blacklist
     */
    protected bool $enableBlacklist;

    /**
     * Blacklist cache driver
     */
    protected string $blacklistCache;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->algorithm = config('jwt.algorithm', 'HS256');
        $this->expiration = config('jwt.expiration', 3600);
        $this->refreshExpiration = config('jwt.refresh_expiration', 604800);
        $this->issuer = config('jwt.issuer', config('app.name'));
        $this->audience = config('jwt.audience', config('app.url'));
        $this->enableBlacklist = config('jwt.enable_blacklist', true);
        $this->blacklistCache = config('jwt.blacklist_cache', 'database');

        if (!$this->secret || $this->secret === 'your-secret-key-change-this') {
            throw new Exception('JWT_SECRET is not configured. Run: php artisan jwt:generate');
        }
    }

    /**
     * Generate a new JWT access token
     *
     * @param User $user
     * @param array $customClaims Additional claims to include
     * @return string JWT token
     */
    public function generateAccessToken(User $user, array $customClaims = []): string
    {
        $issuedAt = now();
        $expiresAt = $issuedAt->clone()->addSeconds($this->expiration);

        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
            'sub' => $user->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'type' => 'access',
            'jti' => $this->generateTokenId(), // JWT ID for tracking
            ...$customClaims,
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Generate a refresh token
     *
     * @param User $user
     * @return string JWT refresh token
     */
    public function generateRefreshToken(User $user): string
    {
        $issuedAt = now();
        $expiresAt = $issuedAt->clone()->addSeconds($this->refreshExpiration);

        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
            'sub' => $user->id,
            'user_id' => $user->id,
            'type' => 'refresh',
            'jti' => $this->generateTokenId(),
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Decode and validate a JWT token
     *
     * @param string $token
     * @return object|null Decoded token payload or null if invalid
     */
    public function decodeToken(string $token): ?object
    {
        try {
            // Check if token is blacklisted
            if ($this->enableBlacklist && $this->isTokenBlacklisted($token)) {
                return null;
            }

            $decoded = JWT::decode(
                $token,
                new Key($this->secret, $this->algorithm)
            );

            return $decoded;
        } catch (ExpiredException $e) {
            // Token expired
            return null;
        } catch (SignatureInvalidException $e) {
            // Invalid signature
            return null;
        } catch (BeforeValidException $e) {
            // Token not yet valid
            return null;
        } catch (Exception $e) {
            // Other JWT errors
            return null;
        }
    }

    /**
     * Validate a token string
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        return $this->decodeToken($token) !== null;
    }

    /**
     * Get user from JWT token
     *
     * @param string $token
     * @return User|null
     */
    public function getUserFromToken(string $token): ?User
    {
        $decoded = $this->decodeToken($token);

        if (!$decoded || !isset($decoded->user_id)) {
            return null;
        }

        return User::find($decoded->user_id);
    }

    /**
     * Revoke (blacklist) a token
     *
     * @param string $token
     * @return bool
     */
    public function revokeToken(string $token): bool
    {
        if (!$this->enableBlacklist) {
            return true; // Blacklist disabled, consider revoked
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->secret, $this->algorithm)
            );

            if (!isset($decoded->jti)) {
                return false;
            }

            $jti = $decoded->jti;
            $expiresAt = $decoded->exp;
            $ttl = $expiresAt - now()->timestamp;

            // Store in cache with TTL equal to token expiration
            if ($ttl > 0) {
                Cache::driver($this->blacklistCache)->put(
                    "jwt_blacklist:{$jti}",
                    true,
                    $ttl
                );
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if a token is blacklisted
     *
     * @param string $token
     * @return bool
     */
    public function isTokenBlacklisted(string $token): bool
    {
        if (!$this->enableBlacklist) {
            return false;
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->secret, $this->algorithm)
            );

            if (!isset($decoded->jti)) {
                return false;
            }

            return Cache::driver($this->blacklistCache)->has("jwt_blacklist:{$decoded->jti}");
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Revoke all tokens for a user
     *
     * Used when password is changed or account is compromised
     *
     * @param User $user
     * @return bool
     */
    public function revokeAllUserTokens(User $user): bool
    {
        if (!$this->enableBlacklist) {
            return true;
        }

        try {
            // Add a marker to cache to invalidate all tokens issued before now
            Cache::driver($this->blacklistCache)->put(
                "jwt_user_revoke:{$user->id}",
                now()->timestamp,
                $this->refreshExpiration
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if user's all tokens are revoked
     *
     * @param User $user
     * @return bool
     */
    public function areUserTokensRevoked(User $user, int $tokenIssuedAt): bool
    {
        if (!$this->enableBlacklist) {
            return false;
        }

        $revokedAt = Cache::driver($this->blacklistCache)->get("jwt_user_revoke:{$user->id}");

        return $revokedAt !== null && $revokedAt > $tokenIssuedAt;
    }

    /**
     * Generate a unique token ID (JTI)
     *
     * @return string
     */
    protected function generateTokenId(): string
    {
        return hash('sha256', implode('.', [
            now()->timestamp,
            random_bytes(16),
            config('app.key'),
        ]));
    }

    /**
     * Get token expiration time
     *
     * @return int Seconds
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }

    /**
     * Get refresh token expiration time
     *
     * @return int Seconds
     */
    public function getRefreshExpiration(): int
    {
        return $this->refreshExpiration;
    }
}

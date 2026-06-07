<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT Authentication Middleware
 *
 * Validates JWT tokens from Authorization header.
 * Authenticates the user if token is valid.
 */
class JwtAuthenticate
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract token from Authorization header
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'message' => 'Unauthorized: Missing authentication token',
                'error' => 'missing_token',
            ], 401);
        }

        // Validate token
        $payload = $this->jwtService->decodeToken($token);

        if (!$payload) {
            return response()->json([
                'message' => 'Unauthorized: Invalid or expired token',
                'error' => 'invalid_token',
            ], 401);
        }

        // Get user from token
        $user = $this->jwtService->getUserFromToken($token);

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized: User not found',
                'error' => 'user_not_found',
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active ?? true) {
            return response()->json([
                'message' => 'Unauthorized: Account is disabled',
                'error' => 'account_disabled',
            ], 401);
        }

        // Check if email is verified (if required)
        if (config('jwt.require_email_verified', true) && !$user->email_verified_at) {
            return response()->json([
                'message' => 'Unauthorized: Email not verified',
                'error' => 'email_not_verified',
            ], 401);
        }

        // Authenticate the user
        Auth::guard('api')->setUser($user);
        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('jwt_token', $token);

        return $next($request);
    }

    /**
     * Extract JWT token from request
     *
     * Tries multiple sources:
     * 1. Authorization: Bearer <token>
     * 2. Authorization: Token <token>
     * 3. X-API-Token header
     * 4. api_token query parameter (fallback)
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');

        // Check for Bearer token
        if ($authHeader && preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Check for Token header format
        if ($authHeader && preg_match('/Token\s+(.+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Check for X-API-Token header (alternative header)
        if ($apiToken = $request->header('X-API-Token')) {
            return trim($apiToken);
        }

        // Check for query parameter (less secure, only for development/special cases)
        if ($request->has('api_token')) {
            return trim($request->query('api_token'));
        }

        return null;
    }
}

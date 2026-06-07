<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JwtLoginRequest;
use App\Http\Requests\Api\JwtRefreshRequest;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * JWT Authentication Controller
 *
 * Handles API authentication using JWT tokens.
 * Provides endpoints for login, logout, token refresh, and profile.
 */
class JwtAuthController extends Controller
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
        $this->middleware('jwt.auth', except: ['login', 'refresh']);
    }

    /**
     * Login with email and password
     *
     * Validates credentials and returns JWT tokens.
     *
     * @param JwtLoginRequest $request
     * @return JsonResponse
     */
    public function login(JwtLoginRequest $request): JsonResponse
    {
        // Validate credentials
        if (!Auth::guard('api')->attempt($request->only('email', 'password'))) {
            // Log failed login attempt
            // This will be logged in Phase 5 - Security Logging
            
            return response()->json([
                'message' => 'Invalid credentials',
                'error' => 'invalid_credentials',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::guard('api')->user();

        // Check if email is verified
        if (!$user->email_verified_at && config('jwt.require_email_verified', true)) {
            Auth::guard('api')->logout();
            
            return response()->json([
                'message' => 'Email not verified',
                'error' => 'email_not_verified',
            ], 403);
        }

        // Check if MFA is required and enabled
        if (config('jwt.require_mfa', false) && ($user->mfa_enabled ?? false) && !($user->mfa_verified_at ?? null)) {
            // Store user in session temporarily for MFA verification
            $request->session()->put('mfa_pending_user_id', $user->id);
            
            return response()->json([
                'message' => 'MFA verification required',
                'error' => 'mfa_required',
                'user_id' => $user->id,
            ], 403);
        }

        // Generate tokens
        $accessToken = $this->jwtService->generateAccessToken($user, [
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
        ]);
        
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        // Log successful login
        // This will be enhanced in Phase 5 - Security Logging

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->getExpiration(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified' => (bool) $user->email_verified_at,
            ],
        ], 200);
    }

    /**
     * Refresh JWT access token
     *
     * Validates refresh token and issues new access token.
     *
     * @param JwtRefreshRequest $request
     * @return JsonResponse
     */
    public function refresh(JwtRefreshRequest $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        // Decode refresh token
        $payload = $this->jwtService->decodeToken($refreshToken);

        if (!$payload || ($payload->type ?? null) !== 'refresh') {
            return response()->json([
                'message' => 'Invalid refresh token',
                'error' => 'invalid_refresh_token',
            ], 401);
        }

        // Get user
        $user = $this->jwtService->getUserFromToken($refreshToken);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'error' => 'user_not_found',
            ], 401);
        }

        // Generate new access token
        $newAccessToken = $this->jwtService->generateAccessToken($user, [
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
        ]);

        return response()->json([
            'message' => 'Token refreshed successfully',
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->getExpiration(),
        ], 200);
    }

    /**
     * Logout - Revoke JWT token
     *
     * Blacklists the current token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $token = $request->attributes->get('jwt_token');

        if (!$user || !$token) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Revoke the token
        $this->jwtService->revokeToken($token);

        // Log logout event
        // This will be enhanced in Phase 5 - Security Logging

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get authenticated user profile
     *
     * Returns details of the currently authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified' => (bool) $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ], 200);
    }

    /**
     * Logout from all devices
     *
     * Revokes all tokens for the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Revoke all tokens
        $this->jwtService->revokeAllUserTokens($user);

        // Log all devices logout
        // This will be enhanced in Phase 5 - Security Logging

        return response()->json([
            'message' => 'Logged out from all devices successfully',
        ], 200);
    }
}

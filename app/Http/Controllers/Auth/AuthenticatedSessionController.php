<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    protected TotpService $totpService;

    public function __construct(TotpService $totpService)
    {
        $this->totpService = $totpService;
    }

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * PHASE 3 - MFA Integration:
     * - First authenticates user with email/password
     * - If MFA is enabled, redirects to MFA verification
     * - If MFA is disabled, establishes session as before
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if user has MFA enabled
        if ($this->totpService->isMfaSetup($user)) {
            // Store user ID and timestamp in session for MFA verification
            $request->session()->put('mfa_pending_user_id', $user->id);
            $request->session()->put('mfa_pending_timestamp', now()->timestamp);

            // Logout temporarily
            Auth::guard('web')->logout();

            // Redirect to MFA verification
            return Inertia::location(route('mfa.verify'));
        }

        // MFA not enabled, proceed with normal session establishment
        return $this->completeAuthentication($request);
    }

    /**
     * Complete authentication after MFA verification (if required)
     *
     * This method is called either:
     * 1. Directly from store() if MFA is disabled
     * 2. From MFA controller after successful verification
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function completeAuthentication(Request $request): RedirectResponse
    {
        // If user is not authenticated, authenticate from session MFA verification
        if (!Auth::check()) {
            $userId = $request->session()->get('mfa_pending_user_id');
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    Auth::guard('web')->login($user);
                }
            }
        }

        // Regenerate session for security (prevents session fixation)
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        // Logout from other devices
        Auth::logoutOtherDevices($request->password, 'argon2id');

        // Clear MFA temporary session data
        $request->session()->forget(['mfa_pending_user_id', 'mfa_pending_timestamp']);

        return Inertia::location(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Regenerate the session ID to prevent session fixation
        $request->session()->regenerate();

        return redirect('/');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Flow:
     * 1. Authenticate email/password
     * 2. If MFA enabled -> redirect to MFA verification
     * 3. If MFA disabled -> complete authentication
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $user = Auth::user();

        // Check MFA status
        if ($this->totpService->isMfaSetup($user)) {

            // Save temporary MFA session
            $request->session()->put('mfa_pending_user_id', $user->id);
            $request->session()->put('mfa_pending_timestamp', now()->timestamp);

            // Logout until MFA verification succeeds
            Auth::guard('web')->logout();

            return Inertia::location(route('mfa.verify'));
        }

        // No MFA -> finish login
        return $this->completeAuthentication($request);
    }

    /**
     * Complete authentication after successful MFA verification.
     */
    public function completeAuthentication(Request $request): RedirectResponse
    {
        // Re-login user from MFA session if needed
        if (!Auth::check()) {

            $userId = $request->session()->get('mfa_pending_user_id');

            if ($userId) {

                $user = \App\Models\User::find($userId);

                if ($user) {
                    Auth::guard('web')->login($user);
                }
            }
        }

        // Prevent session fixation
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        // Clear MFA temporary data
        $request->session()->forget([
            'mfa_pending_user_id',
            'mfa_pending_timestamp',
        ]);

        // Redirect to dashboard
        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->regenerate();

        return redirect('/');
    }
}
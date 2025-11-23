<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Removed guest middleware - it causes redirects instead of JSON responses
    }

    /**
     * Show the login form (returns JSON for OnlyBell SPA)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showLoginForm()
    {
        return response()->json(['message' => 'Please login']);
    }

    /**
     * Handle a login request to the application.
     * Password-only authentication: tries password against all users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $password = $request->input('password');

        // Get all users from database (ordered by role priority: superuser first, then admin)
        // Superuser login is always allowed, but admin login requires license
        $users = User::orderByRaw("CASE WHEN role = 'superuser' THEN 1 WHEN role = 'admin' THEN 2 ELSE 3 END")->get();

        foreach ($users as $user) {
            // Try to authenticate with this user's username and the provided password
            if (Auth::attempt(['username' => $user->username, 'password' => $password], true)) {
                $authenticatedUser = Auth::user();

                // Check if admin user needs license activation
                if ($authenticatedUser->role === 'admin' && !\App\Setting::isLicenseActive()) {
                    Auth::logout();
                    return response()->json([
                        'success' => false,
                        'message' => 'System license is not active. Please contact the superuser.'
                    ], 403);
                }

                // Regenerate session ID to prevent session fixation
                $request->session()->regenerate();

                // Force save the session to ensure it persists
                $request->session()->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'username' => $authenticatedUser->username,
                        'role' => $authenticatedUser->role,
                        'features_activated' => $authenticatedUser->features_activated,
                        'has_features' => $authenticatedUser->hasActivatedFeatures(),
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid password'
        ], 401);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}

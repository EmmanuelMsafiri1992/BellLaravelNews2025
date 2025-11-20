<?php

namespace App\Http\Controllers;

use App\Setting;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('superuser')->except(['status', 'activateFeatures']);
    }

    /**
     * Get license status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        $licenseSettings = Setting::getLicenseSettings();
        $status = $this->validateLicense($licenseSettings);

        return response()->json([
            'success' => true,
            'license' => [
                'status' => $status['status'],
                'message' => $status['message'],
                'key' => $licenseSettings['key'] ? substr($licenseSettings['key'], 0, 8) . '...' : null,
                'expiry' => $licenseSettings['expiry'],
                'isActive' => $status['isActive'],
            ]
        ]);
    }

    /**
     * Update system license.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|uuid',
            'expiry' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        // Save license settings
        Setting::updateLicenseSettings([
            'key' => $request->key,
            'expiry' => $request->expiry,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'License updated successfully'
        ]);
    }

    /**
     * Generate a new UUID license key.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate()
    {
        $licenseKey = (string) Str::uuid();

        return response()->json([
            'success' => true,
            'licenseKey' => $licenseKey
        ]);
    }

    /**
     * Get list of users who have activated features.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activatedUsers()
    {
        $users = User::where('features_activated', true)
            ->where('role', 'admin')
            ->get(['id', 'username', 'features_activated', 'created_at']);

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => $users->count()
        ]);
    }

    /**
     * Activate features for the authenticated admin user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activateFeatures()
    {
        $user = auth()->user();

        // Only admins can activate features (superusers already have all features)
        if ($user->isSuperuser()) {
            return response()->json([
                'success' => false,
                'message' => 'Superusers already have all features activated'
            ], 400);
        }

        // Check if system license is active
        if (!Setting::isLicenseActive()) {
            return response()->json([
                'success' => false,
                'message' => 'System license is not active. Please contact the superuser.'
            ], 403);
        }

        // Activate features for this user
        $user->activateFeatures();

        return response()->json([
            'success' => true,
            'message' => 'Features activated successfully for user: ' . $user->username
        ]);
    }

    /**
     * Validate a license.
     *
     * @param  array  $licenseSettings
     * @return array
     */
    private function validateLicense($licenseSettings)
    {
        $status = [
            'status' => 'unlicensed',
            'message' => 'No license installed',
            'isActive' => false,
        ];

        if (empty($licenseSettings['key'])) {
            return $status;
        }

        // Validate UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $licenseSettings['key'])) {
            $status['status'] = 'invalid_format';
            $status['message'] = 'Invalid license key format';
            return $status;
        }

        // Check expiry date
        if (empty($licenseSettings['expiry'])) {
            $status['status'] = 'invalid';
            $status['message'] = 'License has no expiry date';
            return $status;
        }

        $expiryDate = \Carbon\Carbon::parse($licenseSettings['expiry']);
        $now = \Carbon\Carbon::now();

        if ($expiryDate->isPast()) {
            $status['status'] = 'expired';
            $status['message'] = 'License expired on ' . $expiryDate->format('Y-m-d');
            return $status;
        }

        // License is valid and active
        $status['status'] = 'active';
        $status['message'] = 'License is active until ' . $expiryDate->format('Y-m-d');
        $status['isActive'] = true;

        return $status;
    }
}

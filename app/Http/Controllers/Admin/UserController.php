<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\License; // Import the License model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log; // Ensure Log facade is imported
use Carbon\Carbon; // Import Carbon

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the users.
     * Eager load the 'license' relationship.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Eager load the 'license' relationship to avoid N+1 query problem
        $users = User::with('license')->latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user', // Default role for new users, adjust as needed
            ]);
            return redirect()->route('admin.users.index')->with('success', 'User created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified user's password.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user's password in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user->password = Hash::make($request->password);
            $user->save();
            return redirect()->route('admin.users.index')->with('success', 'User password updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating user password: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update user password. Please try again.');
        }
    }

    /**
     * Show the form for editing a user's license.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function editLicense(User $user)
    {
        // Eager load the license relationship for the specific user
        $user->load('license');

        // Get available licenses (not used and not expired)
        $availableLicenses = License::where('is_used', false)
                                    ->where(function($query) {
                                        $query->whereNull('expires_at')
                                              ->orWhere('expires_at', '>', now());
                                    })
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return view('admin.users.edit_license', compact('user', 'availableLicenses'));
    }

    /**
     * Update a user's license in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function updateLicense(Request $request, User $user)
    {
        Log::info('Attempting to update license for user ID: ' . $user->id);
        Log::info('Request data:', $request->all());

        $request->validate([
            'is_used' => 'nullable', // Allow it to be present (e.g., "on") or not present
            'expires_at' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            // Eager load existing license
            $user->load('license');
            $existingLicense = $user->license;

            // Check if an expired license exists
            if ($existingLicense && $existingLicense->expires_at && $existingLicense->expires_at->copy()->endOfDay()->isPast()) {
                Log::warning('Attempt to reuse expired license', [
                    'user_id' => $user->id,
                    'license_id' => $existingLicense->id,
                    'expired_at' => $existingLicense->expires_at->toDateTimeString()
                ]);

                return redirect()->back()->with('error',
                    __('This license expired on :date and cannot be reused. Please delete the old license and create a new one.', [
                        'date' => $existingLicense->expires_at->format('F d, Y')
                    ])
                );
            }

            // Find or create a license record for this user
            $license = $user->license()->firstOrNew(['user_id' => $user->id]);

            // Set is_used based on checkbox presence
            $license->is_used = $request->has('is_used');
            Log::info('License is_used will be set to: ' . ($license->is_used ? 'true' : 'false'));

            // Set expires_at
            $license->expires_at = $request->input('expires_at');
            Log::info('License expires_at will be set to: ' . ($license->expires_at ? Carbon::parse($license->expires_at)->format('Y-m-d H:i:s') : 'null'));

            // If license doesn't have a code, generate one (for admin-created licenses)
            if (empty($license->code)) {
                $license->code = 'ADMIN-' . strtoupper(bin2hex(random_bytes(8)));
                Log::info('Generated license code: ' . $license->code);
            }

            $license->save(); // Save the license record

            // If the license is now expired or deactivated, invalidate the user's sessions
            $shouldInvalidate = false;
            $reason = '';

            if (!$license->is_used) {
                $shouldInvalidate = true;
                $reason = 'license deactivated';
            } elseif ($license->expires_at && $license->expires_at->copy()->endOfDay()->isPast()) {
                $shouldInvalidate = true;
                $reason = 'license expired';
            } elseif (!$license->expires_at) {
                $shouldInvalidate = true;
                $reason = 'no expiration date';
            }

            if ($shouldInvalidate) {
                // Delete all sessions for this user to force re-authentication
                \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();

                Log::info('User sessions invalidated', [
                    'user_id' => $user->id,
                    'reason' => $reason
                ]);
            }

            Log::info('License update successful for user ID: ' . $user->id);
            return redirect()->route('admin.users.index')->with('success', 'User license updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating user license: ' . $e->getMessage(), $e->errors());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating user license: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to update user license. Please try again.');
        }
    }

    /**
     * Assign an existing license from the pool to a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function assignLicense(Request $request, User $user)
    {
        Log::info('Attempting to assign license to user ID: ' . $user->id);
        Log::info('Request data:', $request->all());

        $request->validate([
            'license_id' => 'required|exists:licenses,id',
        ]);

        try {
            // Check if user already has a license
            $user->load('license');
            if ($user->license) {
                return redirect()->back()->with('error',
                    __('User already has a license assigned. Please delete the existing license first.')
                );
            }

            // Get the selected license
            $license = License::findOrFail($request->license_id);

            // Check if license is already used
            if ($license->is_used) {
                return redirect()->back()->with('error',
                    __('This license is already in use by another user.')
                );
            }

            // Check if license is expired
            if ($license->expires_at && $license->expires_at->copy()->endOfDay()->isPast()) {
                return redirect()->back()->with('error',
                    __('This license has expired and cannot be assigned.')
                );
            }

            // Assign the license to the user
            $license->user_id = $user->id;
            $license->is_used = true;
            $license->save();

            Log::info('License assigned successfully', [
                'user_id' => $user->id,
                'license_id' => $license->id,
                'license_code' => $license->code
            ]);

            return redirect()->route('admin.users.index')->with('success',
                __('License assigned successfully to :name!', ['name' => $user->name])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error assigning license: ' . $e->getMessage(), $e->errors());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error assigning license: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', __('Failed to assign license. Please try again.'));
        }
    }

    /**
     * Delete a user's license (allows creating a new one for expired licenses).
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function deleteLicense(User $user)
    {
        Log::info('Attempting to delete license for user ID: ' . $user->id);

        try {
            $user->load('license');

            if (!$user->license) {
                return redirect()->back()->with('error', __('User does not have a license to delete.'));
            }

            $licenseId = $user->license->id;
            $user->license->delete();

            // Invalidate all sessions for this user since they no longer have a license
            \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            Log::info('License deleted and sessions invalidated', [
                'user_id' => $user->id,
                'license_id' => $licenseId
            ]);

            return redirect()->route('admin.users.index')->with('success', __('License deleted successfully. You can now create a new license for this user.'));
        } catch (\Exception $e) {
            Log::error('Error deleting license: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', __('Failed to delete license. Please try again.'));
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        try {
            // Optionally, delete the associated license when a user is deleted
            if ($user->license) {
                $user->license->delete();
            }
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete user. Please try again.');
        }
    }
}

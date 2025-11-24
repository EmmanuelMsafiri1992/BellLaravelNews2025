<?php

use Illuminate\Support\Facades\Route;

// Home page - serves the Vue/vanilla JS SPA
Route::get('/', 'App\Http\Controllers\WelcomeController@index')->name('home');

// Health check endpoint (public)
Route::get('/health', 'App\Http\Controllers\DashboardController@status')->name('health');

// Health check endpoint (public)
Route::get('/health', 'App\Http\Controllers\DashboardController@status')->name('health');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', 'App\Http\Controllers\Auth\LoginController@login')->name('login');
Route::post('/logout', 'App\Http\Controllers\Auth\LoginController@logout')->name('logout');

// Password management
Route::middleware('auth')->group(function () {
    Route::post('/change_password', 'App\Http\Controllers\Auth\PasswordController@change')->name('password.change');
});

// Password reset (superuser only)
Route::middleware(['auth', 'superuser'])->group(function () {
    Route::post('/admin/reset_password', 'App\Http\Controllers\Auth\PasswordController@reset')->name('password.reset');
});

/*
|--------------------------------------------------------------------------
| Alarm Management Routes
|--------------------------------------------------------------------------
*/

// Read-only alarm routes (all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/api/alarms', 'App\Http\Controllers\AlarmController@index')->name('alarms.index');
    Route::get('/api/alarms/enabled', 'App\Http\Controllers\AlarmController@getEnabled')->name('alarms.enabled');
    Route::get('/api/alarms/day/{day}', 'App\Http\Controllers\AlarmController@getByDay')->name('alarms.by_day');
});

// Write alarm routes (authenticated + features activated)
Route::middleware(['auth', 'features'])->group(function () {
    Route::post('/set_alarm', 'App\Http\Controllers\AlarmController@store')->name('alarms.store');
    Route::post('/edit_alarm/{id}', 'App\Http\Controllers\AlarmController@update')->name('alarms.update');
    Route::patch('/api/alarms/{id}', 'App\Http\Controllers\AlarmController@patch')->name('alarms.patch');
    Route::delete('/delete_alarm/{id}', 'App\Http\Controllers\AlarmController@destroy')->name('alarms.destroy');
});

/*
|--------------------------------------------------------------------------
| Sound Library Routes
|--------------------------------------------------------------------------
*/

// Read-only sound routes (all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/api/sounds', 'App\Http\Controllers\SoundController@index')->name('sounds.index');
    Route::post('/test_sound', 'App\Http\Controllers\SoundController@test')->name('sounds.test');
});

// Write sound routes (authenticated + features activated)
Route::middleware(['auth', 'features'])->group(function () {
    Route::post('/upload', 'App\Http\Controllers\SoundController@upload')->name('sounds.upload');
    Route::delete('/delete_song/{filename}', 'App\Http\Controllers\SoundController@destroy')->name('sounds.destroy');
});

/*
|--------------------------------------------------------------------------
| System Settings Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/api/system_settings', 'App\Http\Controllers\SettingController@index')->name('settings.index');
    Route::post('/api/system_settings', 'App\Http\Controllers\SettingController@update')->name('settings.update');
    Route::get('/api/current_network_status', 'App\Http\Controllers\SettingController@currentNetworkStatus')->name('settings.network_status');
});

// Apply network settings (superuser only)
Route::middleware(['auth', 'superuser'])->group(function () {
    Route::post('/api/apply_network_settings', 'App\Http\Controllers\SettingController@applyNetworkSettings')->name('settings.apply_network');
});

/*
|--------------------------------------------------------------------------
| License Management Routes
|--------------------------------------------------------------------------
*/

// License status (all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/api/license_status', 'App\Http\Controllers\LicenseController@status')->name('license.status');
    Route::post('/activate_features', 'App\Http\Controllers\LicenseController@activateFeatures')->name('license.activate_features');
});

// License management (superuser only)
Route::middleware(['auth', 'superuser'])->group(function () {
    Route::post('/api/license', 'App\Http\Controllers\LicenseController@update')->name('license.update');
    Route::get('/api/generate_license_key', 'App\Http\Controllers\LicenseController@generate')->name('license.generate');
    Route::get('/api/licensed_users', 'App\Http\Controllers\LicenseController@activatedUsers')->name('license.activated_users');
});

/*
|--------------------------------------------------------------------------
| User Management Routes (Superuser Only)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'superuser'])->group(function () {
    Route::get('/api/users', 'App\Http\Controllers\UserController@index')->name('users.index');
    Route::post('/admin/add_user', 'App\Http\Controllers\UserController@store')->name('users.store');
    Route::get('/api/users/{id}', 'App\Http\Controllers\UserController@show')->name('users.show');
    Route::put('/api/users/{id}', 'App\Http\Controllers\UserController@update')->name('users.update');
    Route::delete('/api/users/{id}', 'App\Http\Controllers\UserController@destroy')->name('users.destroy');
});

/*
|--------------------------------------------------------------------------
| Dashboard & Metrics Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/api/metrics', 'App\Http\Controllers\DashboardController@metrics')->name('dashboard.metrics');
    Route::get('/api/server_time', 'App\Http\Controllers\DashboardController@serverTime')->name('dashboard.server_time');
    Route::get('/api/status', 'App\Http\Controllers\DashboardController@status')->name('dashboard.status');
});

// Removed test-auth route (used closure, prevented route caching)

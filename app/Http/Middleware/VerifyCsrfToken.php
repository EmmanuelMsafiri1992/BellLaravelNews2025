<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/login',
        '/logout',
        '/api/*',
        '/set_alarm',
        '/edit_alarm/*',
        '/delete_alarm/*',
        '/upload',
        '/delete_song/*',
        '/test_sound',
        '/change_password',
        '/admin/*',
        '/activate_features',
    ];
}

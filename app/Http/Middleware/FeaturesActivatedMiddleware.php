<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class FeaturesActivatedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to access this page.'
                ], 401);
            }
            return redirect('/')->with('error', 'You must be logged in to access this page.');
        }

        if (!Auth::user()->hasActivatedFeatures()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must activate features to access this functionality.'
                ], 403);
            }
            return redirect('/')->with('error', 'You must activate features to access this functionality.');
        }

        return $next($request);
    }
}

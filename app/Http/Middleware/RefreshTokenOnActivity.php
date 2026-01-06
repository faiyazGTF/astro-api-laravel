<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class RefreshTokenOnActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Check if the user is authenticated
        if (auth()->guard('admin')->check()) {
            // $token = JWTAuth::getToken();

            // Refresh the token's expiration time
            // $manager = JWTAuth::manager()->setBlacklistEnabled(true);
            // $newToken = $manager->refresh($token);
            // $response->headers->set('Authorization', 'Bearer ' . $newToken);


     


        }

        return $response;
    }
}

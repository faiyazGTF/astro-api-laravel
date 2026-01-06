<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHttpMethod
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next,...$method)
    {
        
        // if(!in_array($request->method(),$method)){
        //     return response()->json([
        //         'message'=>"Method is not allowed"
        //     ],405);
        // }
        return $next($request);
    }
}

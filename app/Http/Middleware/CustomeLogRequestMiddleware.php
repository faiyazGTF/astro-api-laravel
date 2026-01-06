<?php  
namespace App\Http\Middleware;

use Closure;
use App\Models\RequestLog;

class CustomeLogRequestMiddleware
{
    public function handle($request, Closure $next)
    {
        // Continue request
        $response = $next($request);

        // Save log in DB
        RequestLog::create([
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'ip'          => $request->ip(),
            'payload'     => json_encode($request->all()),
            'status_code' => $response->getStatusCode(),
        ]);

        return $response;
    }
}


?>
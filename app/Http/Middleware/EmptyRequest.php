<?php

namespace App\Http\Middleware;

use Closure;

class EmptyRequest
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
        if (empty($request->all())) {
            return response()->json([
                'status' => false,
                'msg'   => 'Bad Request!'
            ], 503);
        }
        return $next($request);
    }
}

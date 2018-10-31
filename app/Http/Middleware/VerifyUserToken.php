<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class VerifyUserToken
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
        $token = User::where('user_access_token', $request->access_token)->first();
        if (empty($token)) {
            return response()->json([
                'status' => false,
                'msg'    => 'Token is not valid.'
            ], 500);
        }
        return $next($request);
    }
}

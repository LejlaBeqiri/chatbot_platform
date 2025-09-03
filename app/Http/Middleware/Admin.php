<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin
{
    public function handle(Request $request, Closure $next)
    {

        if (auth()->user() && auth()->user()->hasRole('admin')) {
            return $next($request);
        }

        return response()->json(['data' => 'You are not authorized to perform this action!'], 403);
    }
}

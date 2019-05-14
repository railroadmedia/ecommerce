<?php

namespace Railroad\Ecommerce\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdministratorMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
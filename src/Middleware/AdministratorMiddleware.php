<?php

namespace Railroad\Ecommerce\Middleware;

use Closure;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;

class AdministratorMiddleware
{
    /**
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (request()->get('auth_level') === 'administrator') {
            // admins can see all payment methods by default
            PaymentMethodRepository::$pullAllPaymentMethods = true;

        }

        return $next($request);
    }
}
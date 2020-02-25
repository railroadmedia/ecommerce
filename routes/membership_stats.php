<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/membership-stats', Railroad\Ecommerce\Controllers\MembershipJsonController::class . '@stats')
        ->name('membership.stats');
});

<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/retention-stats', Railroad\Ecommerce\Controllers\RetentionJsonController::class . '@stats')
        ->name('retention.stats');

    Route::get(
        '/retention-stats/average-membership-end',
        Railroad\Ecommerce\Controllers\RetentionJsonController::class . '@averageMembershipEnd'
    )
        ->name('retention.stats');
});

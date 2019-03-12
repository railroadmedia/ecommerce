<?php

use Illuminate\Support\Facades\Route;

Route::put(
    '/discount-criteria/{discountId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountCriteriaJsonController::class . '@store',
    ]
)->name('discount.criteria.store');

Route::patch(
    '/discount-criteria/{discountCriteriaId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountCriteriaJsonController::class . '@update',
    ]
)->name('discount.criteria.update');

Route::delete(
    '/discount-criteria/{discountCriteriaId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountCriteriaJsonController::class . '@delete',
    ]
)->name('discount.criteria.delete');

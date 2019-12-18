<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Structures\AccountingProduct;
use Railroad\Ecommerce\Entities\Structures\AccountingProductTotals;
use Throwable;

class AccountingService
{
    /**
     * @var 
     */

    /**
     * AccountingService constructor.
     *
     * @param 
     */
    public function __construct(
    )
    {
    }

    /**
     * @param Request $request
     *
     * @return AccountingProductTotals
     *
     * @throws Throwable
     */
    public function indexByRequest(Request $request): AccountingProductTotals
    {
        $smallDate = $request->get(
            'small_date_time',
            Carbon::now()
                ->subDay()
                ->startOfDay()
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate)
                ->startOfDay();

        $bigDate = $request->get(
            'big_date_time',
            Carbon::now()
                ->subDay()
                ->endOfDay()
                ->toDateTimeString()
        );

        $bigDateTime =
            Carbon::parse($bigDate)
                ->endOfDay();

        $result = new AccountingProductTotals($smallDate, $bigDate);

        

        return $result;
    }
}

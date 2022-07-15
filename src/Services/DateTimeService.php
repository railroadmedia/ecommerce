<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use \Exception;

/**
 * Class DateTimeService
 * @package Railroad\Ecommerce\Services
 */
class DateTimeService
{

    /**
     * DateTimeService constructor.
     *
     */
    public function __construct()
    {
    }

    /**
     * @param Carbon $dateTime
     * @param $intervalType
     * @param int $nIntervals
     * @return mixed
     */
    public function addInterval(Carbon $dateTime, $intervalType, int $nIntervals = 1)
    {
        switch ($intervalType) {
            case config('ecommerce.interval_type_monthly'):
                return $dateTime->addMonths($nIntervals);
            case config('ecommerce.interval_type_yearly'):
                return $dateTime->addYears($nIntervals);
            case config('ecommerce.interval_type_daily'):
                return $dateTime->addDays($nIntervals);
            default:
                throw new Exception("intervalType '$intervalType' not handled.");
        }
    }


}

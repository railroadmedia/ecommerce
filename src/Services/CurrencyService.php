<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Location\Services\LocationService;

class CurrencyService
{
    /**
     * @var LocationService
     */
    private $locationService;

    /**
     * CurrencyService constructor.
     */
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * @return string
     */
    public function get()
    {
        $currency = $this->locationService->getCurrency();

        if (!in_array($currency, ConfigService::$supportedCurrencies) || empty($currency)) {
            $currency = ConfigService::$defaultCurrency;
        }

        return $currency;
    }

    public function convertFromBase($price, $currency): float
    {
        // todo - ask for specs on conversion rate
        return $price;
    }
}

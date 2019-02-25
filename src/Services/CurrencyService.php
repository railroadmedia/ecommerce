<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Location\Services\LocationService;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;

class CurrencyService
{
    const CONVERSION_CONFIG = 'Invalid conversion result';

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

    /**
     * Get the converion rate of specified currency
     *
     * @param string $currency
     *
     * @return float
     *
     * @throws PaymentFailedException
     */
    public function getRate(string $currency)
    {
        if (!$currency || !isset(ConfigService::$defaultCurrencyConversionRates[$currency])) {
            throw new PaymentFailedException(self::CONVERSION_CONFIG);
        }

        return ConfigService::$defaultCurrencyConversionRates[$currency];
    }

    /**
     * Converts base $price into $currency
     *
     * @param float $price
     * @param string $currency
     *
     * @return float
     *
     * @throws PaymentFailedException
     */
    public function convertFromBase(float $price, string $currency): float
    {
        $rate = $this->getRate($currency);

        return round($price * $rate, 2);
    }
}

<?php

namespace Railroad\Ecommerce\Services;

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
     *
     * @param LocationService $locationService
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

        if (!in_array($currency, config('ecommerce.supported_currencies')) || empty($currency)) {
            $currency = config('ecommerce.default_currency');
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
        if (!$currency || !isset(config('ecommerce.paypal.default_currency_conversion_rates')[$currency])) {
            throw new PaymentFailedException(self::CONVERSION_CONFIG);
        }

        return config('ecommerce.paypal.default_currency_conversion_rates')[$currency];
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

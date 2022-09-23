<?php

namespace Railroad\Ecommerce\ExternalHelpers;

class CurrencyConversion
{
    /**
     * @param $value
     * @param $from
     * @param string $to
     * @return float|void|null
     */
    public function convert($value, $from, $to = 'USD')
    {
        $cache =
            app()
                ->make('EcommerceArrayCache');

        if ($cache->get('exchangeRates')) {
            $response = $cache->get('exchangeRates');
        } else {
            //$url = 'https://api.exchangeratesapi.io/latest?symbols=' . $to . '&base=' . $from;
            try {
                //return null if api key is not defined
                if (!config('ecommerce.exchange_rate_api_token')) {
                    return null;
                }

                $url =
                    'https://v6.exchangerate-api.com/v6/' .
                    config('ecommerce.exchange_rate_api_token') .
                    '/latest/' .
                    $to;

                $response_json = file_get_contents($url);
                $response = json_decode($response_json);

                // Check for success
                $existError =
                    property_exists($response, 'error') ||
                    (property_exists($response, 'result') && $response->result != 'success');

                if ($existError) {
                    return null;
                }

                //cache exchangeRates
                $cache->put('exchangeRates', $response, $response->time_next_update_unix);

            } catch (Exception $e) {
                return null;
            }
        }

        $rate = $response->conversion_rates->$from;

        return round(($value / $rate), 2);
    }
}

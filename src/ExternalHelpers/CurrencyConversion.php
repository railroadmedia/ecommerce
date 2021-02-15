<?php

namespace Railroad\Ecommerce\ExternalHelpers;

class CurrencyConversion
{
    /**
     * @param $value
     * @param $from
     * @param string $to
     * @return float|int
     */
    public static function convert($value, $from, $to = 'USD')
    {
        $client = new \GuzzleHttp\Client();

        $url = 'https://api.exchangeratesapi.io/latest?symbols=' . $to . '&base=' . $from;

        $exchangeRate = json_decode(
            $client->get($url)
                ->getBody()
                ->getContents(),
            true
        )['rates'][$to];

        $amountUSD = (float)$exchangeRate * $value;

        return $amountUSD;
    }
}

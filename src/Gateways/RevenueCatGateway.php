<?php

namespace Railroad\Ecommerce\Gateways;

use GuzzleHttp\Exception\GuzzleException;

class RevenueCatGateway
{
    /**
     * @param $receipt
     * @param $user
     * @param $productId
     * @param $platform
     * @param null $localPrice
     * @param null $currency
     * @param string $app
     * @return string
     */
    public function sendRequest(
        $receipt,
        $user,
        $productId,
        $platform,
        $localPrice = null,
        $currency = null,
        $app = 'Musora'
    ) {
        $client = new \GuzzleHttp\Client();
        $userId = $user->getId();
        $bod = [
            'product_id' => $productId,
            'app_user_id' => "$userId",
            'fetch_token' => $receipt,
            'price' => $localPrice,
            'currency' => $currency,
            'observer_mode' => 'true',
            'attributes' => [
                'email' => [
                    'value' => $user->getEmail(),
                ],
            ],
        ];

        try {
            $response = $client->request('POST', 'https://api.revenuecat.com/v1/receipts', [
                'body' => json_encode($bod),
                'headers' => [
                    'X-Platform' => $platform,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer '.config('ecommerce.revenuecat.'.$platform)[$app],
                ],
            ]);
        } catch (GuzzleException $exception) {
            error_log($exception->getMessage());

            return $exception->getMessage();
        }

        return $response->getBody()
            ->getContents();
    }

    /**
     * @param $receipt
     * @param null $productId
     * @param $platform
     * @param null $localPrice
     * @param null $currency
     * @param string $app
     * @param null $userEmail
     * @param null $userId
     * @return string
     */
    public function purchase(
        $receipt,
        $productId = null,
        $platform,
        $localPrice = null,
        $currency = null,
        $app = 'Musora',
        $userEmail = null,
        $userId = null
    ) {
        $client = new \GuzzleHttp\Client();

        $bod = [
            'product_id' => $productId,
            'fetch_token' => $receipt,
            'price' => $localPrice,
            'currency' => $currency,
            'attributes' => [
                'email' => [
                    'value' => $userEmail,
                ],
            ],
        ];
        if ($userId) {
            $bod['app_user_id'] = "$userId";
        }

        try {
           // dd(config('ecommerce.revenuecat.'.$platform)[$app]);
            $response = $client->request('POST', 'https://api.revenuecat.com/v1/receipts', [
                'body' => json_encode($bod),
                'headers' => [
                    'X-Platform' => $platform,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer '.config('ecommerce.revenuecat.'.$platform)[$app],
                ],
            ]);
        } catch (GuzzleException $exception) {
            error_log($exception->getMessage());

            return $exception->getMessage();
        }

        return $response->getBody()
            ->getContents();
    }

    /**
     * @param $userId
     * @param $attributes
     * @param $musoraUserId
     * @param $platform
     * @param string $app
     * @return string
     */
    public function updateSubscriberAttribute($userId, $attributes, $musoraUserId, $platform, $app = 'Musora')
    {
        $client = new \GuzzleHttp\Client();
        $att = [];
        foreach ($attributes as $key => $value) {
            $att[$key] = [
                'value' => $value,
            ];
        }
        $bod = [
            'attributes' => $att,
        ];

        try {
            $response = $client->request('POST', 'https://api.revenuecat.com/v1/subscribers/'.$userId.'/attributes', [
                'body' => json_encode($bod),
                'headers' => [
                    'X-Platform' => $platform,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer '.config('ecommerce.revenuecat.'.$platform)[$app],
                ],
            ]);
        } catch (GuzzleException $exception) {
            error_log($exception->getMessage());

            return $exception->getMessage();
        }

        return $response->getBody()
            ->getContents();
    }

}
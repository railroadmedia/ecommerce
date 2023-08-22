<?php

namespace Railroad\Ecommerce\Gateways;

use GuzzleHttp\Exception\GuzzleException;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\Validator;
use Throwable;

class RevenueCatGateway
{
    public function sendRequest($receipt, $user, $productId, $platform, $localPrice = null, $currency = null, $app = 'Musora')
    {

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

}

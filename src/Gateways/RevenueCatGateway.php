<?php

namespace Railroad\Ecommerce\Gateways;

use GuzzleHttp\Exception\GuzzleException;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\Validator;
use Throwable;

class RevenueCatGateway
{
    public function sendRequest($receipt, $user)
    {
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('POST', 'https://api.revenuecat.com/v1/receipts', [
                'body' => '{"product_id":"DLM-1-year",
                "app_user_id":"'.$user->getId().'",
                "fetch_token":"'.$receipt->getReceipt().'"}',
                'headers' => [
                    'X-Platform' => 'ios',
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer '.config('ecommerce.revenuecat.token'),
                ],
            ]);
        } catch (GuzzleException $exception) {
            dd($exception->getMessage());
        }

        dd($response);
    }

}

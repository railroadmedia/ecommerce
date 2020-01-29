<?php

namespace Railroad\Ecommerce\Gateways;

use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use ReceiptValidator\GooglePlay\SubscriptionResponse;
use ReceiptValidator\GooglePlay\Validator;
use Google_Client;
use Google_Service_AndroidPublisher;
use Throwable;

class GooglePlayStoreGateway
{
    /**
     * @param string $packageName
     * @param string $productId
     * @param string $purchaseToken
     *
     * @return SubscriptionResponse
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function validate(
        string $packageName,
        string $productId,
        string $purchaseToken
    ): SubscriptionResponse
    {
        $validator = $this->getValidator();

        $response = $validator->setPackageName($packageName)
                        ->setProductId($productId)
                        ->setPurchaseToken($purchaseToken)
                        ->validateSubscription();

        $seconds = intval($response->getExpiryTimeMillis() / 1000);

        if (Carbon::createFromTimestamp($seconds) <= Carbon::now()) {
            throw new ReceiptValidationException('Subscription expired', $response);
        }

        return $response;
    }
    /**
     * @param string $packageName
     * @param string $productId
     * @param string $purchaseToken
     *
     * @return SubscriptionResponse
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function getResponse(
        string $packageName,
        string $productId,
        string $purchaseToken
    ): SubscriptionResponse
    {
        $validator = $this->getValidator();

        return $validator->setPackageName($packageName)
                        ->setProductId($productId)
                        ->setPurchaseToken($purchaseToken)
                        ->validateSubscription();
    }

    /**
     * @return Validator
     *
     * @throws ReceiptValidationException
     */
    public function getValidator(): Validator
    {
        $credentialsJson = config('ecommerce.payment_gateways.google_play_store.credentials');

        if (!$credentialsJson) {
            throw new ReceiptValidationException('Invalid google play store credentials json config');
        }

        $applicationName = config('ecommerce.payment_gateways.google_play_store.application_name');

        if (!$applicationName) {
            throw new ReceiptValidationException('Invalid google play store application name config');
        }

        $scope = config('ecommerce.payment_gateways.google_play_store.scope');

        if (!$scope) {
            throw new ReceiptValidationException('Invalid google play store scope config');
        }

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsJson);

        $client = new Google_Client();
        $client->setApplicationName($applicationName);
        $client->useApplicationDefaultCredentials();
        $client->setScopes($scope);

        $validator = new Validator(new Google_Service_AndroidPublisher($client));

        return $validator;
    }
}

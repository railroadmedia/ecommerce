<?php

namespace Railroad\Ecommerce\Gateways;

use Railroad\Ecommerce\Exceptions\AppleStoreKit\ReceiptValidationException;
use ReceiptValidator\iTunes\Validator;

class AppleStoreKitGateway
{
    /**
     * @param string $receipt
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function validate(string $receipt)
    {
        $validator = $this->getValidator();

        $validator->setReceiptData($receipt);

        $response = $validator->validate();

        if (!$response->isValid()) {
            throw new ReceiptValidationException(
                $this->getValidationErrorMessage(
                    $response->getResultCode()
                )
            );
        }

        return $response;
    }

    public function getValidator()
    {
        $endpoint = config('ecommerce.payment_gateways.apple_store_kit.endpoint');
        $sharedSecret = config('ecommerce.payment_gateways.apple_store_kit.shared_secret');

        if (!$endpoint) {
            throw new ReceiptValidationException('Invalid apple store kit enpoint config');
        }

        if (!$sharedSecret) {
            throw new ReceiptValidationException('Invalid apple store kit shared secret config');
        }

        $validator = new Validator($endpoint);

        return $validator->setSharedSecret($sharedSecret);
    }

    public function getValidationErrorMessage(int $errorCode)
    {
        switch ($errorCode) {
            case 0:
                return 'Validation OK';

            case 21000:
                return 'Apple could not read validation request JSON';

            case 21002:
                return 'The data in the receipt-data property was malformed or missing';

            case 21003:
                return 'The receipt could not be authenticated';

            case 21004:
                return 'The shared secret you provided does not match the shared secret on file for your account';

            case 21005:
                return 'The receipt server is not currently available';

            case 21006:
                return 'This receipt is valid but the subscription has expired';

            case 21007:
                return 'This receipt is from the test environment, but it was sent to the production environment for verification';

            case 21008:
                return 'This receipt is from the production environment, but it was sent to the test environment for verification';

            case 21010:
                return 'This receipt could not be authorized';

            default:
                return 'Unknown error code: ' . $errorCode;
        }
    }
}

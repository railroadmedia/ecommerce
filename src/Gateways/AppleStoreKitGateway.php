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

        return $validator->validate();
    }

    public function getValidator()
    {
        $endpoint = config('ecommerce.payment_gateways.apple_store_kit.endpoint');
        $sharedSecret = config('ecommerce.payment_gateways.apple_store_kit.shared_secret');

        if (!$endpoint) {
            throw new ReceiptValidationException('Invalid apple store kit enpoint config');
        }

        if (!$endpoint) {
            throw new ReceiptValidationException('Invalid apple store kit shared secret config');
        }

        $validator = new Validator($endpoint);

        return $validator->setSharedSecret($sharedSecret);
    }
}

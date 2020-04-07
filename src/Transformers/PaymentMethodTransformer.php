<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use Exception;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\PaymentMethod;

class PaymentMethodTransformer extends TransformerAbstract
{
    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return array
     */
    public function transform(PaymentMethod $paymentMethod)
    {
        $this->defaultIncludes[] = 'method';

        if (!empty($paymentMethod->getUserPaymentMethod())) {
            $this->defaultIncludes[] = 'userPaymentMethod';
        }

        if ($paymentMethod->getBillingAddress()) {
            $this->defaultIncludes[] = 'billingAddress';
        }

        return [
            'id' => $paymentMethod->getId(),
            'currency' => $paymentMethod->getCurrency(),
            'note' => $paymentMethod->getNote(),
            'deleted_at' => $paymentMethod->getDeletedAt() ?
                $paymentMethod->getDeletedAt()
                    ->toDateTimeString() : null,
            'created_at' => $paymentMethod->getCreatedAt() ?
                $paymentMethod->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $paymentMethod->getUpdatedAt() ?
                $paymentMethod->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return Item
     */
    public function includeBillingAddress(PaymentMethod $paymentMethod)
    {
        if (!empty($paymentMethod->getBillingAddress())) {
            return $this->item(
                $paymentMethod->getBillingAddress(),
                new AddressTransformer(),
                'address'
            );
        }
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return Item
     */
    public function includeUserPaymentMethod(PaymentMethod $paymentMethod)
    {
        return $this->item(
            $paymentMethod->getUserPaymentMethod(),
            new UserPaymentMethodsTransformer(),
            'userPaymentMethod'
        );
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return Item|null
     */
    public function includeMethod(PaymentMethod $paymentMethod)
    {
        if (!empty($paymentMethod->getCreditCard())) {
            return $this->item(
                $paymentMethod->getMethod(),
                new CreditCardTransformer(),
                'creditCard'
            );
        }

        if (!empty($paymentMethod->getPaypalBillingAgreement())) {
            return $this->item(
                $paymentMethod->getMethod(),
                new PaypalBillingAgreementTransformer(),
                'paypalBillingAgreement'
            );
        }

        return null;
    }
}

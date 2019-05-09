<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use Exception;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\PaymentMethod;

class PaymentMethodTransformer extends TransformerAbstract
{
    public function transform(PaymentMethod $paymentMethod)
    {
        $this->defaultIncludes[] = 'method';

        if ($paymentMethod->getBillingAddress()) {
            $this->defaultIncludes[] = 'billingAddress';
        }

        return [
            'id' => $paymentMethod->getId(),
            'method_id' => $paymentMethod->getMethod()->getId(),
            'method_type' => $paymentMethod->getMethodType(),
            'currency' => $paymentMethod->getCurrency(),
            'deleted_at' => $paymentMethod->getDeletedAt() ? $paymentMethod->getDeletedAt()->toDateTimeString() : null,
            'created_at' => $paymentMethod->getCreatedAt() ? $paymentMethod->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $paymentMethod->getUpdatedAt() ? $paymentMethod->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeBillingAddress(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->getBillingAddress() instanceof Proxy) {
            return $this->item(
                $paymentMethod->getBillingAddress(),
                new EntityReferenceTransformer(),
                'address'
            );
        } else {
            return $this->item(
                $paymentMethod->getBillingAddress(),
                new AddressTransformer(),
                'address'
            );
        }
    }

    public function includeMethod(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {

            if ($paymentMethod->getMethod() instanceof Proxy) {
                return $this->item(
                    $paymentMethod->getMethod(),
                    new EntityReferenceTransformer(),
                    'creditCard'
                );
            } else {
                return $this->item(
                    $paymentMethod->getMethod(),
                    new CreditCardTransformer(),
                    'creditCard'
                );
            }

        } elseif ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {

            if ($paymentMethod->getMethod() instanceof Proxy) {
                return $this->item(
                    $paymentMethod->getMethod(),
                    new EntityReferenceTransformer(),
                    'paypalBillingAgreement'
                );
            } else {
                return $this->item(
                    $paymentMethod->getMethod(),
                    new PaypalBillingAgreementTransformer(),
                    'paypalBillingAgreement'
                );
            }

        } else {
            throw new Exception('Invalid payment method type for ID: ' . $paymentMethod->getId());
        }
    }
}

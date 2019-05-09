<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Transformers\CreditCardTransformer;
use Railroad\Ecommerce\Transformers\PaymentMethodTransformer;
use Railroad\Ecommerce\Transformers\PaypalBillingAgreementTransformer;

class UserPaymentMethodsTransformer extends TransformerAbstract
{
    protected $creditCardsMap;
    protected $defaultIncludes = ['paymentMethod', 'user', 'method'];
    protected $paypalAgreementsMap;

    public function __construct(
        $creditCardsMap = [],
        $paypalAgreementsMap = []
    ) {

        $this->creditCardsMap = $creditCardsMap;
        $this->paypalAgreementsMap = $paypalAgreementsMap;
    }

    public function transform(UserPaymentMethods $userPaymentMethod)
    {
        return [
            'id' => $userPaymentMethod->getId(),
            'is_primary' => $userPaymentMethod->getIsPrimary(),
            'created_at' => $userPaymentMethod->getCreatedAt() ?
                                $userPaymentMethod->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $userPaymentMethod->getUpdatedAt() ?
                                $userPaymentMethod->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeUser(UserPaymentMethods $userPaymentMethod)
    {
        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $userPaymentMethod->getUser(),
            $userTransformer,
            'user'
        );
    }

    public function includePaymentMethod(
        UserPaymentMethods $userPaymentMethod
    ) {
        return $this->item(
            $userPaymentMethod->getPaymentMethod(),
            new PaymentMethodTransformer(),
            'paymentMethod'
        );
    }

    public function includeMethod(UserPaymentMethods $userPaymentMethod)
    {
        $paymentMethod = $userPaymentMethod->getPaymentMethod();

        // composite method handling
        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {
            return $this->item(
                $this->paypalAgreementsMap[$paymentMethod->getMethod()->getId()],
                new PaypalBillingAgreementTransformer(),
                'paypalAgreement'
            );
        } else {
            return $this->item(
                $this->creditCardsMap[$paymentMethod->getMethod()->getId()],
                new CreditCardTransformer(),
                'creditCard'
            );
        }
    }
}

<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;

class PaymentMethodService
{

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingRepository;

    CONST PAYPAL_PAYMENT_METHOD_TYPE = 'paypal';
    CONST CREDIT_CARD_PAYMENT_METHOD_TYPE = 'credit card';

    /**
     * PaymentMethodService constructor.
     * @param PaymentMethodRepository $paymentRepository
     */
    public function __construct(PaymentMethodRepository $paymentMethodRepository,
                                CreditCardRepository $creditCardRepository,
                                PaypalBillingAgreementRepository $paypalBillingAgreementRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->creditCardRepository = $creditCardRepository;
        $this->paypalBillingRepository = $paypalBillingAgreementRepository;
    }

    public function store(
        $type,
        $methodType,
        $creditCardYearSelector,
        $creditCardMonthSelector,
        $fingerprint,
        $last4,
        $cardHolderName,
        $companyName,
        $externalId,
        $externalProvider,
        $agreementId,
        $expressCheckoutToken,
        $addressId

    ) {
        if ($methodType == self::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $methodId = $this->creditCardRepository->create([
                'type' => self::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $fingerprint,
                'last_four_digits' => $last4,
                'cardholder_name' => $cardHolderName,
                'company_name' => $companyName,
                'external_id' => $externalId,
                'external_provider' => $externalProvider,
                'expiration_date' => Carbon::create(
                    $creditCardYearSelector,
                    $creditCardMonthSelector,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on' => Carbon::now()->toDateTimeString()
            ]);
        } else if ($methodType == self::PAYPAL_PAYMENT_METHOD_TYPE) {
            $methodId = $this->paypalBillingRepository->create(
                [
                    'agreement_id' => $agreementId,
                    'express_checkout_token' => $expressCheckoutToken,
                    'address_id' => $addressId,
                    'expiration_date' => Carbon::now()->addYears(10),
                    'created_on' => Carbon::now()->toDateTimeString()
                ]
            );
        } else {
            return null;
        }
        $paymentMethodId = $this->paymentMethodRepository->create([
            'type' => $type,
            'method_id' => $methodId,
            'method_type' => $methodType,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->paymentMethodRepository->getById($paymentMethodId);
    }


}
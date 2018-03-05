<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;

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

    Private $customerPaymentMethodsRepository;

    private $userPaymentMethodsRepository;

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
                                PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
                                CustomerPaymentMethodsRepository $customerPaymentMethodsRepository,
                                UserPaymentMethodsRepository $userPaymentMethodsRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->creditCardRepository = $creditCardRepository;
        $this->paypalBillingRepository = $paypalBillingAgreementRepository;
        $this->customerPaymentMethodsRepository = $customerPaymentMethodsRepository;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
    }

    public function store(
        $methodType,
        $creditCardYearSelector = null,
        $creditCardMonthSelector = null,
        $fingerprint = '',
        $last4 = '',
        $cardHolderName = '',
        $companyName = '',
        $externalId = null,
        $agreementId = null,
        $expressCheckoutToken = '',
        $addressId = null,
        $userId = null,
        $customerId = null

    ) {
        if ($methodType == self::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $methodId = $this->creditCardRepository->create([
                'type' => self::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $fingerprint,
                'last_four_digits' => $last4,
                'cardholder_name' => $cardHolderName,
                'company_name' => $companyName,
                'external_id' => $externalId,
                'external_provider' => ConfigService::$creditCard['external_provider'],
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
            'method_id' => $methodId,
            'method_type' => $methodType,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        if ($userId) {
            $this->userPaymentMethodsRepository->create([
                'payment_method_id' => $paymentMethodId,
                'user_id' => $userId,
                'created_on' => Carbon::now()->toDateTimeString()
            ]);
        } else if ($customerId) {
            $this->customerPaymentMethodsRepository->create([
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customerId,
                'created_on' => Carbon::now()->toDateTimeString()
            ]);
        }

        return $this->paymentMethodRepository->getById($paymentMethodId);
    }

    public function delete($paymentMethodId)
    {

    }


}
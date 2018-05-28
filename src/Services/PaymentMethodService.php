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
     * @var CreditCardRepository
     */
    private $creditCardRepository;
    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;
    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;
    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;
    /**
     * @var CustomerPaymentMethodsRepository
     */
    private $customerPaymentMethodsRepository;

    CONST PAYPAL_PAYMENT_METHOD_TYPE = 'paypal';
    CONST CREDIT_CARD_PAYMENT_METHOD_TYPE = 'credit-card';

    public function __construct(
        CreditCardRepository $creditCardRepository,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        PaymentMethodRepository $paymentMethodRepository,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
    ) {
        $this->creditCardRepository = $creditCardRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
        $this->customerPaymentMethodsRepository = $customerPaymentMethodsRepository;
    }

    public function createUserCreditCard(
        $userId,
        $fingerPrint,
        $last4,
        $cardHolderName,
        $companyName,
        $expirationYear,
        $expirationMonth,
        $externalId,
        $externalCustomerId,
        $gatewayName,
        $billingAddressId = null,
        $currency = null,
        $makePrimary = false
    ) {
        $creditCard = $this->creditCardRepository->create(
            [
                'fingerprint' => $fingerPrint,
                'last_four_digits' => $last4,
                'cardholder_name' => $cardHolderName,
                'company_name' => $companyName,
                'expiration_date' => Carbon::createFromDate($expirationYear, $expirationMonth)->toDateTimeString(),
                'external_id' => $externalId,
                'external_customer_id' => $externalCustomerId,
                'payment_gateway_name' => $gatewayName,
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        $paymentMethod = $this->paymentMethodRepository->create(
            [
                'method_id' => $creditCard['id'],
                'method_type' => self::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency' => $currency ?? ConfigService::$defaultCurrency,
                'billing_address_id' => $billingAddressId,
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        if ($makePrimary) {
            $this->userPaymentMethodsRepository->query()->where('user_id', $userId)->update(['is_primary' => false]);
        }

        $userPaymentMethod = $this->userPaymentMethodsRepository->create(
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod['id'],
                'is_primary' => $makePrimary,
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        return $paymentMethod['id'];
    }
}
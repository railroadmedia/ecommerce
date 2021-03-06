<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\PayPal\CreateBillingAgreementException;
use Railroad\Ecommerce\ExternalHelpers\Paypal;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Factories\GatewayFactory;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository;
use Railroad\Ecommerce\Repositories\CustomerStripeCustomerRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Repositories\UserStripeCustomerRepository;
use Railroad\Location\Services\LocationService;
use Stripe\Error\InvalidRequest;

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
     * @var CustomerPaymentMethodsRepository
     */
    Private $customerPaymentMethodsRepository;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingRepository;

    /**
     * @var LocationService
     */
    private $locationService;

    /**
     * @var Stripe
     */
    private $stripe;

    /**
     * @var PayPal
     */
    private $payPalService;

    /**
     * @var UserStripeCustomerRepository
     */
    private $userStripeCustomerRepository;

    /**
     * @var CustomerStripeCustomerRepository
     */
    private $customerStripeCustomerRepository;

    /**
     * @var PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * @var \Railroad\Ecommerce\Services\StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Services\PaypalPaymentGateway
     */
    private $paypalPaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Factories\GatewayFactory
     */
    private $gatewayFactory;

    //constants that represent payment method types
    CONST PAYPAL_PAYMENT_METHOD_TYPE      = 'paypal';
    CONST CREDIT_CARD_PAYMENT_METHOD_TYPE = 'credit card';
    //constants for update action
    CONST UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD = 'create-credit-card';
    CONST UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD     = 'update-current-credit-card';
    CONST UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL             = 'use-paypal';

    /**
     * PaymentMethodService constructor.
     *
     * @param PaymentMethodRepository          $paymentMethodRepository
     * @param CreditCardRepository             $creditCardRepository
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     * @param CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
     * @param UserPaymentMethodsRepository     $userPaymentMethodsRepository
     */
    public function __construct(
        PaymentMethodRepository $paymentMethodRepository,
        CreditCardRepository $creditCardRepository,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        CustomerPaymentMethodsRepository $customerPaymentMethodsRepository,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        LocationService $locationService,
        Stripe $stripe,
        Paypal $payPal,
        UserStripeCustomerRepository $userStripeCustomerRepository,
        CustomerStripeCustomerRepository $customerStripeCustomerRepository,
        PaymentGatewayRepository $paymentGatewayRepository,
        StripePaymentGateway $stripePaymentGateway,
        PaypalPaymentGateway $paypalPaymentGateway,
        GatewayFactory $gatewayFactory
    ) {
        $this->paymentMethodRepository          = $paymentMethodRepository;
        $this->creditCardRepository             = $creditCardRepository;
        $this->paypalBillingRepository          = $paypalBillingAgreementRepository;
        $this->customerPaymentMethodsRepository = $customerPaymentMethodsRepository;
        $this->userPaymentMethodsRepository     = $userPaymentMethodsRepository;
        $this->locationService                  = $locationService;
        $this->stripe                           = $stripe;
        $this->payPalService                    = $payPal;
        $this->userStripeCustomerRepository     = $userStripeCustomerRepository;
        $this->customerStripeCustomerRepository = $customerStripeCustomerRepository;
        $this->paymentGatewayRepository         = $paymentGatewayRepository;
        $this->stripePaymentGateway             = $stripePaymentGateway;
        $this->paypalPaymentGateway             = $paypalPaymentGateway;
        $this->gatewayFactory                   = $gatewayFactory;
    }

    /** Save a new payment method, a new credit card/paypal billing record based on payment method type and
     * assign the new created payment method to the proper user/customer.
     * Return - null if the payment method type it's not credit card or paypal
     *        - the new created payment method
     *
     * @param string      $methodType
     * @param null        $creditCardYearSelector
     * @param null        $creditCardMonthSelector
     * @param string      $fingerprint
     * @param string      $last4
     * @param string      $cardHolderName
     * @param string      $companyName
     * @param null        $externalId
     * @param null        $agreementId
     * @param string      $expressCheckoutToken
     * @param null        $addressId
     * @param string|null $currency
     * @param integer     $paymentGatewayId
     * @param null        $userId
     * @param null        $customerId
     * @return array|mixed|null
     */
    public function store(
        $methodType,
        $paymentGatewayId,
        $creditCardYearSelector = null,
        $creditCardMonthSelector = null,
        $fingerprint = '',
        $last4 = '',
        $cardHolderName = '',
        $companyName = '',
        $expressCheckoutToken = '',
        $addressId = null,
        $currency = null,
        $userId = null,
        $customerId = null

    ) {
        $methodId = null;

        $paymentGateway        = $this->paymentGatewayRepository->getById($paymentGatewayId);
        $stripeUserMapping     = $this->userStripeCustomerRepository->getByUserId($userId);
        $stripeCustomerMapping = $this->customerStripeCustomerRepository->getByCustomerId($customerId);

        $gateway = $this->gatewayFactory->create($methodType);

        $data = $gateway->handlingData(
            [
                'paymentGateway'        => $paymentGateway,
                'creditCardYear'        => $creditCardYearSelector,
                'creditCardMonth'       => $creditCardMonthSelector,
                'fingerprint'           => $fingerprint,
                'last4'                 => $last4,
                'cardholder'            => $cardHolderName,
                'expressCheckoutToken'  => $expressCheckoutToken,
                'userId'                => $userId,
                'customerId'            => $customerId,
                'stripeUserMapping'     => $stripeUserMapping,
                'stripeCustomerMapping' => $stripeCustomerMapping
            ]);

        if(!$data['status'])
        {
            return $data;
        }

        if($methodType == self::CREDIT_CARD_PAYMENT_METHOD_TYPE)
        {
            $methodId = $this->creditCardRepository->create([
                'type'                 => self::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'          => $fingerprint,
                'last_four_digits'     => $last4,
                'cardholder_name'      => $cardHolderName,
                'company_name'         => $companyName,
                'external_id'          => $data['stripe_card_id'],
                'external_customer_id' => $data['stripe_customer_id'],
                'external_provider'    => ConfigService::$creditCard['external_provider'],
                'expiration_date'      => Carbon::create(
                    $creditCardYearSelector,
                    $creditCardMonthSelector,
                    12,
                    0,
                    0,
                    0
                ),
                'payment_gateway_id'   => $paymentGateway['id'],
                'created_on'           => Carbon::now()->toDateTimeString()
            ]);

            $this->syncStripeCustomer($userId, $customerId, $data);
        }
        else if($methodType == self::PAYPAL_PAYMENT_METHOD_TYPE)
        {
            if($data['results'])
            {
                $methodId = $this->paypalBillingRepository->create(
                    [
                        'agreement_id'           => $data['results'],
                        'express_checkout_token' => $expressCheckoutToken,
                        'address_id'             => $addressId,
                        'payment_gateway_id'     => $paymentGateway['id'],
                        'expiration_date'        => Carbon::now()->addYears(10),
                        'created_on'             => Carbon::now()->toDateTimeString()
                    ]
                );
            }
        }
        else
        {
            //unknown payment method type
            return null;
        }

        //can not continue if method not exist
        if(!$methodId)
        {
            return null;
        }

        // if the currency not exist on the request, get the currency with Location package, based on ip address
        if(!$currency)
        {
            $currency = $this->locationService->getCurrency();
        }
        $paymentMethodId = $this->createPaymentMethod($methodType, $methodId, $currency);

        if($userId)
        {
            $this->assignPaymentMethodToUser($userId, $paymentMethodId);
        }

        if($customerId)
        {
            $this->assignPaymentMethodToCustomer($customerId, $paymentMethodId);
        }

        $paymentMethod = $this->paymentMethodRepository->getById($paymentMethodId);
        $paymentMethod['status'] = true;

        return $paymentMethod;
    }

    /** Delete a payment method and the corresponding credit card/paypal billing
     * Return boolean or null if the payment method not exist
     *
     * @param integer $paymentMethodId
     * @return bool|null
     */
    public function delete($paymentMethodId)
    {
        $paymentMethod = $this->paymentMethodRepository->getById($paymentMethodId);

        if(!$paymentMethod)
        {
            return null;
        }

        if($paymentMethod['method_type'] == self::CREDIT_CARD_PAYMENT_METHOD_TYPE)
        {
            $this->creditCardRepository->delete($paymentMethod['method']['id']);
        }

        if($paymentMethod['method_type'] == self::PAYPAL_PAYMENT_METHOD_TYPE)
        {
            $this->paypalBillingRepository->delete($paymentMethod['method']['id']);
        }
        $this->customerPaymentMethodsRepository->deleteByPaymentMethodId($paymentMethod['id']);
        $this->userPaymentMethodsRepository->deleteByPaymentMethodId($paymentMethod['id']);

        return $this->paymentMethodRepository->delete($paymentMethodId);
    }

    /** Update payment method data.
     * Return - null if the payment method not exist or the user have not rights to access it
     *        - array with the updated payment method
     *
     * @param integer $paymentMethodId
     * @param array   $data
     * @return array|int|mixed|null
     */
    public function update($paymentMethodId, array $data)
    {
        $paymentMethod = $this->paymentMethodRepository->getById($paymentMethodId);
        if(!$paymentMethod)
        {
            return null;
        }

        $methodId       = null;
        $paymentGateway = $this->paymentGatewayRepository->getById($data['payment_gateway'] ?? null);

        if($data['update_method'] == self::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD)
        {
            $gateway               = $this->gatewayFactory->create(self::CREDIT_CARD_PAYMENT_METHOD_TYPE);
            $stripeUserMapping     = $this->userStripeCustomerRepository->getByUserId($data['user_id'] ?? null);
            $stripeCustomerMapping = $this->customerStripeCustomerRepository->getByCustomerId($data['customer_id'] ?? null);

            $paymentData = $gateway->handlingData(
                [
                    'paymentGateway'        => $paymentGateway,
                    'creditCardYear'        => $data['card_year'],
                    'creditCardMonth'       => $data['card_month'],
                    'fingerprint'           => $data['card_fingerprint'],
                    'last4'                 => $data['card_number_last_four_digits'],
                    'cardholder'            => $data['cardholder_name'],
                    'expressCheckoutToken'  => '',
                    'userId'                => $data['user_id'] ?? null,
                    'customerId'            => $data['customer_id'] ?? null,
                    'stripeUserMapping'     => $stripeUserMapping,
                    'stripeCustomerMapping' => $stripeCustomerMapping
                ]);
            if (!$paymentData['status']){
                return $paymentData;
            }

            $methodId = $this->creditCardRepository->create([
                'type'                 => self::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'          => $data['card_fingerprint'],
                'last_four_digits'     => $data['card_number_last_four_digits'],
                'cardholder_name'      => $data['cardholder_name'],
                'company_name'         => $data['company_name'],
                'external_id'          => $paymentData['stripe_card_id'],
                'external_customer_id' => $paymentData['stripe_customer_id'],
                'external_provider'    => ConfigService::$creditCard['external_provider'],
                'expiration_date'      => Carbon::create(
                    $data['card_year'],
                    $data['card_month'],
                    12,
                    0,
                    0,
                    0
                ),
                'payment_gateway_id'   => $paymentGateway['id'],
                'created_on'           => Carbon::now()->toDateTimeString()
            ]);

            $this->syncStripeCustomer($data['user_id'] ?? null, $data['customer_id'] ?? null, $paymentData);
        }
        else if($data['update_method'] == self::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD)
        {
            $this->creditCardRepository->update($paymentMethod['method']['id'],
                [
                    'expiration_date' => Carbon::create(
                        $data['card_year'],
                        $data['card_month'],
                        12,
                        0,
                        0,
                        0
                    ),
                    'updated_on'      => Carbon::now()->toDateTimeString()
                ]);
        }
        elseif($data['update_method'] == self::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL)
        {
            if(empty($data['express_checkout_token']))
            {
                return -1;
            }

            $gateway = $this->gatewayFactory->create(self::PAYPAL_PAYMENT_METHOD_TYPE);

            $paymentData = $gateway->handlingData(
                [
                    'paymentGateway'       => $paymentGateway,
                    'expressCheckoutToken' => $data['express_checkout_token'],
                    'userId'               => $data['user_id'] ?? null,
                    'customerId'           => $data['customer_id'] ?? null
                ]);
            if (!$paymentData['status']){
                return $paymentData;
            }

            $this->paypalBillingRepository->updateOrCreate(['id' => $paymentMethod['method']['id']], [
                'agreement_id'           => $paymentData['results'],
                'express_checkout_token' => $data['express_checkout_token'],
                'address_id'             => $data['address_id'],
                'payment_gateway_id'     => $paymentGateway['id'],
                'expiration_date'        => Carbon::now()->addYears(10),
                'created_on'             => Carbon::now()->toDateTimeString(),
                'updated_on'             => Carbon::now()->toDateTimeString()
            ]);
        }

        $this->paymentMethodRepository->update($paymentMethodId, [
            'method_id'   => $methodId ?? $paymentMethod['method']['id'],
            'method_type' => $data['method_type'],
            'updated_on'  => Carbon::now()->toDateTimeString()
        ]);

        return $this->paymentMethodRepository->getById($paymentMethodId);
    }

    /** Create payment method and return the id
     *
     * @param string $methodType
     * @param int    $methodId
     * @return int
     */
    private function createPaymentMethod($methodType, $methodId, $currency)
    {
        $paymentMethodId = $this->paymentMethodRepository->create([
            'method_id'   => $methodId,
            'method_type' => $methodType,
            'currency'    => $currency,
            'created_on'  => Carbon::now()->toDateTimeString()
        ]);

        return $paymentMethodId;
    }

    /** Assign payment method to user
     *
     * @param $userId
     * @param $paymentMethodId
     */
    private function assignPaymentMethodToUser($userId, $paymentMethodId)
    {
        $this->userPaymentMethodsRepository->create([
            'payment_method_id' => $paymentMethodId,
            'user_id'           => $userId,
            'created_on'        => Carbon::now()->toDateTimeString()
        ]);
    }

    /** Assign payment method to the customer
     *
     * @param $customerId
     * @param $paymentMethodId
     */
    private function assignPaymentMethodToCustomer($customerId, $paymentMethodId)
    {
        $this->customerPaymentMethodsRepository->create([
            'payment_method_id' => $paymentMethodId,
            'customer_id'       => $customerId,
            'created_on'        => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * @param $userId
     * @param $customerId
     * @param $data
     */
    private function syncStripeCustomer($userId, $customerId, $data)
    {
        if($userId)
        {
            $this->userStripeCustomerRepository->create([
                'user_id'            => $userId,
                'stripe_customer_id' => $data['stripe_customer_id'],
                'created_on'         => Carbon::now()->toDateTimeString()
            ]);
        }
        else
        {
            $this->customerStripeCustomerRepository->create([
                'customer_id'        => $customerId,
                'stripe_customer_id' => $data['stripe_customer_id'],
                'created_on'         => Carbon::now()->toDateTimeString()
            ]);
        }
    }
}
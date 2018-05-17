<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\DBAL\Driver\PDOException;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Stripe\Error\Card;
use Stripe\Error\InvalidRequest;

class StripePaymentGateway
{
    /**
     * @var Stripe
     */
    private $stripeService;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * StripePaymentGateway constructor.
     *
     * @param Stripe $stripeService
     */
    public function __construct(
        Stripe $stripeService,
        PaymentGatewayRepository $paymentGatewayRepository,
        CreditCardRepository $creditCardRepository
    ) {
        $this->stripeService            = $stripeService;
        $this->paymentGatewayRepository = $paymentGatewayRepository;
        $this->creditCardRepository     = $creditCardRepository;
    }

    public function chargePayment($due, $paid, $paymentMethod, $currency)
    {
        $paymentData = [
            'due'               => $due,
            'payment_method_id' => $paymentMethod['id'],
            'external_provider' => ConfigService::$creditCard['external_provider'],
            'created_on'        => Carbon::now()->toDateTimeString()
        ];

        try
        {
            $charge                     = $this->chargeStripeCreditCardPayment($due, $paymentMethod, $currency);
            $paymentData['external_id'] = $charge['results'];
            $paymentData['paid']        = $due;
            $paymentData['status']      = true;
            $paymentData['currency']    = $currency;
        }
        catch(InvalidRequest $e)
        {
            $paymentData['paid']        = 0;
            $paymentData['status']      = false;
            $paymentData['message']     = $e->getMessage();
            $paymentData['external_id'] = null;
        }

        return $paymentData;
    }

    /**
     * @param $due
     * @param $paymentMethod
     * @throws \Railroad\Ecommerce\ExternalHelpers\CardException
     */
    private function chargeStripeCreditCardPayment($due, $paymentMethod, $currency)
    {
        $paymentGateway = $this->paymentGatewayRepository->getById($paymentMethod['method']['payment_gateway_id']);

        $this->stripeService->setApiKey(ConfigService::$stripeAPI[$paymentGateway['config']]['stripe_api_secret']);

        $stripeCustomer = $this->stripeService->retrieveCustomer($paymentMethod['method']['external_customer_id']);

        $stripeCard = $this->stripeService->retrieveCard(
            $stripeCustomer,
            $paymentMethod['method']['external_id']
        );

        $chargeResponse = $this->stripeService->createCharge(
            $due * 100,
            $stripeCustomer,
            $stripeCard,
            $currency ?? $paymentMethod['currency']
        );

        if(!$chargeResponse['status'])
        {
            return $chargeResponse;
        }

        return
            [
                'status'  => true,
                'results' => $chargeResponse->id
            ];
    }

    /** Create credit card and return the id
     *
     * @param $creditCardYearSelector
     * @param $creditCardMonthSelector
     * @param $fingerprint
     * @param $last4
     * @param $cardHolderName
     * @param $companyName
     * @param $externalId
     * @return int
     */
    public function createCreditCard(
        $creditCardYearSelector,
        $creditCardMonthSelector,
        $fingerprint,
        $last4,
        $cardHolderName,
        $paymentGatewayId,
        $companyName,
        $stripeCustomer
    ) {
        $paymentGateway = $this->paymentGatewayRepository->read($paymentGatewayId);
        $this->stripeService->setApiKey(ConfigService::$stripeAPI[$paymentGateway['config']]['stripe_api_secret']);

        try
        {
            $token = $this->stripeService->createCardToken(
                $fingerprint,
                $creditCardMonthSelector,
                $creditCardYearSelector,
                $last4,
                $cardHolderName,
                '',
                '',
                '',
                '',
                '',
                ''
            );
        }
        catch(Card $e)
        {
            return
                [
                    'status'  => false,
                    'message' => 'Can not create token:: ' . $e->getMessage()
                ];
        }

        $stripeCard = $this->stripeService->createCard(
            $stripeCustomer,
            $token
        );

        $creditCard = $this->creditCardRepository->create(
            [
                'type'                 => 'credit card',
                'fingerprint'          => $fingerprint,
                'last_four_digits'     => $last4,
                'cardholder_name'      => $cardHolderName,
                'company_name'         => $companyName,
                'external_id'          => $stripeCard->id,
                'external_customer_id' => $stripeCustomer->id,
                'external_provider'    => 'stripe',
                'expiration_date'      => Carbon::create(
                    $creditCardYearSelector,
                    $creditCardMonthSelector,
                    12,
                    0,
                    0,
                    0
                ),
                'payment_gateway_id'   => $paymentGatewayId,
                'created_on'           => Carbon::now()->toDateTimeString()
            ]
        );

        return array_merge($creditCard, [
            'status'             => true,
            'stripe_customer_id' => $stripeCustomer->id,
            'stripe_card_id'     => $stripeCard->id
        ]);
    }

    /**
     * @param $stripeCustomerMapping
     * @return \Stripe\Customer
     */
    public function getStripeCustomer($stripeCustomerMapping)
    {
        $this->stripeService->setApiKey(ConfigService::$stripeAPI['stripe_1']['stripe_api_secret']);
        //TODO - we need the user/customer email address
        try
        {
            $stripeCustomer = $this->stripeService->retrieveCustomer($stripeCustomerMapping['stripe_customer_id'] ?? 0);
        }
        catch(InvalidRequest $exception)
        {
            $stripeCustomer = $this->stripeService->createCustomer(['email' => 'roxana@test.ro']);
        }

        return $stripeCustomer;
    }

    /** Get/create a Stripe customer and create a credit card
     *
     * @param array $data
     * @return array
     */
    public function saveExternalData(array $data)
    {
        if($data['userId'])
        {
            $stripeCustomer = $this->getStripeCustomer($data['stripeUserMapping']);
        }
        if($data['customerId'])
        {
            $stripeCustomer = $this->getStripeCustomer($data['stripeCustomerMapping']);
        }

        $stripeData = $this->createCreditCard(
            $data['creditCardYear'],
            $data['creditCardMonth'],
            $data['fingerprint'],
            $data['last4'],
            $data['cardholder'],
            $data['paymentGateway'],
            $data['company_name'],
            $stripeCustomer);

        return $stripeData;
    }

    /** Create a new Stripe refund transaction.
     *Return the external ID for the refund action or NULL there are exception
     *
     * @param integer $paymentMethddId
     * @param integer $refundedAmount
     * @param integer $paymentAmount
     * @param string  $currency
     * @param integer $paymentExternalId
     * @param string  $note
     * @return null|integer
     */
    public function refund($paymentMethodId, $refundedAmount, $paymentAmount, $currency, $paymentExternalId, $note)
    {
        $creditCard     = $this->creditCardRepository->read($paymentMethodId);
        $paymentGateway = $this->paymentGatewayRepository->read($creditCard['payment_gateway_id']);
        $this->stripeService->setApiKey(ConfigService::$stripeAPI[$paymentGateway['config']]['stripe_api_secret']);

        try
        {
            $stripeRefund = $this->stripeService->createRefund($refundedAmount, $paymentExternalId, $note);

            return $stripeRefund->id;
        }
        catch(InvalidRequest $exception)
        {
            return null;
        }
    }
}
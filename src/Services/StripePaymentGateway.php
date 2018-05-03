<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Stripe\Error\InvalidRequest;

class StripePaymentGateway
{
    /**
     * @var Stripe
     */
    private $stripeService;

    /**
     * @var \aymentRepository
     */
    private $paymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * StripePaymentGateway constructor.
     *
     * @param Stripe $stripeService
     */
    public function __construct(
        Stripe $stripeService,
        PaymentRepository $paymentRepository,
        PaymentGatewayRepository $paymentGatewayRepository
    ) {
        $this->stripeService            = $stripeService;
        $this->paymentRepository        = $paymentRepository;
        $this->paymentGatewayRepository = $paymentGatewayRepository;
    }

    public function chargePayment($due, $paid, $paymentMethod, $type, $currency)
    {
        $paymentData = [
            'due'               => $due,
            'type'              => $type,
            'payment_method_id' => $paymentMethod['id'],
            'external_provider' => ConfigService::$creditCard['external_provider'],
            'created_on'        => Carbon::now()->toDateTimeString()
        ];

        try
        {
            $paymentData['external_id'] = $this->chargeStripeCreditCardPayment($due, $paymentMethod);
            $paymentData['paid']        = $due;
            $paymentData['status']      = true;
            $paymentData['currency']    = $currency;
        }
        catch(Exception $e)
        {
            $paymentData['paid']    = 0;
            $paymentData['status']  = false;
            $paymentData['message'] = $e->getMessage();
        }

        return $paymentData;
    }

    /**
     * @param $due
     * @param $paymentMethod
     * @throws \Railroad\Ecommerce\ExternalHelpers\CardException
     */
    private function chargeStripeCreditCardPayment($due, $paymentMethod)
    {
        $paymentGateway = $this->paymentGatewayRepository->getById($paymentMethod['method']['payment_gateway_id']);

        $this->stripeService->setApiKey(ConfigService::$stripeAPI[$paymentGateway['config']]['stripe_api_secret']);

        $stripeCustomer = $this->stripeService->retrieveCustomer($paymentMethod['method']['external_customer_id']);
        try
        {
            $stripeCard = $this->stripeService->retrieveCard(
                $stripeCustomer,
                $paymentMethod['method']['external_id']
            );
        }
        catch(Exception $e)
        {
            throw new Payment(
                'Payment failed due to an internal error. Please contact support.', 4001
            );
        }

        try
        {
            $chargeResponse = $this->stripeService->createCharge(
                $due * 100,
                $stripeCustomer,
                $stripeCard,
                $paymentMethod['currency']
            );
        }
        catch(Card $cardException)
        {
            throw new PaymentFailedException('Payment failed. ' . $cardException->getMessage());
        }

        return $chargeResponse->id;
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
        $paymentGateway,
        $stripeCustomer
    ) {
        $this->stripeService->setApiKey(ConfigService::$stripeAPI[$paymentGateway['config']]['stripe_api_secret']);

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

        $stripeCard = $this->stripeService->createCard(
            $stripeCustomer,
            $token
        );

        return [
            'stripe_customer_id' => $stripeCustomer->id,
            'stripe_card_id'     => $stripeCard->id
        ];
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
     * @param array $data
     * @return array
     */
    public function handlingData(array $data)
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
            $stripeCustomer);

        return $stripeData;
    }
}
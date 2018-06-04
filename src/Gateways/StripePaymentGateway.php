<?php

namespace Railroad\Ecommerce\Gateways;

use Exception;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Services\ConfigService;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Refund;

class StripePaymentGateway
{
    /**
     * @var Stripe
     */
    protected $stripe;

    /**
     * StripePaymentGateway constructor.
     *
     * @param Stripe $stripe
     */
    public function __construct(Stripe $stripe)
    {
        $this->stripe = $stripe;
    }

    /**
     * @param $gatewayName
     * @param $customerEmail
     * @return Customer
     * @throws PaymentFailedException
     */
    public function getOrCreateCustomer($gatewayName, $customerEmail)
    {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if(empty($config))
        {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        $this->stripe->setApiKey($config['stripe_api_secret']);

        $customers = $this->stripe->getCustomersByEmail($customerEmail)['data'];

        if(empty($customers))
        {
            $customer = $this->stripe->createCustomer(['email' => $customerEmail]);
        }
        else
        {
            $customer = reset($customers);
        }

        return $customer;
    }

    /**
     * @param          $gatewayName
     * @param Customer $customer
     * @param          $tokenId
     * @return Card
     * @throws PaymentFailedException
     */
    public function createCustomerCard($gatewayName, Customer $customer, $tokenId)
    {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if(empty($config))
        {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        $this->stripe->setApiKey($config['stripe_api_secret']);

        $card = $this->stripe->createCard($customer, $this->stripe->retrieveToken($tokenId));

        return $card;
    }

    /**
     * @param          $gatewayName
     * @param          $amount
     * @param          $currency
     * @param Card     $card
     * @param Customer $customer
     * @param string   $description
     * @return Charge
     * @throws PaymentFailedException
     */
    public function chargeCustomerCard(
        $gatewayName,
        $amount,
        $currency,
        Card $card,
        Customer $customer,
        $description = ''
    ) {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if(empty($config))
        {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try
        {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $charge = $this->stripe->chargeCard(
                $amount * 100,
                $card,
                $customer,
                $currency,
                $description
            );
        }
        catch(Exception $exception)
        {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $charge;
    }

    /**
     * @param $amount
     * @param $externalPaymentId
     * @param $reason
     * @param $gatewayName
     * @return Refund
     * @throws PaymentFailedException
     */
    public function refund($gatewayName, $amount, $externalPaymentId, $reason = null)
    {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if(empty($config))
        {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        if(!in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer']))
        {
            $reason = null;
        }

        try
        {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $refund = $this->stripe->createRefund($amount * 100, $externalPaymentId, $reason);
        }
        catch(Exception $exception)
        {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $refund;
    }

    /**
     * @param $gatewayName
     * @param $customerId
     * @return \Stripe\Customer
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    public function getCustomer($gatewayName, $customerId)
    {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if(empty($config))
        {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try
        {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $customer = $this->stripe->retrieveCustomer($customerId);
        }
        catch(Exception $exception)
        {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $customer;
    }

    /**
     * @param \Stripe\Customer $customer
     * @param                  $cardId
     * @param                  $gatewayName
     * @return \Stripe\Card
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    public function getCard(Customer $customer, $cardId, $gatewayName)
    {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if(empty($config))
        {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try
        {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $card = $this->stripe->retrieveCard($customer, $cardId);
        }
        catch(Exception $exception)
        {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $card;
    }

    /**
     * @param      $gatewayName
     * @param      $number
     * @param      $expirationMonth
     * @param      $expirationYear
     * @param null $cvc
     * @param null $cardholderName
     * @param null $city
     * @param null $country
     * @param null $addressLineOne
     * @param null $addressLineTwo
     * @param null $state
     * @param null $zip
     * @return \Stripe\Token
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    public function createCardToken(
        $gatewayName,
        $number,
        $expirationMonth,
        $expirationYear,
        $cvc = null,
        $cardholderName = null,
        $city = null,
        $country = null,
        $addressLineOne = null,
        $addressLineTwo = null,
        $state = null,
        $zip = null
    ) {

        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if(empty($config))
        {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try
        {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $cardToken = $this->stripe->createCardToken($number,
                $expirationMonth,
                $expirationYear,
                $cvc,
                $cardholderName,
                $city,
                $country,
                $addressLineOne,
                $addressLineTwo,
                $state,
                $zip);
        }
        catch(Exception $exception)
        {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $cardToken;
    }
}
<?php

namespace Railroad\Ecommerce\Gateways;

use Exception;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\StripeObject;
use Stripe\Token;

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
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        $this->stripe->setApiKey($config['stripe_api_secret']);

        $customers = $this->stripe->getCustomersByEmail($customerEmail)['data'];

        if (empty($customers)) {
            $customer = $this->stripe->createCustomer(['email' => $customerEmail]);
        }
        else {
            $customer = reset($customers);
        }

        return $customer;
    }

    /**
     * @param $gatewayName
     * @param $customerEmail
     * @return Customer
     * @throws PaymentFailedException
     */
    public function createCustomer($gatewayName, $customerEmail)
    {
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        $this->stripe->setApiKey($config['stripe_api_secret']);

        return $this->stripe->createCustomer(['email' => $customerEmail]);
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
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        $this->stripe->setApiKey($config['stripe_api_secret']);

        $card = $this->stripe->createCard($customer, $this->stripe->retrieveToken($tokenId));

        return $card;
    }

    /**
     * @param string $gatewayName
     * @param Card $card
     * @param int $expirationMonth
     * @param int $expirationYear
     * @param string $addressCountry
     * @param string $addressRegion
     *
     * @return StripeObject
     *
     * @throws PaymentFailedException
     */
    public function updateCard(
        $gatewayName,
        Card $card,
        $expirationMonth,
        $expirationYear,
        $addressCountry,
        $addressRegion
    )
    {
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            $message = 'Gateway ' . $gatewayName . ' is not configured.';
            throw new PaymentFailedException($message);
        }

        $this->stripe->setApiKey($config['stripe_api_secret']);

        return $this->stripe->updateCard(
            $card,
            $expirationMonth,
            $expirationYear,
            $addressCountry,
            $addressRegion
        );
    }

    /**
     * @param          $gatewayName
     * @param          $amount
     * @param          $currency
     * @param Card $card
     * @param Customer $customer
     * @param string $description
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
    )
    {
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $charge = $this->stripe->chargeCard(
                $amount * 100,
                $card,
                $customer,
                $currency,
                $description
            );
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $charge;
    }

    /**
     * @param $amount
     * @param $externalPaymentId
     * @param $reason
     * @param $gatewayName
     * @return string
     * @throws PaymentFailedException
     */
    public function refund($gatewayName, $amount, $externalPaymentId, $reason = null)
    {
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        if (!in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'])) {
            $reason = null;
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $refund = $this->stripe->createRefund($amount, $externalPaymentId, $reason);
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $refund->id;
    }

    /**
     * @param $gatewayName
     * @param $customerId
     * @return Customer
     * @throws PaymentFailedException
     */
    public function getCustomer($gatewayName, $customerId)
    {
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $customer = $this->stripe->retrieveCustomer($customerId);
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $customer;
    }

    /**
     * @param Customer $customer
     * @param                  $cardId
     * @param                  $gatewayName
     * @return Card
     * @throws PaymentFailedException
     */
    public function getCard(Customer $customer, $cardId, $gatewayName)
    {
        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $card = $this->stripe->retrieveCard($customer, $cardId);
        } catch (Exception $exception) {
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
     * @param null $region
     * @param null $zip
     *
     * @return Token
     *
     * @throws PaymentFailedException
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
        $region = null,
        $zip = null
    )
    {

        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $cardToken = $this->stripe->createCardToken(
                $number,
                $expirationMonth,
                $expirationYear,
                $cvc,
                $cardholderName,
                $city,
                $country,
                $addressLineOne,
                $addressLineTwo,
                $region,
                $zip
            );
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $cardToken;
    }
}
<?php

namespace Railroad\Ecommerce\ExternalHelpers;

use Railroad\Ecommerce\Services\ConfigService;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Error\InvalidRequest;
use Stripe\Refund;
use Stripe\Source;
use Stripe\Token;

class Stripe
{
    private $stripe;

    public function __construct(StripeDependencies $stripe)
    {
        $this->stripe = $stripe;
    }

    /**
     * @param string      $number
     * @param integer     $expirationMonth
     * @param integer     $expirationYear
     * @param string|null $cvc
     * @param string|null $cardholderName
     * @param string|null $city
     * @param string|null $country
     * @param string|null $addressLineOne
     * @param string|null $addressLineTwo
     * @param string|null $state
     * @param string|null $zip
     * @return Token
     */
    public function createCardToken(
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
        return $this->stripe->token->create(
            [
                "card" => [
                    "number"          => $number,
                    "exp_month"       => $expirationMonth,
                    "exp_year"        => $expirationYear,
                    "cvc"             => $cvc,
                    "name"            => $cardholderName,
                    "address_city"    => $city,
                    "address_country" => $country,
                    "address_line1"   => $addressLineOne,
                    "address_line2"   => $addressLineTwo,
                    "address_state"   => $state,
                    "address_zip"     => $zip,
                ]
            ]
        );
    }

    /**
     * @param string $tokenString
     * @return Token
     */
    public function retrieveToken($tokenString)
    {
        return $this->stripe->token->retrieve($tokenString);
    }

    /**
     * @param array $attributes
     * @return Customer
     *
     * stripe.com/docs/api/php#customers
     */
    public function createCustomer($attributes = [])
    {
        return $this->stripe->customer->create($attributes);
    }

    /**
     * @param $id
     * @return Customer
     */
    public function retrieveCustomer($id)
    {
        return $this->stripe->customer->retrieve($id);
    }

    /**
     * @param Customer $customer
     * @param Token    $token
     * @return Card
     */
    public function createCard(Customer $customer, Token $token)
    {
        return $customer->sources->create(['source' => $token]);
    }

    /**
     * @param Customer $customer
     * @param string   $cardId
     * @return Card
     */
    public function retrieveCard(Customer $customer, $cardId)
    {
        return $this->stripe->card->retrieve($cardId);
    }

    /**
     * @param Customer $customer
     * @param string   $cardId
     * @param          $expirationMonth
     * @param          $expirationYear
     * @return \Stripe\ExternalAccount
     */
    public function updateCard(
        Customer $customer,
        $cardId,
        $expirationMonth,
        $expirationYear
    ) {
        $card = $this->retrieveCard(
            $customer,
            $cardId
        );

        $card->exp_month = $expirationMonth;
        $card->exp_year  = $expirationYear;

        return $card->save();
    }

    /**
     * @param integer $amount (in minimum possible currency) (cents)
     * @param $source
     * @param string $currency
     * @param string $description
     * @return \Stripe\Charge
     */
    public function createCharge(
        $amount,
        $source,
        $currency,
        $description = ''
    ) {
        return $this->stripe->charge->create(
            [
                'amount'   => $amount,
                'currency' => $currency,
                'source'   => $source,
                'description'   => $description,
            ]
        );
    }

    /**
     * @param string $id
     * @return Charge
     */
    public function retrieveCharge($id)
    {
        return $this->stripe->charge->retrieve($id);
    }

    /**
     * @param integer $amount (cents)
     * @param string  $chargeId
     * @param string  $reason (must be one of: duplicate, fraudulent, requested_by_customer
     * @return Refund
     */
    public function createRefund($amount, $chargeId, $reason = '')
    {
        return $this->stripe->refund->create(
            [
                'charge' => $chargeId,
                'amount' => $amount * 100,
                'reason' => $reason
            ]
        );
    }

    public function setApiKey($apiKey)
    {
        $this->stripe->stripe->setApiKey($apiKey);
    }
}
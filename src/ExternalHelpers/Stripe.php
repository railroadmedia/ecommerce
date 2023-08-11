<?php

namespace Railroad\Ecommerce\ExternalHelpers;

use Stripe\Card;
use Stripe\Charge;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Refund;
use Stripe\StripeObject;
use Stripe\Token;

class Stripe
{
    private $stripe;

    public function __construct(StripeDependencies $stripe)
    {
        $this->stripe = $stripe;

        \Stripe\Stripe::setApiVersion('2020-08-27');
    }

    /**
     * @param string $number
     * @param integer $expirationMonth
     * @param integer $expirationYear
     * @param string|null $cvc
     * @param string|null $cardholderName
     * @param string|null $city
     * @param string|null $country
     * @param string|null $addressLineOne
     * @param string|null $addressLineTwo
     * @param string|null $region
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
        $region = null,
        $zip = null
    ) {
        return $this->stripe->token->create(
            [
                "card" => [
                    "number" => $number,
                    "exp_month" => $expirationMonth,
                    "exp_year" => $expirationYear,
                    "cvc" => $cvc,
                    "name" => $cardholderName,
                    "address_city" => $city,
                    "address_country" => $country,
                    "address_line1" => $addressLineOne,
                    "address_line2" => $addressLineTwo,
                    "address_state" => $region,
                    "address_zip" => $zip,
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

    public function listCreditCards($id)
    {
        return $this->stripe->customer->allSources($id, ['limit' => 100]);
    }

    /**
     * @param $email
     *
     * @return Collection
     */
    public function getCustomersByEmail($email)
    {
        return $this->stripe->customer->all(['email' => $email]);
    }

    /**
     * @param Customer $customer
     * @param Token $token
     * @return array|Card|StripeObject
     */
    public function createCard(Customer $customer, Token $token)
    {
        return $this->stripe->customer->createSource($customer->id, ['source' => $token->id]);
    }

    /**
     * @param Customer $customer
     * @param string $cardId
     * @return array|Card|StripeObject
     */
    public function retrieveCard(Customer $customer, $cardId)
    {
        return $this->stripe->customer->retrieveSource($customer->id, $cardId);
    }

    /**
     * @param Card $card
     * @param int $expirationMonth
     * @param int $expirationYear
     * @param string $addressCountry
     * @param string $addressRegion
     * @return StripeObject
     */
    public function updateCard(
        Card $card,
        $expirationMonth,
        $expirationYear,
        $addressCountry,
        $addressRegion
    ) {
        $card->exp_month = $expirationMonth;
        $card->exp_year = $expirationYear;
        $card->address_country = $addressCountry;
        $card->address_state = $addressRegion;

        return $card->save();
    }

    /**
     * @param integer $amount (in minimum possible currency) (cents)
     * @param $card
     * @param $customer
     * @param string $currency
     * @param string $description
     *
     * @return Charge
     */
    public function chargeCard(
        $amount,
        Card $card,
        Customer $customer,
        $currency,
        $description = ''
    ) {
        return $this->stripe->charge->create(
            [
                'amount' => $amount,
                'currency' => $currency,
                'source' => $card,
                'description' => $description,
                'customer' => $customer,
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
     * @param string $chargeId
     * @param string $reason (must be one of: duplicate, fraudulent, requested_by_customer
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
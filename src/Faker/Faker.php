<?php

namespace Railroad\Ecommerce\Faker;

use Carbon\Carbon;
use Faker\Generator;
use Railroad\Ecommerce\Services\AddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Webpatser\Countries\Countries;

class Faker extends Generator
{
    public function product(array $override = [])
    {
        return array_merge(
            [
                'name'                        => $this->word,
                'sku'                         => $this->word,
                'price'                       => $this->numberBetween(1, 1000),
                'type'                        => $this->randomElement(
                    [
                        ProductService::TYPE_PRODUCT,
                        ProductService::TYPE_SUBSCRIPTION,
                    ]
                ),
                'active'                      => $this->randomElement([0, 1]),
                'description'                 => $this->text,
                'thumbnail_url'               => $this->imageUrl(),
                'is_physical'                 => $this->randomElement([0, 1]),
                'weight'                      => $this->numberBetween(0, 100),
                'subscription_interval_type'  => $this->randomElement(
                    [
                        SubscriptionService::INTERVAL_TYPE_YEARLY,
                        SubscriptionService::INTERVAL_TYPE_MONTHLY,
                        SubscriptionService::INTERVAL_TYPE_DAILY,
                    ]
                ),
                'subscription_interval_count' => $this->numberBetween(0, 12),
                'stock'                       => $this->numberBetween(1, 1000),
                'brand'                       => ConfigService::$brand,
                'created_on'                  => Carbon::now()->toDateTimeString(),
            ],
            $override
        );
    }

    public function customer(array $override = [])
    {
        return array_merge(
            [
                'phone'      => $this->phoneNumber,
                'email'      => $this->email,
                'brand'      => ConfigService::$brand,
                'created_on' => Carbon::now()->toDateTimeString(),
            ],
            $override
        );
    }

    public function address(array $override = [])
    {
        return array_merge(
            [
                'type'          => $this->randomElement(
                    [
                        AddressService::BILLING_ADDRESS,
                        AddressService::SHIPPING_ADDRESS,
                    ]
                ),
                'brand'         => ConfigService::$brand,
                'user_id'       => rand(),
                'customer_id'   => null,
                'first_name'    => $this->firstName,
                'last_name'     => $this->lastName,
                'street_line_1' => $this->streetAddress,
                'street_line_2' => null,
                'city'          => $this->city,
                'zip'           => $this->postcode,
                'state'         => $this->word,
                'country'       => $this->randomElement(array_column(Countries::getCountries(), 'full_name')),
                'created_on'    => Carbon::now()->toDateTimeString(),
            ],
            $override
        );
    }

    public function shippingOption(array $override = [])
    {
        return array_merge(
            [
                'country'    => $this->country,
                'active'     => $this->randomNumber(),
                'priority'   => $this->boolean,
                'created_on' => Carbon::now()->toDateTimeString(),
            ],
            $override
        );
    }

    public function shippingCost(array $override = [])
    {
        return array_merge(
            [
                'shipping_option_id' => $this->randomNumber(),
                'min'                => $this->randomNumber(),
                'max'                => $this->randomNumber(),
                'price'              => $this->randomNumber(),
                'created_on'         => Carbon::now()->toDateTimeString(),
            ],
            $override
        );
    }

    public function payment(array $override = [])
    {
        return array_merge(
            [
                'due'               => $this->randomNumber(),
                'paid'              => $this->randomNumber(),
                'refunded'          => $this->randomNumber(),
                'type'              => $this->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]),
                'external_provider' => $this->word,
                'external_id'       => $this->word,
                'status'            => 1,
                'message'           => null,
                'payment_method_id' => $this->randomNumber(),
                'currency'          => $this->currencyCode,
                'created_on'        => Carbon::now()->toDateTimeString()
            ], $override
        );
    }

    public function paymentMethod(array $override = [])
    {
        return array_merge(
            [
                'method_id'   => $this->randomNumber(),
                'method_type' => $this->word,
                'currency'    => $this->currencyCode,
                'created_on'  => Carbon::now()->toDateTimeString()
            ], $override
        );
    }

    public function creditCard(array $override = [])
    {
        return array_merge(
            [
                'fingerprint'          => '4242424242424242',
                'last_four_digits'     => $this->randomNumber(4),
                'cardholder_name'      => $this->name,
                'company_name'         => $this->creditCardType,
                'external_id'          => 'card_1CT9rUE2yPYKc9YRHSwdADbH',
                'external_customer_id' => 'cus_CsviON4xYQxcwC',
                'expiration_date'      => $this->creditCardExpirationDateString,
                'payment_gateway_name'   => $this->randomElement(['drumeo','recordeo']),
                'created_on'           => Carbon::now()->toDateTimeString()
            ], $override
        );
    }

    public function paymentGateway(array $override = [])
    {
        return array_merge(
            [
                'brand'      => ConfigService::$brand,
                'type'       => $this->word,
                'name'       => $this->word,
                'config'     => $this->word,
                'created_on' => Carbon::now()->toDateTimeString()
            ], $override
        );
    }

    public function userPaymentMethod(array $override = [])
    {
        return array_merge(
            [
                'user_id'           => $this->randomNumber(),
                'payment_method_id' => $this->randomNumber(),
                'is_primary'        => $this->boolean,
                'created_on'        => Carbon::now()->toDateTimeString()
            ], $override);
    }

    public function paypalBillingAgreement(array $override = [])
    {
        return array_merge(
            [
                'agreement_id'           => 'B-5Y6562572W918445E',
                'express_checkout_token' => 'EC-73P77133DA956953G',
                'address_id'             => $this->randomNumber(),
                'payment_gateway_name'     => $this->randomElement(['stripe','paypal']),
                'expiration_date'        => $this->creditCardExpirationDateString,
                'created_on'             => Carbon::now()->toDateTimeString()
            ], $override
        );
    }

    public function userStripeCustomer(array $override = [])
    {
        return array_merge(
            [
                'user_id'            => $this->randomNumber(),
                'stripe_customer_id' => 'cus_CsviON4xYQxcwC',
                'created_on'         => Carbon::now()->toDateTimeString()
            ], $override
        );
    }
}
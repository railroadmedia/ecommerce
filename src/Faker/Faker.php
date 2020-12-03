<?php

namespace Railroad\Ecommerce\Faker;

use Carbon\Carbon;
use Faker\Generator;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Location\Services\LocationService;

class Faker extends Generator
{
    public function accessCode(array $override = [])
    {
        $claimed = $this->boolean;

        return array_merge(
            [
                'code' => $this->shuffleString($this->bothify('???###???###???###???###')),
                'product_ids' => [$this->randomNumber(), $this->randomNumber(), $this->randomNumber()],
                'is_claimed' => $claimed,
                'claimer_id' => null,
                'claimed_on' => $claimed ?
                    Carbon::now()
                        ->toDateTimeString() : null,
                'brand' => config('ecommerce.brand'),
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function product(array $override = [])
    {
        return array_merge(
            [
                'name' => $this->word,
                'sku' => $this->word . rand(100000, 10000000),
                'fulfillment_sku' => $this->word . rand(100000, 10000000),
                'price' => $this->numberBetween(1, 1000),
                'type' => $this->randomElement(
                    [
                        Product::TYPE_DIGITAL_ONE_TIME,
                        Product::TYPE_DIGITAL_SUBSCRIPTION,
                        Product::TYPE_PHYSICAL_ONE_TIME,
                    ]
                ),
                'active' => $this->randomElement([0, 1]),
                'category' => $this->word,
                'description' => $this->text,
                'thumbnail_url' => $this->imageUrl(),
                'sales_page_url' => $this->url,
                'is_physical' => $this->randomElement([0, 1]),
                'weight' => $this->numberBetween(0, 100),
                'subscription_interval_type' => $this->randomElement(
                    [
                        config('ecommerce.interval_type_daily'),
                        config('ecommerce.interval_type_monthly'),
                        config('ecommerce.interval_type_yearly'),
                    ]
                ),
                'subscription_interval_count' => $this->numberBetween(0, 12),
                'stock' => $this->numberBetween(100, 1000),
                'auto_decrement_stock' => $this->randomElement([0, 1]),
                'brand' => config('ecommerce.brand'),
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function customer(array $override = [])
    {
        return array_merge(
            [
                'phone' => $this->phoneNumber,
                'email' => $this->email,
                'brand' => config('ecommerce.brand'),
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function address(array $override = [])
    {
        return array_merge(
            [
                'type' => $this->randomElement(
                    [
                        Address::BILLING_ADDRESS_TYPE,
                        Address::SHIPPING_ADDRESS_TYPE,
                    ]
                ),
                'brand' => config('ecommerce.brand'),
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'street_line_1' => $this->streetAddress,
                'street_line_2' => null,
                'city' => $this->city,
                'zip' => $this->postcode,
                'region' => $this->word,
                'country' => $this->randomElement(LocationService::countries()),
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
                'updated_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function membershipAction(array $override = [])
    {
        return array_merge(
            [
                'action' => $this->word,
                'action_amount' => $this->randomNumber(),
                'action_reason' => $this->words(3, true),
                'user_id' => $this->randomNumber(),
                'subscription_id' => $this->randomNumber(),
                'brand' => config('ecommerce.brand'),
                'note' => $this->words(3, true),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
                'updated_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function shippingOption(array $override = [])
    {
        return array_merge(
            [
                'country' => $this->country,
                'active' => $this->boolean,
                'priority' => $this->randomNumber(),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function shippingCost(array $override = [])
    {
        return array_merge(
            [
                'shipping_option_id' => $this->randomNumber(),
                'min' => $this->numberBetween(0, 100),
                'max' => $this->numberBetween(101, 200),
                'price' => $this->randomNumber(),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function payment(array $override = [])
    {
        return array_merge(
            [
                'total_due' => $this->randomNumber(),
                'total_paid' => $this->randomNumber(),
                'total_refunded' => $this->randomNumber(),
                'attempt_number' => 0,
                'conversion_rate' => $this->randomNumber(2),
                'type' => $this->randomElement(
                    [config('ecommerce.order_payment_type'), config('ecommerce.renewal_payment_type')]
                ),
                'external_provider' => $this->word,
                'external_id' => $this->word,
                'gateway_name' => $this->word,
                'status' => 1,
                'message' => null,
                'payment_method_id' => $this->randomNumber(),
                'currency' => $this->currencyCode,
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function paymentMethod(array $override = [])
    {
        return array_merge(
            [
                'currency' => $this->currencyCode,
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function creditCard(array $override = [])
    {
        return array_merge(
            [
                'fingerprint' => '4242424242424242',
                'last_four_digits' => $this->randomNumber(4),
                'cardholder_name' => $this->name,
                'company_name' => $this->creditCardType,
                'external_id' => 'card_1CT9rUE2yPYKc9YRHSwdADbH',
                'external_customer_id' => 'cus_CsviON4xYQxcwC',
                'expiration_date' => $this->creditCardExpirationDateString,
                'payment_gateway_name' => $this->randomElement(['drumeo', 'recordeo']),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function userPaymentMethod(array $override = [])
    {
        return array_merge(
            [
                'user_id' => $this->randomNumber(),
                'payment_method_id' => $this->randomNumber(),
                'is_primary' => $this->boolean,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function customerPaymentMethod(array $override = [])
    {
        return array_merge(
            [
                'customer_id' => $this->randomNumber(),
                'payment_method_id' => $this->randomNumber(),
                'is_primary' => $this->boolean,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function paypalBillingAgreement(array $override = [])
    {
        return array_merge(
            [
                'external_id' => 'B-5Y6562572W918445E',
                'payment_gateway_name' => $this->randomElement(['drumeo', 'recordeo']),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function userStripeCustomer(array $override = [])
    {
        return array_merge(
            [
                'user_id' => $this->randomNumber(),
                'stripe_customer_id' => 'cus_CsviON4xYQxcwC',
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function discount(array $override = [])
    {
        return array_merge(
            [
                'name' => $this->word(),
                'description' => $this->text,
                'type' => $this->word,
                'amount' => $this->randomNumber(2),
                'product_id' => null,
                'active' => $this->boolean,
                'visible' => $this->boolean,
                'expiration_date' => null,
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function discountCriteria(array $override = [])
    {
        return array_merge(
            [
                'name' => $this->word(),
                'type' => $this->word,
                'min' => $this->randomNumber(1),
                'max' => $this->randomNumber(2),
                'discount_id' => $this->randomNumber(1),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function order(array $override = [])
    {
        return array_merge(
            [
                'total_due' => $this->randomNumber(),
                'product_due' => $this->randomNumber(),
                'taxes_due' => $this->randomNumber(),
                'shipping_due' => $this->randomNumber(),
                'finance_due' => $this->randomNumber(),
                'total_paid' => $this->randomNumber(),
                'user_id' => $this->randomNumber(),
                'customer_id' => null,
                'brand' => config('ecommerce.brand'),
                'shipping_address_id' => null,
                'billing_address_id' => null,
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function orderItem(array $override = [])
    {
        return array_merge(
            [
                'order_id' => $this->randomNumber(),
                'product_id' => $this->randomNumber(),
                'quantity' => $this->randomNumber(),
                'weight' => $this->randomNumber(),
                'initial_price' => $this->randomNumber(),
                'total_discounted' => $this->randomNumber(),
                'final_price' => $this->randomNumber(),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function subscription(array $override = [])
    {
        return array_merge(
            [
                'brand' => config('ecommerce.brand'),
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'user_id' => $this->randomNumber(),
                'customer_id' => null,
                'order_id' => null,
                'product_id' => $this->randomNumber(),
                'is_active' => 1,
                'stopped' => 0,
                'start_date' => Carbon::now()
                    ->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->toDateTimeString(),
                'canceled_on' => null,
                'note' => $this->text,
                'total_price' => $this->randomNumber(3),
                'tax' => 0,
                'currency' => 'USD',
                'interval_type' => 'year',
                'interval_count' => $this->randomNumber(),
                'total_cycles_due' => $this->randomNumber(),
                'total_cycles_paid' => $this->randomNumber(),
                'renewal_attempt' => 0,
                'payment_method_id' => $this->randomNumber(),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
                'updated_at' => Carbon::now()
                    ->toDateTimeString(),
                'deleted_at' => null,
                'cancellation_reason' => null,
            ],
            $override
        );
    }

    public function orderItemFulfillment(array $override = [])
    {
        return array_merge(
            [
                'order_id' => $this->randomNumber(),
                'order_item_id' => $this->randomNumber(),
                'status' => config('ecommerce.fulfillment_status_pending'),
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => $this->text,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }

    public function userProduct(array $override = [])
    {
        return array_merge(
            [
                'user_id' => $this->randomNumber(),
                'product_id' => $this->randomNumber(),
                'quantity' => $this->numberBetween(1, 5),
                'start_date' => Carbon::now()->subDays(10)->toDateTimeString(),
                'expiration_date' => Carbon::now()->addDays(10)->toDateTimeString(),
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $override
        );
    }
}
<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaypalWebhookControllerTest extends EcommerceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_process_renewal()
    {
        $recurringPaymentId = 'I-' . $this->faker->word . $this->faker->word; // subscription external id
        $txnId = $this->faker->word . $this->faker->word; // transaction/payment external id
        $price = 128.95;

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'price' => $price,
        ]);

        $user = $this->fakeUser();

        $subscription = $this->fakeSubscription([
            'type' => Subscription::TYPE_PAYPAL_SUBSCRIPTION,
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $user['id'],
            'paid_until' => Carbon::now()
                        ->subDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'total_price' => $product['price'],
            'tax' => 0,
            'paypal_recurring_profile_id' => $recurringPaymentId,
        ]);

        $results = $this->call(
            'POST',
            '/paypal/webhook/1',
            [
                'txn_type' => 'recurring_payment',
                'payment_status' => 'Completed',
                'recurring_payment_id' => $recurringPaymentId,
                'txn_id' => $txnId,
                'payment_gross' => $price,
                'payment_date' => Carbon::now()->format('H:i:s M d, Y e'),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            array_merge(
                array_diff_key(
                    $subscription,
                    ['updated_at' => true]
                ),
                [
                    'paid_until' => Carbon::now()
                        ->addYear(1)
                        ->startOfDay()
                        ->toDateTimeString(),
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                    'total_cycles_paid' => $subscription['total_cycles_paid'] + 1
                ]
            )
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user['id'],
                'product_id' => $product['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry'))
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $price,
                'total_paid' => $price,
                'total_refunded' => 0,
                'type' => Payment::TYPE_PAYPAL_SUBSCRIPTION_RENEWAL,
                'payment_method_id' => null,
                'external_id' => $txnId,
                'external_provider' => Payment::EXTERNAL_PROVIDER_PAYPAL,
                'currency' => '',
                'conversion_rate' => 1,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }
}

<?php

namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use ReceiptValidator\iTunes\SandboxResponse;

class ProcessAppleExpiredSubscriptionsTest extends EcommerceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_command()
    {
        // lets do:
        // 1 successful renewal
        // 1 deactivation/expired renewal

        $user1 = $this->fakeUser();
        $user2 = $this->fakeUser();

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'price' => 12.95,
                'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
                'subscription_interval_count' => 1,
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product['sku'],
            ]
        );

        // to be successfully renewed
        $webOrderLineItemId1 = $this->faker->word;
        $appleReceipt1 = $this->faker->word;

        $subscription1 = $this->fakeSubscription(
            [
                'user_id' => $user1['id'],
                'type' => Subscription::TYPE_APPLE_SUBSCRIPTION,
                'start_date' => Carbon::now()
                    ->subYear(2),
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'apple_expiration_date' => Carbon::now()
                    ->subDay(1),
                'is_active' => true,
                'canceled_on' => null,
                'product_id' => $product['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'total_price' => $product['price'],
                'external_app_store_id' => $webOrderLineItemId1
            ]
        );

        $appleReceipt1 = $this->fakeAppleReceipt(
            [
                'subscription_id' => $subscription1['id'],
                'receipt' => $appleReceipt1,
                'request_type' => AppleReceipt::MOBILE_APP_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
            ]
        );

        // deactivation renewal
        $webOrderLineItemId2 = $this->faker->word;
        $appleReceipt2 = $this->faker->word;

        $subscription2 = $this->fakeSubscription(
            [
                'user_id' => $user2['id'],
                'type' => Subscription::TYPE_APPLE_SUBSCRIPTION,
                'start_date' => Carbon::now(),
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'apple_expiration_date' => Carbon::now()
                    ->subDays(7),
                'is_active' => true,
                'canceled_on' => null,
                'product_id' => $product['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'total_price' => $product['price'],
                'external_app_store_id' => $webOrderLineItemId2
            ]
        );

        $appleReceipt2 = $this->fakeAppleReceipt(
            [
                'subscription_id' => $subscription2['id'],
                'receipt' => $appleReceipt2,
                'request_type' => AppleReceipt::MOBILE_APP_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
            ]
        );

        $appleProductsMap = array_flip(config('ecommerce.apple_store_products_map'));

        // -------------------------------------------------------------------------
        // first validation response, successful renewal
        $originalTransactionId1 = $this->faker->word;
        $renewalTransactionId1 = $this->faker->word;
        $expirationDate1 = Carbon::now()->addDays(31);

        $originalPurchaseItemArray1 = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$product['sku']],
            'expires_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'transaction_id' => $originalTransactionId1,
            'original_transaction_id' => $originalTransactionId1,
            'web_order_line_item_id' => $webOrderLineItemId1,
            'purchase_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'original_purchase_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'is_trial_period' => true,
        ];

        $renewalPurchaseItemArray1 = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$product['sku']],
            'expires_date_ms' => $expirationDate1->timestamp * 1000,
            'transaction_id' => $renewalTransactionId1,
            'original_transaction_id' => $originalTransactionId1,
            'web_order_line_item_id' => $this->faker->word,
            'purchase_date_ms' => Carbon::now()->timestamp * 1000,
            'original_purchase_date_ms' => Carbon::now()->timestamp * 1000,
            'is_trial_period' => false,
        ];

        $rawData1 = [
            'status' => 0,
            'environment' => 'Sandbox',
            'latest_receipt_info' => [$renewalPurchaseItemArray1, $originalPurchaseItemArray1],
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
                'in_app' => [$renewalPurchaseItemArray1, $originalPurchaseItemArray1],
            ],
        ];

        $validationResponse1 = new SandboxResponse($rawData1);

        // -------------------------------------------------------------------------
        // second validation response, subscription is deactivated/expired
        $originalTransactionId2 = $this->faker->word;
        $renewalTransactionId2 = $this->faker->word;
        $expirationDate2 = Carbon::now()->subDays(3);
        $expirationIntentNumber2 = 1;

        $originalPurchaseItemArray2 = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$product['sku']],
            'expires_date_ms' => $expirationDate2->timestamp * 1000,
            'transaction_id' => $originalTransactionId2,
            'original_transaction_id' => $originalTransactionId2,
            'web_order_line_item_id' => $webOrderLineItemId2,
            'purchase_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'original_purchase_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'is_trial_period' => true,
        ];

        $pendingRenewalInfoArray2 = [
            [
                'product_id' => 'drumeo_app_monthly_member',
                'auto_renew_product_id' => 'drumeo_app_monthly_member',
                'original_transaction_id' => $originalTransactionId2,
                'auto_renew_status' => false,
                'expiration_intent' => $expirationIntentNumber2,
                'is_in_billing_retry_period' => false,
            ]
        ];

        $rawData2 = [
            'status' => 0,
            'environment' => 'Sandbox',
            'latest_receipt_info' => [$originalPurchaseItemArray2],
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
                'in_app' => [$originalPurchaseItemArray2],
            ],
            'pending_renewal_info' => $pendingRenewalInfoArray2
        ];

        $validationResponse2 = new SandboxResponse($rawData2);

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse1, $validationResponse2);

        $this->artisan('ProcessAppleExpiredSubscriptions');

        // assert ancient subscription was renewed
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription1['id'],
                'user_id' => $user1['id'],
                'product_id' => $product['id'],
                "type" => "apple_subscription",
                'is_active' => 1,
                'canceled_on' => null,
                'paid_until' => $expirationDate1->toDateTimeString(),
                'apple_expiration_date' => $expirationDate1->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user1['id'],
                'product_id' => $product['id'],
                'expiration_date' => $expirationDate1
                    ->copy()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 1,
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'external_id' => $renewalTransactionId1,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );

        // assert deactivated subscription was not renewed
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription2['id'],
                'user_id' => $user2['id'],
                'product_id' => $product['id'],
                "type" => "apple_subscription",
                'is_active' => 0,
                'canceled_on' => null,
                'paid_until' => $expirationDate2->toDateTimeString(),
                'apple_expiration_date' => $expirationDate2->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user2['id'],
                'product_id' => $product['id'],
                'expiration_date' => $expirationDate2
                    ->copy()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'id' => 2,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'external_id' => $renewalTransactionId2,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 2,
                'payment_id' => 2,
            ]
        );
    }
}

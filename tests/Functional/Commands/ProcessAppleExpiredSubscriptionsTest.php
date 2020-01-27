<?php

namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use ReceiptValidator\iTunes\SandboxResponse;

class ProcessAppleExpiredSubscriptionsTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_command()
    {
        $userOne = $this->fakeUser();

        $productOne = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        // inactive ancient subscription
        $subscriptionOne = $this->fakeSubscription(
            [
                'user_id' => $userOne['id'],
                'type' => Subscription::TYPE_APPLE_SUBSCRIPTION,
                'start_date' => Carbon::now()
                    ->subYear(2),
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'apple_expiration_date' => Carbon::now()
                    ->subDays(7),
                'is_active' => false,
                'canceled_on' => Carbon::now()
                    ->subDays(7),
                'product_id' => $productOne['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'total_price' => $productOne['price'],
            ]
        );

        $appleReceiptOneText = $this->faker->word . $this->faker->word;
        $appleReceiptOne = $this->fakeAppleReceipt(
            [
                'subscription_id' => $subscriptionOne['id'],
                'receipt' => $appleReceiptOneText,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
            ]
        );

        $userTwo = $this->fakeUser();

        $productTwo = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        // active subscription with expiration date in the past
        $subscriptionTwo = $this->fakeSubscription(
            [
                'user_id' => $userTwo['id'],
                'type' => Subscription::TYPE_APPLE_SUBSCRIPTION,
                'start_date' => Carbon::now()
                    ->subYear(2),
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'apple_expiration_date' => Carbon::now()
                    ->subDay(1),
                'product_id' => $productTwo['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'total_price' => $productTwo['price'],
            ]
        );

        $appleReceiptTwoText = $this->faker->word . $this->faker->word;
        $appleReceiptTwo = $this->fakeAppleReceipt(
            [
                'subscription_id' => $subscriptionTwo['id'],
                'receipt' => $appleReceiptTwoText,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
            ]
        );

        $userThree = $this->fakeUser();

        $productThree = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        // active subscription with expiration date in future
        $subscriptionThree = $this->fakeSubscription(
            [
                'user_id' => $userThree['id'],
                'type' => Subscription::TYPE_APPLE_SUBSCRIPTION,
                'start_date' => Carbon::now()
                    ->subYear(2),
                'paid_until' => Carbon::now()
                    ->addDay(1),
                'apple_expiration_date' => Carbon::now()
                    ->addDay(1),
                'product_id' => $productThree['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'total_price' => $productThree['price'],
            ]
        );

        $appleReceiptThreeText = $this->faker->word . $this->faker->word;
        $appleReceiptThree = $this->fakeAppleReceipt(
            [
                'subscription_id' => $subscriptionThree['id'],
                'receipt' => $appleReceiptThreeText,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
            ]
        );

        $this->fakeUserProduct(
            [
                'user_id' => $userThree['id'],
                'product_id' => $productThree['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addDay(1),
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $productOne['sku'],
                $this->faker->word => $productTwo['sku'],
                $this->faker->word => $productThree['sku'],
            ]
        );

        $webOrderLineItemId = $this->faker->word;

        $productsData = [
            $productTwo['sku'] => [
                'web_order_line_item_id' => $webOrderLineItemId,
            ],
        ];

        $validationResponse = $this->getReceiptValidationResponse($productsData);

        $this->appleStoreKitGatewayMock->method('validate')
            ->willReturn($validationResponse);

        $this->artisan('ProcessAppleExpiredSubscriptions');

        // assert ancient subscription was not renewed
        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            [
                'id' => $subscriptionOne['id'],
                'user_id' => $userOne['id'],
                'product_id' => $productOne['id'],
                'is_active' => 1,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_user_products',
            [
                'user_id' => $userOne['id'],
                'product_id' => $productOne['id'],
            ]
        );

        // assert expired subscription was renewed
        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $appleReceiptTwoText,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
                'valid' => 1,
                'validation_error' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $productTwo['price'],
                'total_paid' => $productTwo['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => $userTwo['id'],
                'product_id' => $productTwo['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addMonth()
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userTwo['id'],
                'product_id' => $productTwo['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addMonth()
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $subscriptionTwo['brand'],
                'resource_name' => Payment::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => ActionLogService::ACTOR_COMMAND,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_COMMAND,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $subscriptionTwo['brand'],
                'resource_name' => Subscription::class,
                'resource_id' => $subscriptionTwo['id'],
                'action_name' => Subscription::ACTION_RENEW,
                'actor' => ActionLogService::ACTOR_COMMAND,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_COMMAND,
            ]
        );

        // assert non-expired subscription not modified
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscriptionThree['id'],
                'user_id' => $userThree['id'],
                'product_id' => $productThree['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addDay(1),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userThree['id'],
                'product_id' => $productThree['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addDay(1),
            ]
        );
    }

    protected function getReceiptValidationResponse(
        $productsData,
        $receiptCreationDate = null,
        $receiptStatus = 0
    )
    {
        /*
        // $productsData structure example
        $productsData = [
            $someProduct->getSku() => [
                'quantity' => 1,
                'expires_date_ms' => Carbon::now()->addMonth(),
                'web_order_line_item_id' => $this->faker->word,
                'product_id' => key of config('ecommerce.apple_store_products_map'),
            ]
        ];
        */

        $appleProductsMap = array_flip(config('ecommerce.apple_store_products_map'));

        if (!$receiptCreationDate) {
            $receiptCreationDate = Carbon::now();
        }

        $rawData = [
            'status' => $receiptStatus,
            'environment' => 'Sandbox',
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => $receiptCreationDate->tz('UTC')->getTimestamp() * 1000,
                'in_app' => []
            ]
        ];

        $defaultItemData = [
            'quantity' => 1,
            'expires_date_ms' => $receiptCreationDate->addMonth(),
            'web_order_line_item_id' => $this->faker->word
        ];

        foreach ($productsData as $productSku => $purchaseItemData) {

            $purchaseItemData = array_merge($defaultItemData, $purchaseItemData);

            $purchaseItemData['product_id'] = $appleProductsMap[$productSku];
            $purchaseItemData['expires_date_ms'] = $purchaseItemData['expires_date_ms']->tz('UTC')->getTimestamp() * 1000;

            $rawData['receipt']['in_app'][] = $purchaseItemData;
        }

        return new SandboxResponse($rawData);
    }
}

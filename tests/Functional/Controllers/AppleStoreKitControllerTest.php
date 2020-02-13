<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use Railroad\Ecommerce\Mail\SubscriptionInvoice;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use ReceiptValidator\iTunes\SandboxResponse;

class AppleStoreKitControllerTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var MockObject|AuthManager
     */
    protected $authManagerMock;

    /**
     * @var MockObject|SessionGuard
     */
    protected $sessionGuardMock;

    protected function setUp()
    {
        parent::setUp();

        $this->authManagerMock =
            $this->getMockBuilder(AuthManager::class)
                ->disableOriginalConstructor()
                ->setMethods(['guard'])
                ->getMock();

        $this->sessionGuardMock =
            $this->getMockBuilder(SessionGuard::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->authManagerMock->method('guard')
            ->willReturn($this->sessionGuardMock);

        $this->app->instance(Factory::class, $this->authManagerMock);

        $this->sessionGuardMock->method('loginUsingId')
            ->willReturn(true);
    }

    public function test_process_receipt_validation()
    {
        $response = $this->call('POST', '/apple/verify-receipt-and-process-payment', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals(
            [
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.attributes.receipt',
                    'detail' => 'The receipt field is required.',
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.attributes.email',
                    'detail' => 'The email field is required.',
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.attributes.password',
                    'detail' => 'The password field is required.',
                ]
            ],
            $response->decodeResponseJson('errors')
        );
    }

    public function test_process_receipt_initial_trial_purchase()
    {
        $receipt = $this->faker->word;
        $transactionId = $this->faker->word;
        $webOrderItemId = $this->faker->word;
        $subscriptionExpirationDate = Carbon::now()->addDays(7);
        $email = $this->faker->email;
        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);

        $productOne = $this->fakeProduct(
            [
                'sku' => 'product-one',
                'price' => 12.95,
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
                'subscription_interval_count' => 1,
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $productOne['sku'],
            ]
        );

        $validationResponse =
            $this->getInitialPurchaseReceiptResponse(
                $transactionId,
                $webOrderItemId,
                $productOne['sku'],
                $subscriptionExpirationDate,
                true
            );

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/verify-receipt-and-process-payment',
            [
                'data' => [
                    'attributes' => [
                        'receipt' => $receipt,
                        'email' => $email,
                        'password' => $this->faker->word,
                    ]
                ]
            ]
        );

        // assert the response status code
        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = $response->decodeResponseJson();

        // assert response has meta key with auth code
        $this->assertTrue(isset($decodedResponse['meta']['auth_code']));

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $receipt,
                'request_type' => AppleReceipt::MOBILE_APP_REQUEST_TYPE,
                'email' => $email,
                'valid' => true,
                'validation_error' => null,
                'transaction_id' => $transactionId,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // we dont want order rows
        $this->assertDatabaseMissing(
            'ecommerce_orders',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $productOne['id'],
                'is_active' => 1,
                'paid_until' => $subscriptionExpirationDate->toDateTimeString(),
                'external_app_store_id' => $webOrderItemId,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $productOne['id'],
                'quantity' => 1,
                'expiration_date' => $subscriptionExpirationDate
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );
    }

    public function test_process_receipt_initial_non_trial_purchase()
    {
        $receipt = $this->faker->word;
        $transactionId = $this->faker->word;
        $webOrderItemId = $this->faker->word;
        $subscriptionExpirationDate = Carbon::now()->addDays(7);
        $email = $this->faker->email;
        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);

        $productOne = $this->fakeProduct(
            [
                'sku' => 'product-one',
                'price' => 12.95,
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
                'subscription_interval_count' => 1,
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $productOne['sku'],
            ]
        );

        $validationResponse =
            $this->getInitialPurchaseReceiptResponse(
                $transactionId,
                $webOrderItemId,
                $productOne['sku'],
                $subscriptionExpirationDate,
                false
            );

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/verify-receipt-and-process-payment',
            [
                'data' => [
                    'attributes' => [
                        'receipt' => $receipt,
                        'email' => $email,
                        'password' => $this->faker->word,
                    ]
                ]
            ]
        );

        // assert the response status code
        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = $response->decodeResponseJson();

        // assert response has meta key with auth code
        $this->assertTrue(isset($decodedResponse['meta']['auth_code']));

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $receipt,
                'request_type' => AppleReceipt::MOBILE_APP_REQUEST_TYPE,
                'email' => $email,
                'valid' => true,
                'validation_error' => null,
                'transaction_id' => $transactionId,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // we dont want order rows
        $this->assertDatabaseMissing(
            'ecommerce_orders',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $productOne['price'],
                'total_paid' => $productOne['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $productOne['id'],
                'is_active' => 1,
                'paid_until' => $subscriptionExpirationDate->toDateTimeString(),
                'external_app_store_id' => $webOrderItemId,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $productOne['id'],
                'quantity' => 1,
                'expiration_date' => $subscriptionExpirationDate
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );
    }

    public function test_process_receipt_validation_exception()
    {
        $receipt = $this->faker->word;
        $transactionId = $this->faker->word;
        $webOrderItemId = $this->faker->word;
        $subscriptionExpirationDate = Carbon::now()->addDays(7);
        $email = $this->faker->email;
        $exceptionMessage = $this->faker->word;

        $appleStoreKitGateway =
            $this->getMockBuilder(AppleStoreKitGateway::class)
                ->disableOriginalConstructor()
                ->getMock();

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => 'test1',
                $this->faker->word => 'test2',
            ]
        );

        $validationResponse = $this->getInitialPurchaseReceiptResponse(
            $transactionId,
            $webOrderItemId,
            'test1',
            $subscriptionExpirationDate,
            false
        );

        $appleStoreKitGateway->method('getResponse')
            ->willThrowException(new ReceiptValidationException($exceptionMessage, null, $validationResponse));

        $this->app->instance(AppleStoreKitGateway::class, $appleStoreKitGateway);

        $response = $this->call(
            'POST',
            '/apple/verify-receipt-and-process-payment',
            [
                'data' => [
                    'attributes' => [
                        'receipt' => $receipt,
                        'email' => $email,
                        'password' => $this->faker->word,
                    ]
                ]
            ]
        );

        $this->assertEquals(
            [
                [
                    'title' => 'Receipt validation failed.',
                    'detail' => $exceptionMessage,
                ],
            ],
            $response->decodeResponseJson('errors')
        );

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $receipt,
                'email' => $email,
                'valid' => false,
                'validation_error' => $exceptionMessage,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function test_process_notification_subscription_renewal()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        Mail::fake();

        $email = $this->faker->email;

        $userId = $this->createAndLogInNewUser($email);
        $receipt = $this->faker->word;

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'price' => 12.95,
                'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
                'subscription_interval_count' => 1,
            ]
        );

        $originalWebOrderLineItemId = $this->faker->word;
        $renewalWebOrderLineItemId = $this->faker->word;
        $originalTransactionId = $this->faker->word;
        $renewalTransactionId = $this->faker->word;
        $expirationDate = Carbon::now()->addYear();

        $subscription = $this->fakeSubscription(
            [
                'product_id' => $product['id'],
                'payment_method_id' => null,
                'user_id' => $userId,
                'total_price' => $product['price'],
                'paid_until' => Carbon::now()
                    ->subDay()
                    ->startOfDay()
                    ->toDateTimeString(),
                'is_active' => 0,
                'interval_count' => 1,
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'external_app_store_id' => $originalWebOrderLineItemId
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product['sku'],
            ]
        );

        $validationResponse =
            $this->getFirstRenewalPurchaseReceiptResponse(
                $originalTransactionId,
                $renewalTransactionId,
                $originalWebOrderLineItemId,
                $renewalWebOrderLineItemId,
                $product['sku'],
                $expirationDate->copy()
            );

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'RENEWAL',
                'web_order_line_item_id' => $renewalWebOrderLineItemId,
                'latest_receipt' => $receipt,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $receipt,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
                'valid' => true,
                'validation_error' => null,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'external_id' => $renewalTransactionId,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => 1,
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => $expirationDate->toDateTimeString(),
                'external_app_store_id' => $originalWebOrderLineItemId,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expirationDate
                    ->copy()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );
    }


    public function test_process_notification_subscription_renewal_multiple()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        Mail::fake();

        $email = $this->faker->email;

        $userId = $this->createAndLogInNewUser($email);
        $receipt = $this->faker->word;

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'price' => 12.95,
                'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
                'subscription_interval_count' => 1,
            ]
        );

        $originalWebOrderLineItemId = $this->faker->word;
        $renewalWebOrderLineItemId = $this->faker->word;
        $originalTransactionId = $this->faker->word;
        $renewalTransactionId = $this->faker->word;
        $expirationDate = Carbon::now()->addYear();

        $subscription = $this->fakeSubscription(
            [
                'product_id' => $product['id'],
                'payment_method_id' => null,
                'user_id' => $userId,
                'total_price' => $product['price'],
                'paid_until' => Carbon::now()
                    ->subDay()
                    ->startOfDay()
                    ->toDateTimeString(),
                'is_active' => 0,
                'interval_count' => 1,
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'external_app_store_id' => $originalWebOrderLineItemId
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product['sku'],
            ]
        );

        $validationResponse =
            $this->getFirstRenewalPurchaseReceiptResponse(
                $originalTransactionId,
                $renewalTransactionId,
                $originalWebOrderLineItemId,
                $renewalWebOrderLineItemId,
                $product['sku'],
                $expirationDate->copy()
            );

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'RENEWAL',
                'web_order_line_item_id' => $renewalWebOrderLineItemId,
                'latest_receipt' => $receipt,
            ]
        );

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'RENEWAL',
                'web_order_line_item_id' => $renewalWebOrderLineItemId,
                'latest_receipt' => $receipt,
            ]
        );

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'RENEWAL',
                'web_order_line_item_id' => $renewalWebOrderLineItemId,
                'latest_receipt' => $receipt,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'id' => 1,
                'receipt' => $receipt,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
                'valid' => true,
                'validation_error' => null,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'id' => 2,
                'receipt' => $receipt,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
                'valid' => true,
                'validation_error' => null,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'id' => 3,
                'receipt' => $receipt,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE,
                'valid' => true,
                'validation_error' => null,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
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
                'external_id' => $renewalTransactionId,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'id' => 2,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => 1,
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => $expirationDate->toDateTimeString(),
                'external_app_store_id' => $originalWebOrderLineItemId,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            [
                'id' => 2,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expirationDate
                    ->copy()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_user_products',
            [
                'id' => 2,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );
    }

    public function test_process_notification_subscription_deactivated()
    {
        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);

        $userId = $this->createAndLogInNewUser();

        $receipt = $this->faker->word;
        $originalWebOrderLineItemId = $this->faker->word;
        $renewalWebOrderLineItemId = $this->faker->word;
        $originalTransactionId = $this->faker->word;
        $renewalTransactionId = $this->faker->word;
        $expirationDate = Carbon::now()->addDays(12);

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'price' => 12.95,
                'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
                'subscription_interval_count' => 1,
            ]
        );

        $subscription = $this->fakeSubscription(
            [
                'product_id' => $product['id'],
                'payment_method_id' => null,
                'user_id' => $userId,
                'total_price' => $product['price'],
                'paid_until' => $expirationDate,
                'is_active' => 1,
                'interval_count' => 1,
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'external_app_store_id' => $originalWebOrderLineItemId
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product['sku'],
            ]
        );

        $validationResponse =
            $this->getDeactivatedReceiptResponse(
                $originalTransactionId,
                $renewalTransactionId,
                $originalWebOrderLineItemId,
                $renewalWebOrderLineItemId,
                $product['sku'],
                $expirationDate,
                2
            );

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'CANCEL',
                'latest_receipt' => $receipt,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $receipt,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_CANCEL_NOTIFICATION_TYPE,
                'valid' => true,
                'validation_error' => null,
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => 1,
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 0,
                'paid_until' => $expirationDate->toDateTimeString(),
                'external_app_store_id' => $originalWebOrderLineItemId,
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
                'external_id' => $originalTransactionId,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 2,
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'external_id' => $renewalTransactionId,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );


        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expirationDate
                    ->copy()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 2,
            ]
        );
    }

    public function test_process_notification_subscription_cancelled_with_refund()
    {
        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);

        $userId = $this->createAndLogInNewUser();

        $receipt = $this->faker->word;
        $originalWebOrderLineItemId = $this->faker->word;
        $renewalWebOrderLineItemId = $this->faker->word;
        $originalTransactionId = $this->faker->word;
        $renewalTransactionId = $this->faker->word;
        $expirationDate = Carbon::now()->subDays(1);
        $canceledOnDate = Carbon::now()->subHour(1);

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'price' => 12.95,
                'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
                'subscription_interval_count' => 1,
            ]
        );

        $subscription = $this->fakeSubscription(
            [
                'product_id' => $product['id'],
                'payment_method_id' => null,
                'user_id' => $userId,
                'total_price' => $product['price'],
                'paid_until' => $expirationDate,
                'is_active' => 1,
                'interval_count' => 1,
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'external_app_store_id' => $originalWebOrderLineItemId
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product['sku'],
            ]
        );

        $validationResponse =
            $this->getCancelledAndRefundedReceiptResponse(
                $originalTransactionId,
                $renewalTransactionId,
                $originalWebOrderLineItemId,
                $renewalWebOrderLineItemId,
                $product['sku'],
                $expirationDate,
                $canceledOnDate,
                1
            );

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'CANCEL',
                'latest_receipt' => $receipt,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $receipt,
                'request_type' => AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => AppleReceipt::APPLE_CANCEL_NOTIFICATION_TYPE,
                'valid' => false,
                'validation_error' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => 1,
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 0,
                'paid_until' => $expirationDate->toDateTimeString(),
                'external_app_store_id' => $originalWebOrderLineItemId,
                'canceled_on' => $canceledOnDate->toDateTimeString(),
                'cancellation_reason' => 1,
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
                'external_id' => $originalTransactionId,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 2,
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => $product['price'],
                'type' => Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'external_id' => $renewalTransactionId,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );


        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expirationDate
                    ->copy()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 2,
            ]
        );
    }


    protected function getInitialPurchaseReceiptResponse(
        $transactionId,
        $webOrderItemId,
        $productSku,
        Carbon $expirationDate,
        bool $isTrial
    )
    {
        // first purchase item needs transaction id and expiration date
        $appleProductsMap = array_flip(config('ecommerce.apple_store_products_map'));

        $purchaseItemArray = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$productSku],
            'expires_date_ms' => $expirationDate->timestamp * 1000,
            'transaction_id' => $transactionId,
            'web_order_line_item_id' => $webOrderItemId,
            'purchase_date_ms' => Carbon::now()->timestamp * 1000,
            'original_purchase_date' => Carbon::now()->timestamp * 1000,
            'is_trial_period' => $isTrial,
        ];

        $rawData = [
            'status' => 0,
            'environment' => 'Sandbox',
            'latest_receipt_info' => [$purchaseItemArray,],
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => Carbon::now()->timestamp * 1000,
                'in_app' => [$purchaseItemArray],
            ],
        ];

        return new SandboxResponse($rawData);
    }

    protected function getFirstRenewalPurchaseReceiptResponse(
        $originalTransactionId,
        $renewalTransactionId,
        $subscriptionWebOrderItemId,
        $renewalWebOrderItemId,
        $productSku,
        Carbon $expirationDate
    )
    {
        // first purchase item needs transaction id and expiration date
        $appleProductsMap = array_flip(config('ecommerce.apple_store_products_map'));

        $originalPurchaseItemArray = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$productSku],
            'expires_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'transaction_id' => $originalTransactionId,
            'web_order_line_item_id' => $subscriptionWebOrderItemId,
            'purchase_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'original_purchase_date' => Carbon::now()->subDays(7)->timestamp * 1000,
            'is_trial_period' => true,
        ];

        $renewalPurchaseItemArray = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$productSku],
            'expires_date_ms' => $expirationDate->timestamp * 1000,
            'transaction_id' => $renewalTransactionId,
            'web_order_line_item_id' => $renewalWebOrderItemId,
            'purchase_date_ms' => Carbon::now()->timestamp * 1000,
            'original_purchase_date' => Carbon::now()->timestamp * 1000,
            'is_trial_period' => false,
        ];

        $rawData = [
            'status' => 0,
            'environment' => 'Sandbox',
            'latest_receipt_info' => [$renewalPurchaseItemArray, $originalPurchaseItemArray],
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
                'in_app' => [$renewalPurchaseItemArray, $originalPurchaseItemArray],
            ],
        ];

        return new SandboxResponse($rawData);
    }

    protected function getDeactivatedReceiptResponse(
        $originalTransactionId,
        $renewalTransactionId,
        $subscriptionWebOrderItemId,
        $renewalWebOrderItemId,
        $productSku,
        Carbon $expirationDate,
        $expirationIntentNumber
    )
    {
        // first purchase item needs transaction id and expiration date
        $appleProductsMap = array_flip(config('ecommerce.apple_store_products_map'));

        $originalPurchaseItemArray = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$productSku],
            'expires_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'transaction_id' => $originalTransactionId,
            'web_order_line_item_id' => $subscriptionWebOrderItemId,
            'purchase_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'original_purchase_date' => Carbon::now()->subDays(7)->timestamp * 1000,
            'is_trial_period' => false,
        ];

        $renewalPurchaseItemArray = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$productSku],
            'expires_date_ms' => $expirationDate->timestamp * 1000,
            'transaction_id' => $renewalTransactionId,
            'web_order_line_item_id' => $renewalWebOrderItemId,
            'purchase_date_ms' => Carbon::now()->timestamp * 1000,
            'original_purchase_date' => Carbon::now()->timestamp * 1000,
            'is_trial_period' => false,
        ];

        $pendingRenewalInfoArray = [
            [
                'product_id' => 'drumeo_app_monthly_member',
                'auto_renew_product_id' => 'drumeo_app_monthly_member',
                'original_transaction_id' => $originalTransactionId,
                'auto_renew_status' => false,
                'expiration_intent' => $expirationIntentNumber,
                'is_in_billing_retry_period' => false,
            ]
        ];

        $rawData = [
            'status' => 0,
            'environment' => 'Sandbox',
            'latest_receipt_info' => [$renewalPurchaseItemArray, $originalPurchaseItemArray],
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
                'in_app' => [$renewalPurchaseItemArray, $originalPurchaseItemArray],
            ],
            'pending_renewal_info' => $pendingRenewalInfoArray
        ];

        return new SandboxResponse($rawData);
    }

    protected function getCancelledAndRefundedReceiptResponse(
        $originalTransactionId,
        $renewalTransactionId,
        $subscriptionWebOrderItemId,
        $renewalWebOrderItemId,
        $productSku,
        Carbon $expirationDate,
        Carbon $cancellationDate,
        $cancellationReasonNumber
    )
    {
        // first purchase item needs transaction id and expiration date
        $appleProductsMap = array_flip(config('ecommerce.apple_store_products_map'));

        $originalPurchaseItemArray = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$productSku],
            'expires_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'transaction_id' => $originalTransactionId,
            'web_order_line_item_id' => $subscriptionWebOrderItemId,
            'purchase_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
            'original_purchase_date' => Carbon::now()->subDays(7)->timestamp * 1000,
            'is_trial_period' => false,
        ];

        $renewalPurchaseItemArray = [
            'quantity' => 1,
            'product_id' => $appleProductsMap[$productSku],
            'expires_date_ms' => $expirationDate->timestamp * 1000,
            'transaction_id' => $renewalTransactionId,
            'web_order_line_item_id' => $renewalWebOrderItemId,
            'purchase_date_ms' => Carbon::now()->timestamp * 1000,
            'original_purchase_date' => Carbon::now()->timestamp * 1000,
            'is_trial_period' => false,
            'cancellation_date_ms' => $cancellationDate->timestamp * 1000,
            'cancellation_reason' => $cancellationReasonNumber,
        ];

        $pendingRenewalInfoArray = [
            [
                'product_id' => 'drumeo_app_monthly_member',
                'auto_renew_product_id' => 'drumeo_app_monthly_member',
                'original_transaction_id' => $originalTransactionId,
                'auto_renew_status' => false,
                'expiration_intent' => null,
                'is_in_billing_retry_period' => false,
            ]
        ];

        $rawData = [
            'status' => 0,
            'environment' => 'Sandbox',
            'latest_receipt_info' => [$renewalPurchaseItemArray, $originalPurchaseItemArray],
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => Carbon::now()->subDays(7)->timestamp * 1000,
                'in_app' => [$renewalPurchaseItemArray, $originalPurchaseItemArray],
            ],
            'pending_renewal_info' => $pendingRenewalInfoArray
        ];

        return new SandboxResponse($rawData);
    }
}

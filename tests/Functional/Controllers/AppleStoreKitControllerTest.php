<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
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
        $this->assertEquals([
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
        ], $response->decodeResponseJson('errors'));
    }

    public function test_process_receipt()
    {
        $receipt = $this->faker->word;
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
                'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
                'subscription_interval_count' => 1,
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'sku' => 'product-two',
                'price' => 247,
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
                'subscription_interval_count' => 1,
            ]
        );

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $productOne['sku'],
                $this->faker->word => $productTwo['sku'],
            ]
        );

        $webOrderLineItemOneId = $this->faker->word;
        $webOrderLineItemTwoId = $this->faker->word;

        $productsData = [
            $productOne['sku'] => [
                'web_order_line_item_id' => $webOrderLineItemOneId,
            ],
            $productTwo['sku'] => [ // expired product
                'web_order_line_item_id' => $webOrderLineItemTwoId,
                'expires_date_ms' => Carbon::now()->subMonth()
            ]
        ];

        $validationResponse = $this->getReceiptValidationResponse($productsData);

        $this->appleStoreKitGatewayMock->method('validate')
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
                'created_at' => Carbon::now(),
            ]
        );

        // assert database records were created for productOne
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'id' => 1,
                'total_due' => $productOne['price'],
                'product_due' => $productOne['price'],
                'taxes_due' => 0,
                'shipping_due' => 0,
                'finance_due' => 0,
                'total_paid' => $productOne['price'],
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_items',
            [
                'product_id' => $productOne['id'],
                'quantity' => 1,
                'initial_price' => $productOne['price'],
                'final_price' => $productOne['price'],
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $productOne['price'],
                'total_paid' => $productOne['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_APPLE_INITIAL_ORDER,
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
                'paid_until' => Carbon::now()
                    ->addMonth()
                    ->toDateTimeString(),
                'external_app_store_id' => $webOrderLineItemOneId,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $productOne['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addMonth()
                    ->toDateTimeString(),
            ]
        );

        // assert database records were not created for the expired productTwo
        $this->assertDatabaseMissing(
            'ecommerce_order_items',
            [
                'product_id' => $productTwo['id'],
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            [
                'product_id' => $productTwo['id'],
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_user_products',
            [
                'product_id' => $productTwo['id'],
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Payment::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => ActionLogService::ACTOR_SYSTEM,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_SYSTEM,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Subscription::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => ActionLogService::ACTOR_SYSTEM,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_SYSTEM,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Order::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => ActionLogService::ACTOR_SYSTEM,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_SYSTEM,
            ]
        );
    }

    public function test_process_receipt_validation_exception()
    {
        $receipt = $this->faker->word;
        $email = $this->faker->email;
        $exceptionMessage = $this->faker->word;

        $appleStoreKitGateway =
            $this->getMockBuilder(AppleStoreKitGateway::class)
                ->disableOriginalConstructor()
                ->getMock();

        $appleStoreKitGateway->method('validate')
            ->willThrowException(new ReceiptValidationException($exceptionMessage));

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

        $userId  = $this->createAndLogInNewUser($email);
        $receipt = $this->faker->word;

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'price' => 12.95,
            'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
            'subscription_interval_count' => 1,
        ]);

        $webOrderLineItemId = $this->faker->word;

        $subscription = $this->fakeSubscription([
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
            'external_app_store_id' => $webOrderLineItemId
        ]);

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product['sku'],
            ]
        );

        $productsData = [
            $product['sku'] => [
                'web_order_line_item_id' => $webOrderLineItemId,
            ],
        ];

        $validationResponse = $this->getReceiptValidationResponse($productsData);

        $this->appleStoreKitGatewayMock->method('validate')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'RENEWAL',
                'web_order_line_item_id' => $webOrderLineItemId,
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
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addMonth()
                    ->startOfDay()
                    ->toDateTimeString(),
                'external_app_store_id' => $webOrderLineItemId,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addMonth()
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        Mail::assertSent(SubscriptionInvoice::class, 1);

        Mail::assertSent(
            SubscriptionInvoice::class,
            function ($mail) use ($email) {
                $mail->build(); // raises an exception if brand is not configured under ecommerce.invoice_email_details

                return $mail->hasTo($email) &&
                    $mail->hasFrom(config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_sender')) &&
                    $mail->subject(
                        config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_email_subject')
                    );
            }
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Payment::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => ActionLogService::ACTOR_SYSTEM,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_SYSTEM,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Subscription::class,
                'resource_id' => 1,
                'action_name' => Subscription::ACTION_RENEW,
                'actor' => ActionLogService::ACTOR_SYSTEM,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_SYSTEM,
            ]
        );
    }

    public function test_process_notification_subscription_cancel()
    {
        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);

        $userId  = $this->createAndLogInNewUser();
        $receipt = $this->faker->word;

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'price' => 12.95,
            'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
            'subscription_interval_count' => 1,
        ]);

        $webOrderLineItemId = $this->faker->word;

        $paidUntil = Carbon::now()
            ->addDays(10)
            ->startOfDay()
            ->toDateTimeString();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $userId,
            'total_price' => $product['price'],
            'paid_until' => $paidUntil,
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_monthly'),
            'external_app_store_id' => $webOrderLineItemId
        ]);

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product['sku'],
            ]
        );

        $productsData = [
            $product['sku'] => [
                'web_order_line_item_id' => $webOrderLineItemId,
            ],
        ];

        $validationResponse = $this->getReceiptValidationResponse($productsData);

        $this->appleStoreKitGatewayMock->method('validate')
            ->willReturn($validationResponse);

        $response = $this->call(
            'POST',
            '/apple/handle-server-notification',
            [
                'notification_type' => 'CANCEL',
                'web_order_line_item_id' => $webOrderLineItemId,
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
                'user_id' => 1,
                'product_id' => $product['id'],
                'paid_until' => $paidUntil,
                'canceled_on' => Carbon::now(),
                'external_app_store_id' => $webOrderLineItemId,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Subscription::class,
                'resource_id' => 1,
                'action_name' => Subscription::ACTION_CANCEL,
                'actor' => ActionLogService::ACTOR_SYSTEM,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_SYSTEM,
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

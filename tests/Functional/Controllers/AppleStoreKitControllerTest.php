<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Exceptions\AppleStoreKit\ReceiptValidationException;
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

        $appleStoreKitGateway =
            $this->getMockBuilder(AppleStoreKitGateway::class)
                ->disableOriginalConstructor()
                ->getMock();

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

        $appleStoreKitGateway->method('validate')
            ->willReturn($validationResponse);

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

        // assert the response status code
        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = $response->decodeResponseJson();

        // assert response has meta key with auth code
        $this->assertTrue(isset($decodedResponse['meta']['auth_code']));

        $this->assertDatabaseHas(
            'ecommerce_apple_receipts',
            [
                'receipt' => $receipt,
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
                'type' => Payment::TYPE_INITIAL_APPLE_ORDER,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $productOne['id'],
                'paid_until' => Carbon::now()
                    ->addMonth()
                    ->toDateTimeString(),
                'web_order_line_item_id' => $webOrderLineItemOneId,
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

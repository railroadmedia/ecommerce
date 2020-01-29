<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Google_Service_AndroidPublisher_SubscriptionPurchase;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Controllers\GooglePlayStoreController;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Mail\SubscriptionInvoice;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use ReceiptValidator\GooglePlay\SubscriptionResponse;

class GooglePlayStoreControllerTest extends EcommerceTestCase
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

    public function test_get_real_responses()
    {
        // to use this, put the google api json credentials file in the root, named api.json

        config()->set('ecommerce.payment_gateways.google_play_store', [
            'credentials' => '/app/ecommerce/api.json',
            'application_name' => 'com.drumeo',
            'scope' => ['https://www.googleapis.com/auth/androidpublisher'],
        ]);

        /**
         * @var $gateway GooglePlayStoreGateway
         */
        $gateway = app(GooglePlayStoreGateway::class);

        // change these manually to pull real data
        $response = $gateway->getResponse(
            'com.drumeo',
            'drumeo_app_1_month_member',
            'kjlodknlagepgpeinbiljjfg.AO-J1Oy9PGdSDwwHwez8qy7o9aABZyU4gaYEr98lv1v_8Xg8dswA0mN6vdCUrUD-oG0BreD9wDhnqhklghoETFigquzPFc5O62GsM3XdNQzuqSAGsYYw6YgXwdnaMCxjfNX9D-ZrBxGV'
        );

//        dd($response);

        $this->assertTrue(true);

        /*
         * This is a newly ordered trial for monthly product response from google.
         * No payment has been made, they are in the trial period.
         * This user has never purchased a trial in the past is will not be charged for 7 days.

            ReceiptValidator\GooglePlay\SubscriptionResponse {#1647
              #response: Google_Service_AndroidPublisher_SubscriptionPurchase {#1642
                +acknowledgementState: 1
                +autoRenewing: true
                +autoResumeTimeMillis: null
                +cancelReason: null
                #cancelSurveyResultType: "Google_Service_AndroidPublisher_SubscriptionCancelSurveyResult"
                #cancelSurveyResultDataType: ""
                +countryCode: "US"
                +developerPayload: ""
                +emailAddress: null
                +expiryTimeMillis: "1580931718023"
                +familyName: null
                +givenName: null
                #introductoryPriceInfoType: "Google_Service_AndroidPublisher_IntroductoryPriceInfo"
                #introductoryPriceInfoDataType: ""
                +kind: "androidpublisher#subscriptionPurchase"
                +linkedPurchaseToken: null
                +orderId: "GPA.3308-2947-4667-42299"
                +paymentState: 2
                +priceAmountMicros: "29990000"
                #priceChangeType: "Google_Service_AndroidPublisher_SubscriptionPriceChange"
                #priceChangeDataType: ""
                +priceCurrencyCode: "USD"
                +profileId: null
                +profileName: null
                +promotionCode: null
                +promotionType: null
                +purchaseType: null
                +startTimeMillis: "1580312523295"
                +userCancellationTimeMillis: null
                #internal_gapi_mappings: []
                #modelData: []
                #processed: []
              }
            }

         */
    }

    public function test_process_receipt_validation()
    {
        $response = $this->call('POST', '/google/verify-receipt-and-process-payment', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.package_name',
                'detail' => 'The package name field is required.',
            ],
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.product_id',
                'detail' => 'The product id field is required.',
            ],
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.purchase_token',
                'detail' => 'The purchase token field is required.',
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
            ],
        ], $response->decodeResponseJson('errors'));
    }

    public function test_process_receipt_trial_order()
    {
        $orderId = $this->faker->word . rand();
        $email = $this->faker->email;
        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);

        $product = $this->fakeProduct(
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

        $googleProductId = $this->faker->word;

        config()->set(
            'ecommerce.google_store_products_map',
            [
                $googleProductId => $product['sku'],
            ]
        );

        $googleStoreKitGateway =
            $this->getMockBuilder(GooglePlayStoreGateway::class)
                ->disableOriginalConstructor()
                ->getMock();

        $validationResponse = $this->getReceiptValidationResponse(1, $orderId);

        $googleStoreKitGateway->method('validate')
            ->willReturn($validationResponse);

        $this->app->instance(GooglePlayStoreGateway::class, $googleStoreKitGateway);

        $packageName = $this->faker->word;
        $purchaseToken = $this->faker->word;

        $response = $this->call(
            'POST',
            '/google/verify-receipt-and-process-payment',
            [
                'data' => [
                    'attributes' => [
                        'package_name' => $packageName,
                        'product_id' => $googleProductId,
                        'purchase_token' => $purchaseToken,
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
            'ecommerce_google_receipts',
            [
                'package_name' => $packageName,
                'product_id' => $googleProductId,
                'purchase_token' => $purchaseToken,
                'request_type' => GoogleReceipt::MOBILE_APP_REQUEST_TYPE,
                'email' => $email,
                'valid' => true,
                'validation_error' => null,
                'order_id' => $orderId,
                'raw_receipt_response' => serialize($validationResponse),
                'created_at' => Carbon::now(),
            ]
        );

        // // assert database records were created for productOne
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'id' => 1,
                'total_due' => $product['price'],
                'product_due' => $product['price'],
                'taxes_due' => 0,
                'shipping_due' => 0,
                'finance_due' => 0,
                'total_paid' => $product['price'],
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_items',
            [
                'product_id' => $product['id'],
                'quantity' => 1,
                'initial_price' => $product['price'],
                'final_price' => $product['price'],
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_GOOGLE_INITIAL_ORDER,
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
                    ->toDateTimeString(),
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
                    ->toDateTimeString(),
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

    public function test_process_notification_subscription_renewal()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        $orderId = $this->faker->word . rand();

        Mail::fake();

        $email = $this->faker->email;

        $user = $this->fakeUser(
            [
                'email' => $email,
            ]
        );

        $receipt = $this->faker->word;

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'price' => 12.95,
            'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
            'subscription_interval_count' => 1,
        ]);

        $packageName = $this->faker->word;
        $purchaseToken = $this->faker->word;

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $user['id'],
            'total_price' => $product['price'],
            'paid_until' => Carbon::now()
                ->subDay()
                ->startOfDay()
                ->toDateTimeString(),
            'is_active' => 0,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_monthly'),
            'external_app_store_id' => $purchaseToken
        ]);

        $googleProductId = $this->faker->word;

        config()->set(
            'ecommerce.google_store_products_map',
            [
                $googleProductId => $product['sku'],
            ]
        );

        $googleStoreKitGateway =
            $this->getMockBuilder(GooglePlayStoreGateway::class)
                ->disableOriginalConstructor()
                ->getMock();

        $validationResponse = $this->getReceiptValidationResponse(1, $orderId);

        $googleStoreKitGateway->method('validate')
            ->willReturn($validationResponse);

        $this->app->instance(GooglePlayStoreGateway::class, $googleStoreKitGateway);

        $requestData = [
            'subscriptionNotification' => [
                'notificationType' => GooglePlayStoreController::SUBSCRIPTION_RENEWED,
                'purchaseToken' => $purchaseToken,
                'subscriptionId' => $googleProductId,
            ],
            'packageName' => $packageName
        ];

        $enceodedRequestData = base64_encode(json_encode($requestData));

        $response = $this->call(
            'POST',
            '/google/handle-server-notification',
            [
                'message' => [
                    'data' => $enceodedRequestData,
                ]
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_google_receipts',
            [
                'purchase_token' => $purchaseToken,
                'package_name' => $packageName,
                'product_id' => $googleProductId,
                'request_type' => GoogleReceipt::GOOGLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => GoogleReceipt::GOOGLE_RENEWAL_NOTIFICATION_TYPE,
                'valid' => true,
                'validation_error' => null,
                'order_id' => $orderId,
                'raw_receipt_response' => serialize($validationResponse),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
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
                'external_app_store_id' => $purchaseToken,
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
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                    ->startOfDay()
                    ->toDateTimeString(),
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

        $userId = $this->createAndLogInNewUser();
        $receipt = $this->faker->word;

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'price' => 12.95,
            'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
            'subscription_interval_count' => 1,
        ]);

        $paidUntil = Carbon::now()
            ->addDays(10)
            ->startOfDay()
            ->toDateTimeString();

        $packageName = $this->faker->word;
        $purchaseToken = $this->faker->word;

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $userId,
            'total_price' => $product['price'],
            'paid_until' => $paidUntil,
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_monthly'),
            'external_app_store_id' => $purchaseToken
        ]);

        $googleProductId = $this->faker->word;

        config()->set(
            'ecommerce.google_store_products_map',
            [
                $googleProductId => $product['sku'],
            ]
        );

        $googleStoreKitGateway =
            $this->getMockBuilder(GooglePlayStoreGateway::class)
                ->disableOriginalConstructor()
                ->getMock();

        $validationResponse = $this->getReceiptValidationResponse();

        $googleStoreKitGateway->method('validate')
            ->willReturn($validationResponse);

        $this->app->instance(GooglePlayStoreGateway::class, $googleStoreKitGateway);

        $requestData = [
            'subscriptionNotification' => [
                'notificationType' => GooglePlayStoreController::SUBSCRIPTION_CANCELED,
                'purchaseToken' => $purchaseToken,
                'subscriptionId' => $googleProductId,
            ],
            'packageName' => $packageName
        ];

        $enceodedRequestData = base64_encode(json_encode($requestData));

        $response = $this->call(
            'POST',
            '/google/handle-server-notification',
            [
                'message' => [
                    'data' => $enceodedRequestData,
                ]
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_google_receipts',
            [
                'purchase_token' => $purchaseToken,
                'package_name' => $packageName,
                'product_id' => $googleProductId,
                'request_type' => GoogleReceipt::GOOGLE_NOTIFICATION_REQUEST_TYPE,
                'notification_type' => GoogleReceipt::GOOGLE_CANCEL_NOTIFICATION_TYPE,
                'valid' => true,
                'validation_error' => null,
                'raw_receipt_response' => serialize($validationResponse),
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
                'external_app_store_id' => $purchaseToken,
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
        $paymentState = 1,
        $orderId = null,
        $expiryTimestamp = null
    ): SubscriptionResponse {

        $dependency = new Google_Service_AndroidPublisher_SubscriptionPurchase();

        $dependency->setPaymentState($paymentState);
        $dependency->setOrderId($orderId ?? $this->faker->word . $this->faker->numberBetween());
        $dependency->setExpiryTimeMillis(
            $expiryTimestamp ?? Carbon::now()
                ->addMonth()
                ->getTimestamp() * 1000
        );

        return new SubscriptionResponse($dependency);
    }
}

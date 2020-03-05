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
use Railroad\Ecommerce\Controllers\GooglePlayStoreController;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Services\GooglePlayStoreService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use ReceiptValidator\GooglePlay\PurchaseResponse;
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
        // see: docs/in-app-purchases/android-receipt-data-dump.txt
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
//        $response = $gateway->getResponse(
//            'com.drumeo',
//            'drumeo_app_1_month_member',
//            'kjlodknlagepgpeinbiljjfg.AO-J1Oy9PGdSDwwHwez8qy7o9aABZyU4gaYEr98lv1v_8Xg8dswA0mN6vdCUrUD-oG0BreD9wDhnqhklghoETFigquzPFc5O62GsM3XdNQzuqSAGsYYw6YgXwdnaMCxjfNX9D-ZrBxGV'
//        );

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

    public function test_new_trial_order_success()
    {
        $orderId = $this->faker->word . rand();
        $email = $this->faker->email;
        $expiryTime = Carbon::now()->addWeek();
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

        $validationResponse = $this->getTestReceiptInitialTrial($orderId, $expiryTime->timestamp);

        $googleStoreKitGateway->method('getResponse')
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
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()->addWeek()->toDateTimeString(),
            ]
        );

        // make sure a payment and order is not created, since its just a trial sign up
        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_orders',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addWeek()

                    // 1 day buffer
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_new_order_trial_already_used()
    {
        // If the customer already used their trial time in the past google will charge them the full amount
        // on initial order. It's dictated by the payment state
        $orderId = $this->faker->word . rand();
        $email = $this->faker->email;
        $expiryTime = Carbon::now()->addMonth();
        $startTime = Carbon::now();
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

        $validationResponse = $this->getTestReceiptPaidRenewal($orderId, $expiryTime->timestamp, $startTime->timestamp);

        $googleStoreKitGateway->method('getResponse')
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
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => $expiryTime->toDateTimeString(),
                'start_date' => $startTime->toDateTimeString(),
                'created_at' => $startTime->toDateTimeString(),
            ]
        );

        // make sure a payment and order is not created, since its just a trial sign up
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => $startTime->toDateTimeString(),
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'id' => 1,
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_orders',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expiryTime
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only'))
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_new_trial_order_token_cannot_be_used_twice()
    {
        $orderId = $this->faker->word . rand();
        $email = $this->faker->email;
        $newEmail = $this->faker->email;
        $expiryTime = Carbon::now()->addWeek();
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

        $validationResponse = $this->getTestReceiptInitialTrial($orderId, $expiryTime->timestamp);

        $googleStoreKitGateway->method('getResponse')
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

        // should fail validation, we cant let other users claim the same token
        $response = $this->call(
            'POST',
            '/google/verify-receipt-and-process-payment',
            [
                'data' => [
                    'attributes' => [
                        'package_name' => $packageName,
                        'product_id' => $googleProductId,
                        'purchase_token' => $purchaseToken,
                        'email' => $newEmail,
                        'password' => $this->faker->word,
                    ]
                ]
            ]
        );

        // assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        $decodedResponse = $response->decodeResponseJson();

        // assert response has meta key with auth code
        $this->assertEquals('Validation failed.', $decodedResponse['errors'][0]['title']);
        $this->assertEquals('data.attributes.purchase_token', $decodedResponse['errors'][0]['source']);
        $this->assertEquals('The purchase token has already been taken.', $decodedResponse['errors'][0]['detail']);
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

    public function test_process_notification_subscription_renewal_one()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        $orderId = $this->faker->word . rand() . '..0';
        $expiryTime = Carbon::now()->addMonth(1);
        $startTime = Carbon::now()->subDays(7);

        Mail::fake();

        $email = $this->faker->email;

        $user = $this->fakeUser(
            [
                'email' => $email,
            ]
        );

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

        $validationResponse = $this->getTestReceiptPaidRenewal($orderId, $expiryTime->timestamp, $startTime->timestamp);

        $googleStoreKitGateway->method('getResponse')
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
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => $expiryTime->copy()->subMonth()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => $expiryTime->toDateTimeString(),
                'external_app_store_id' => $purchaseToken,
                'start_date' => $startTime->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expiryTime
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 5))
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_process_notification_subscription_renewal_two()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        $orderId = $this->faker->word . rand() . '..1';
        $expiryTime = Carbon::now()->addMonth()->subHour();
        $startTime = Carbon::now()->subDays(7)->subMonth();

        Mail::fake();

        $email = $this->faker->email;

        $user = $this->fakeUser(
            [
                'email' => $email,
            ]
        );

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

        $validationResponse = $this->getTestReceiptPaidRenewal($orderId, $expiryTime->timestamp, $startTime->timestamp);

        $googleStoreKitGateway->method('getResponse')
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
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 1,
                'external_id' => $orderId,
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->subHour()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 2,
                'external_id' => substr($orderId, 0, strlen($orderId) - 1) . '0',
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->subMonth()->subHour()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => $expiryTime->toDateTimeString(),
                'external_app_store_id' => $purchaseToken,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expiryTime
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 5))
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_process_notification_subscription_renewal_two_ensure_no_dupes()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        $orderId = $this->faker->word . rand() . '..1';
        $expiryTime = Carbon::now()->addMonth();
        $startTime = Carbon::now()->subDays(7)->subMonth();

        Mail::fake();

        $email = $this->faker->email;

        $user = $this->fakeUser(
            [
                'email' => $email,
            ]
        );

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

        $validationResponse = $this->getTestReceiptPaidRenewal($orderId, $expiryTime->timestamp, $startTime->timestamp);

        $googleStoreKitGateway->method('getResponse')
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

        $response = $this->call(
            'POST',
            '/google/handle-server-notification',
            [
                'message' => [
                    'data' => $enceodedRequestData,
                ]
            ]
        );

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
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 1,
                'external_id' => $orderId,
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 2,
                'external_id' => substr($orderId, 0, strlen($orderId) - 1) . '0',
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'total_refunded' => 0,
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->subMonth()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'id' => 3,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscription_payments',
            [
                'id' => 3,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => $expiryTime->toDateTimeString(),
                'external_app_store_id' => $purchaseToken,
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
                'expiration_date' => $expiryTime
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 5))
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_process_notification_subscription_cancel()
    {
        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);

        $userId = $this->createAndLogInNewUser();

        $expiryTime = Carbon::now()->addDays(2);
        $cancelTime = Carbon::now();
        $cancelReason = 2;
        $orderId = $this->faker->word . rand();

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

        $validationResponse = $this->getTestReceiptSubscriptionCancel(
            $orderId,
            $cancelReason,
            $expiryTime->timestamp,
            $cancelTime->timestamp
        );

        $googleStoreKitGateway->method('getResponse')
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
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'paid_until' => $expiryTime->toDateTimeString(),
                'canceled_on' => $cancelTime->toDateTimeString(),
                'external_app_store_id' => $purchaseToken,
                'cancellation_reason' => GooglePlayStoreService::$cancellationReasonMap[$cancelReason],
            ]
        );
    }

    /**
     * @param $orderId
     * @param $expiryTimestamp
     * @return SubscriptionResponse
     */
    protected function getTestReceiptInitialTrial($orderId, $expiryTimestamp): SubscriptionResponse
    {
        $dependency = new Google_Service_AndroidPublisher_SubscriptionPurchase();

        $dependency->setAutoRenewing(true);
        $dependency->setPaymentState(2);
        $dependency->setOrderId($orderId);
        $dependency->setExpiryTimeMillis($expiryTimestamp * 1000);

        return new SubscriptionResponse($dependency);
    }

    /**
     * @param $orderId
     * @param $expiryTimestamp
     * @param $startTimestamp
     * @return SubscriptionResponse
     */
    protected function getTestReceiptPaidRenewal($orderId, $expiryTimestamp, $startTimestamp): SubscriptionResponse
    {
        $dependency = new Google_Service_AndroidPublisher_SubscriptionPurchase();

        $dependency->setAutoRenewing(true);
        $dependency->setPaymentState(1);
        $dependency->setOrderId($orderId);
        $dependency->setExpiryTimeMillis($expiryTimestamp * 1000);
        $dependency->setStartTimeMillis($startTimestamp * 1000);

        return new SubscriptionResponse($dependency);
    }

    /**
     * @param $orderId
     * @param $cancelReasonNumber
     * @param $expiryTimestamp
     * @param $cancelTimestamp
     * @return SubscriptionResponse
     */
    protected function getTestReceiptSubscriptionCancel(
        $orderId,
        $cancelReasonNumber,
        $expiryTimestamp,
        $cancelTimestamp
    ): SubscriptionResponse {

        $dependency = new Google_Service_AndroidPublisher_SubscriptionPurchase();

        $dependency->setAutoRenewing(false);
        $dependency->setCancelReason($cancelReasonNumber);

        // if its still in the trial period, will be 2, otherwise null
        $dependency->setPaymentState($this->faker->randomElement([2, null]));
        $dependency->setOrderId($orderId);
        $dependency->setExpiryTimeMillis($expiryTimestamp * 1000);
        $dependency->setUserCancellationTimeMillis($cancelTimestamp * 1000);

        return new SubscriptionResponse($dependency);
    }

    /**
     * @param $orderId
     * @param $productId
     * @return PurchaseResponse
     */
    protected function getTestReceiptProduct($orderId, $productId): PurchaseResponse
    {
        $dependency = new \Google_Service_AndroidPublisher_ProductPurchase();

        $dependency->setOrderId($orderId);
        $dependency->setProductId($productId);
        $dependency->setQuantity(1);

        return new PurchaseResponse($dependency);
    }

    public function test_process_receipt_pack_purchase()
    {
        $orderId = $this->faker->word;
        $email = $this->faker->email;
        $brand = 'drumeo';

        config()->set('ecommerce.brand', $brand);

        $product = $this->fakeProduct(
            [
                'sku' => 'product-one',
                'price' => 12.95,
                'type' => Product::TYPE_DIGITAL_ONE_TIME,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
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

        $validationResponse = $this->getTestReceiptProduct($orderId, $product['id']);

        $googleStoreKitGateway->method('validatePurchase')
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
                        'purchase_type' => GoogleReceipt::GOOGLE_PRODUCT_PURCHASE
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
                'purchase_type' => GoogleReceipt::GOOGLE_PRODUCT_PURCHASE,
                'request_type' => GoogleReceipt::MOBILE_APP_REQUEST_TYPE,
                'email' => $email,
                'valid' => true,
                'validation_error' => null,
                'order_id' => $orderId,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        // we dont want order rows
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'type' => Payment::TYPE_INITIAL_ORDER,
                'status' => Payment::STATUS_PAID,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => null
            ]
        );
    }

    public function test_process_receipt_subscription_and_free_pack_purchase()
    {
        $orderId = $this->faker->word;
        $email = $this->faker->email;
        $startTime = Carbon::now()->subDays(7)->subMonth();
        $expiryTime = Carbon::now()->addWeek();
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

        $pack = $this->fakeProduct(
            [
                'sku' => 'product-two',
                'price' => 12.95,
                'type' => Product::TYPE_DIGITAL_ONE_TIME,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
            ]
        );

        $googleProductId = $this->faker->word;

        config()->set(
            'ecommerce.google_store_products_map',
            [
                $googleProductId => [$product['sku'], $pack['sku']],
            ]
        );

        $googleStoreKitGateway =
            $this->getMockBuilder(GooglePlayStoreGateway::class)
                ->disableOriginalConstructor()
                ->getMock();

        $validationResponse = $this->getTestReceiptPaidRenewal($orderId, $expiryTime->timestamp, $startTime->timestamp);

        $googleStoreKitGateway->method('getResponse')
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
                        'password' => $this->faker->word
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
                'purchase_type' => GoogleReceipt::GOOGLE_SUBSCRIPTION_PURCHASE,
                'request_type' => GoogleReceipt::MOBILE_APP_REQUEST_TYPE,
                'email' => $email,
                'valid' => true,
                'validation_error' => null,
                'order_id' => $orderId,
                'raw_receipt_response' => base64_encode(serialize($validationResponse)),
                'created_at' => Carbon::now(),
            ]
        );

        // we dont want order rows
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $product['price'],
                'total_paid' => $product['price'],
                'type' => Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
                'status' => Payment::STATUS_PAID
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'id' => 3,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $expiryTime
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 5))
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => 1,
                'product_id' => $pack['id'],
                'quantity' => 1,
                'expiration_date' => null,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => 1,
                'product_id' => $product['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()->addWeek()->toDateTimeString(),
            ]
        );
    }
}
